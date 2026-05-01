<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'category_id', 'description', 'image', 'slug', 'price', 'quantity', 'is_featured'])]
class Product extends Model
{
    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
