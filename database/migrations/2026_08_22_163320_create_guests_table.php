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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->boolean('has_consented')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // A guest may hold several tokens at once: one per device, plus any issued when
        // they recover their list from a saved link. Keeping them in their own table means
        // recognising a guest on a second device never invalidates the first.
        Schema::create('guest_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_tokens');
        Schema::dropIfExists('guests');
    }
};
