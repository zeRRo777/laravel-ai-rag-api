<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RAGAnswerData extends Data
{
    /**
     * @param  string  $answer  Ответ от нейросети
     * @param  array<int>  $sourceIds  ID документов, которые использовались для ответа
     */
    public function __construct(
        public string $answer,
        public array $sourceIds,
    ) {}
}
