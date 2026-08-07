<?php

namespace App\Services;

use App\Data\RAGAnswerData;
use App\Exceptions\AI\AIApiException;
use App\Models\Document;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAssistantService
{
    /**
     * Получение эмбеддинга (вектора) из текста через локальную Ollama (bge-m3)
     */
    public function getEmbedding(string $text): array
    {
        $url = config('services.ollama.url').'/v1/embeddings';
        $model = config('services.ollama.model');

        $payload = [
            'model' => $model,
            'input' => $text,
        ];

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(30)
                ->retry(3, 500, function (Exception $exception, $request) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException && $exception->response->serverError()) {
                        return true;
                    }

                    return false;
                })
                ->throw()
                ->post($url, $payload);
        } catch (ConnectionException|RequestException $e) {
            Log::error('API ИИ не отвечает: '.$e->getMessage(), [
                'status' => $e->response?->status(),
                'body' => $e->response?->body(),
            ]);

            throw new AIApiException('Api ии временно недоступен.');
        } catch (Exception $e) {
            Log::error('Ошибка обработки данных AI API');

            throw new AIApiException('Ошибка обработки данных AI API');
        }

        $result = $response->json('data.0.embedding');

        if (empty($result) || ! is_array($result)) {
            Log::error('Пустой или некорректный ответ от AI API', [
                'response' => $response->body(),
                'parsed' => $result,
            ]);
            throw new AIApiException('AI API вернул пустой или некорректный ответ.');
        }

        return $result;
    }

    /**
     * RAG-поиск: отвечает на вопрос, используя векторный поиск по базе
     */
    public function askQuestionWithContext(string $question): RAGAnswerData
    {
        $question = trim($question);

        $embedding = $this->getEmbedding($question);
        $vectorString = '['.implode(',', $embedding).']';
        $threshold = 0.35;

        $documents = Document::query()
            ->select('id', 'content')
            ->whereRaw('(1 - (embedding <=> ?::vector)) >= ?', [$vectorString, $threshold])
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit(3)
            ->get();

        if ($documents->isEmpty()) {
            return RAGAnswerData::from([
                'answer' => 'Извините, я не нашел информации по вашему вопросу в базе знаний.',
                'sourceIds' => [],
            ]);
        }

        $contextString = $documents->map(fn ($doc) => "Документ #{$doc->id}:\n{$doc->content}")->implode("\n\n");
        $sourceIds = $documents->pluck('id')->toArray();

        $answer = $this->generateLLMResponse($question, $contextString);

        return RAGAnswerData::from([
            'answer' => $answer,
            'sourceIds' => $sourceIds,
        ]);
    }

    /**
     * Отправка запроса в большую LLM для генерации ответа
     */
    protected function generateLLMResponse(string $question, string $context): string
    {
        $url = config('services.ai.url');
        $token = config('services.ai.key');
        $model = config('services.ai.model');

        $systemPrompt = "Ты — корпоративный ИИ-ассистент. Отвечай на вопрос пользователя, опираясь ТОЛЬКО на предоставленный контекст.\n".
            "Если в контексте нет ответа на вопрос, честно скажи: \"Я не знаю ответа на этот вопрос на основе предоставленной базы знаний\".\n".
            "Не придумывай информацию. Будь краток и вежлив.\n\n".
            "КОНТЕКСТ:\n{$context}";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $question],
            ],
            'stream' => false,
            'temperature' => 0.1,
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(30)
                ->retry(
                    3,
                    500,
                    function (Exception $exception, $request) {
                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException && $exception->response->serverError()) {
                            return true;
                        }

                        return false;
                    }
                )
                ->post($url, $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::error('API ИИ не отвечает: '.$e->getMessage(), [
                'status' => $e->response?->status(),
                'body' => $e->response?->body(),
            ]);

            throw new AIApiException('Api ии временно недоступен.');
        } catch (Exception $e) {
            Log::error('Ошибка обработки данных AI API');

            throw new AIApiException('Ошибка обработки данных AI API');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            Log::error('Пустой ответ от AI API при RAG-запросе', ['response' => $response->body()]);
            throw new AIApiException('AI API вернул пустой ответ.');
        }

        return $content;
    }
}
