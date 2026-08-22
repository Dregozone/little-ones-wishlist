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
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_pennies')->default(0);
            $table->string('product_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('cached_image_path')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index('is_visible');
            $table->index('price_pennies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
