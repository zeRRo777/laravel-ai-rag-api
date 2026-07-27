<?php

namespace App\Data;

use Override;
use Spatie\LaravelData\Data;

class IngestDocumentData extends Data
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}

    public static function attributes(...$args)
    {
        return [
            'title' => 'Название',
            'content' => 'Контент'
        ];
    }
}
