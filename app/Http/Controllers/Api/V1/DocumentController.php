<?php

namespace App\Http\Controllers\Api\V1;

use App\CQRT\Commands\IngestCommand;
use App\Data\IngestDocumentData;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

class DocumentController extends Controller
{
    #[OA\Post(
        path: '/api/v1/documents/ingest',
        summary: 'Загрузка текста для векторизации',
        description: 'Принимает текст документа, разбивает его на смысловые части (абзацы) и ставит в очередь на получение эмбеддингов (векторизацию) через AI (Ollama).',
        tags: ['Documents'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Данные документа в формате JSON',
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        example: 'Основы Laravel AI',
                        description: 'Заголовок документа'
                    ),
                    new OA\Property(
                        property: 'content',
                        type: 'string',
                        example: "Первый абзац про Laravel.\n\nВторой абзац про AI RAG.",
                        description: 'Текст документа. Для корректного разделения на чанки используйте двойной перенос строки (\n\n).'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Документ успешно принят, разбит на части и отправлен в очередь.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Документ успешно разбит на части и добавлен в очередь на векторизацию.'
                        ),
                        new OA\Property(
                            property: 'processedCount',
                            type: 'integer',
                            example: 2,
                            description: 'Количество созданных чанков (задач в очереди)'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации (например, не передан контент)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'http://localhost:8080/errors/validation-error'),
                        new OA\Property(property: 'title', type: 'string', example: 'Validation Error'),
                        new OA\Property(property: 'status', type: 'integer', example: 422),
                        new OA\Property(property: 'detail', type: 'string', example: 'Произошла одна или несколько ошибок проверки.'),
                        new OA\Property(property: 'instance', type: 'string', example: '/api/v1/documents/ingest'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Список ошибок валидации',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            ),
                            example: [
                                'content' => ['Поле content обязательно для заполнения.'],
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function ingest(IngestDocumentData $data, IngestCommand $ingestCommand): JsonResponse
    {
        $proccessedCount = $ingestCommand($data);

        return response()->json([
            'message' => 'Документ успешно разбит на части и добавлен в очередь на векторизацию.',
            'processedCount' => $proccessedCount,
        ]);
    }
}
