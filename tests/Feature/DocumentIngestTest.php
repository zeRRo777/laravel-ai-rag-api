<?php

use App\Jobs\VectorizeAndSaveChunkJob;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\postJson;


test('успешно принимает документ, разбивает на чанки и отправляет в очередь', function () {
    Queue::fake();

    $payload = [
        'title' => 'Laravel AI RAG',
        'content' => "Первый абзац про Laravel.\n\nВторой абзац про AI.\n\nТретий абзац про RAG."
    ];

    $endpoint = 'api/v1/documents/ingest';

    $response = postJson($endpoint, $payload);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Документ успешно разбит на части и добавлен в очередь на векторизацию.',
            'processedCount' => 3
        ]);

    Queue::assertPushed(VectorizeAndSaveChunkJob::class, 3);

    Queue::assertPushed(VectorizeAndSaveChunkJob::class, function (VectorizeAndSaveChunkJob $job) {
        return $job->title === 'Laravel AI RAG' && $job->content === 'Первый абзац про Laravel.';
    });
});

test('возвращает ошибку валидации 422, если не передан обязательный контент', function () {
    $payload = [
        'title' => 'Только заголовок',
    ];

    $response = postJson('/api/v1/documents/ingest', $payload);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 422,
            'title'  => 'Validation Error',
        ])
        ->assertJsonPath('errors.content', fn($errors) => is_array($errors));
});
