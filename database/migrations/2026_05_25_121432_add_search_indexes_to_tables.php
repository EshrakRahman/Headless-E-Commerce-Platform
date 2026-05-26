<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('price');
            $table->index('category_id');
        });

        Schema::table('sizes', function (Blueprint $table) {
            $table->index('name');
        });

        Schema::table('product_size', function (Blueprint $table) {
            $table->index(['product_id', 'size_id']);
            $table->index(['size_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['price']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('sizes', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('product_size', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'size_id']);
            $table->dropIndex(['size_id', 'product_id']);
        });
    }
};
