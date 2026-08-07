<?php

namespace App\CQRT\Commands;

use App\Data\IngestDocumentData;
use App\Jobs\VectorizeAndSaveChunkJob;
use App\Services\AIAssistantService;

class IngestCommand
{
    public function __construct(
        private readonly AIAssistantService $aiService
    ) {}

    public function __invoke(IngestDocumentData $data): int
    {
        $chunks = $this->chunkText($data->content);
        $processedCount = 0;

        foreach ($chunks as $chunk) {
            VectorizeAndSaveChunkJob::dispatch($data->title, $chunk);
            $processedCount++;
        }

        return $processedCount;
    }

    private function chunkText(string $text): array
    {
        $chunks = explode("\n\n", $text);

        return array_values(array_filter(array_map('trim', $chunks)));
    }
}
