<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category_id', 'description', 'image', 'slug', 'price', 'quantity', 'is_featured'])]
class Product extends Model
{
    use SoftDeletes;
    public function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'deleted_at'  => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
