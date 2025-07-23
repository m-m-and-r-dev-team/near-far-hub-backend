<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('listing_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'listing_id']);
            $table->index('user_id');
            $table->index('listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_favorites');
    }
};
