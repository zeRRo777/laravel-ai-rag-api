<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    #[Override]
    protected function casts()
    {
        return [
            'embedding' => 'array',
        ];
    }
}
