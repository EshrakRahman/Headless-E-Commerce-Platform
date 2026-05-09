<?php

namespace App\Models;

use Database\Factories\SizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Size extends Model
{
    /** @use HasFactory<SizeFactory> */
    use HasFactory;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_size');
    }
}
