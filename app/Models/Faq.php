<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer_beginner',
        'answer_advance',
        'answer_tldr',
        'lang',
        'categories',
        'highlight',
        'priority',
        'link',
    ];

    public function getCategoryListAttribute(): array
    {
        return array_map('trim', explode(',', $this->categories));
    }
}
