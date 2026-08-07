<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\AIAssistantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VectorizeAndSaveChunkJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $content,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AIAssistantService $aiService): void
    {
        $embeddingArray = $aiService->getEmbedding($this->content);
        $vectorString = '['.implode(',', $embeddingArray).']';

        Document::create([
            'title' => $this->title,
            'content' => $this->content,
            'embedding' => $vectorString,
        ]);
    }
}
