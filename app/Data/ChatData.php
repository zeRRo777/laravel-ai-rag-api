<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class ChatData extends Data
{
    public function __construct(
        #[Min(3), Max(1000)]
        public string $question
    ) {}

    public static function attributes(...$args)
    {
        return [
            'question' => 'Вопрос',
        ];
    }
}
