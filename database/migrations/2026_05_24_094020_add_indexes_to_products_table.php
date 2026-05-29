<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Resolve any duplicate slugs first
        $duplicateSlugs = DB::table('products')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicateSlugs as $slug) {
            $products = DB::table('products')->where('slug', $slug)->get();
            // Skip the first one, and update the rest
            foreach ($products->skip(1) as $index => $product) {
                $newSlug = $product->slug.'-'.($index + 1).'-'.Str::random(4);
                DB::table('products')->where('id', $product->id)->update(['slug' => $newSlug]);
            }
        }

        // 2. Add indexes
        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
            $table->index('is_featured');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['created_at']);
        });
    }
};
