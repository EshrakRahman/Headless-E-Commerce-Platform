<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Size extends Model
{
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_size');
    }
}
