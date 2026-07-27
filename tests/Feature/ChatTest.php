<?php

use App\Data\RAGAnswerData;
use App\Services\AIAssistantService;
use Mockery\MockInterface;
use function Pest\Laravel\postJson;

test('успешно обрабатывает вопрос и возвращает ответ от ИИ с источниками', function () {
    $question = 'Как получить компенсацию за интернет?';

    $this->mock(AIAssistantService::class, function (MockInterface $mock) use ($question) {
        $mock->shouldReceive('askQuestionWithContext')
            ->once()
            ->with($question)
            ->andReturn(new RAGAnswerData(
                answer: 'Для получения компенсации загрузите чек на портал до 5 числа.',
                sourceIds: [2, 5]
            ));
    });

    $payload = [
        'question' => $question
    ];

    $response = postJson('/api/v1/chat', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'answer' => 'Для получения компенсации загрузите чек на портал до 5 числа.',
            'sourceIds' => [2, 5]
        ]);
});

test('возвращает ошибку валидации 422, если вопрос не передан', function () {
    $response = postJson('/api/v1/chat', []);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 422,
            'title'  => 'Validation Error',
        ])
        ->assertJsonPath('errors.question', fn($errors) => is_array($errors));
});

test('возвращает ошибку валидации 422, если вопрос слишком короткий', function () {
    $response = postJson('/api/v1/chat', [
        'question' => 'А?'
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.question', fn($errors) => is_array($errors));
});
