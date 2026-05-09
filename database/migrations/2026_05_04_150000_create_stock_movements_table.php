<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_size_id')->nullable()->constrained('product_size')->cascadeOnDelete();
            $table->string('type'); // initial, adjustment, order, refund
            $table->integer('before_quantity');
            $table->integer('quantity_change');
            $table->integer('after_quantity');
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
