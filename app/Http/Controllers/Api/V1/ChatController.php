<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\ChatData;
use App\Services\AIAssistantService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChatController extends Controller
{
    #[OA\Post(
        path: '/api/v1/chat',
        summary: 'Задать вопрос AI-ассистенту (RAG)',
        description: 'Принимает вопрос пользователя, ищет наиболее релевантные фрагменты текста в векторной базе данных (pgvector) и генерирует ответ через LLM, опираясь исключительно на найденный корпоративный контекст.',
        tags: ['Chat'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Данные запроса',
            content: new OA\JsonContent(
                required: ['question'],
                properties: [
                    new OA\Property(
                        property: 'question',
                        type: 'string',
                        example: 'Сколько дней отпуска я могу взять минимум за один раз?',
                        description: 'Текст вопроса пользователя',
                        minLength: 3,
                        maxLength: 1000
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ от нейросети',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'answer',
                            type: 'string',
                            example: 'Согласно регламенту, отпуск можно дробить, но одна из частей должна быть не менее 14 дней.',
                            description: 'Сгенерированный ответ ИИ на основе базы знаний'
                        ),
                        new OA\Property(
                            property: 'sourceIds',
                            type: 'array',
                            description: 'Массив ID документов (чанков), которые использовались в качестве контекста для формирования ответа (полезно для вывода сносок в UI)',
                            items: new OA\Items(type: 'integer'),
                            example: [2, 5]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации (пустой или слишком короткий вопрос)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'http://localhost:8080/errors/validation-error'),
                        new OA\Property(property: 'title', type: 'string', example: 'Validation Error'),
                        new OA\Property(property: 'status', type: 'integer', example: 422),
                        new OA\Property(property: 'detail', type: 'string', example: 'Произошла одна или несколько ошибок проверки.'),
                        new OA\Property(property: 'instance', type: 'string', example: '/api/v1/chat'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Список ошибок валидации',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            ),
                            example: [
                                'question' => ['Вопрос должен содержать не менее 3 символов.'],
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function chat(ChatData $data, AIAssistantService $aIAssistantService): JsonResponse
    {
        $result = $aIAssistantService->askQuestionWithContext($data->question);

        return response()->json($result);
    }
}
