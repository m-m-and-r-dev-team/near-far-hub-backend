<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable'); // Creates imageable_type and imageable_id columns
            $table->enum('type', [
                'profile',
                'listing',
                'seller_verification',
                'user_avatar',
                'listing_gallery',
                'listing_thumbnail'
            ]);
            $table->string('filename');
            $table->string('original_name');
            $table->string('path');
            $table->string('url');
            $table->bigInteger('size'); // File size in bytes
            $table->string('mime_type');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Store thumbnails, upload info, etc.
            $table->timestamps();

            // Indexes for performance
            $table->index(['imageable_type', 'imageable_id']);
            $table->index(['type', 'is_active']);
            $table->index(['is_primary', 'sort_order']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};