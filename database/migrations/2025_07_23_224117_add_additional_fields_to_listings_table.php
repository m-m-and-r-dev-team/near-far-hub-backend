<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Add slug for SEO-friendly URLs
            $table->string('slug')->unique()->after('title');

            // Add additional product details
            $table->json('tags')->nullable()->after('location');
            $table->json('delivery_options')->nullable()->after('can_deliver_globally');
            $table->json('dimensions')->nullable()->after('delivery_options'); // length, width, height, unit
            $table->decimal('weight', 8, 2)->nullable()->after('dimensions');
            $table->string('brand', 100)->nullable()->after('weight');
            $table->string('model', 100)->nullable()->after('brand');
            $table->unsignedSmallInteger('year')->nullable()->after('model');
            $table->string('color', 50)->nullable()->after('year');
            $table->string('material', 100)->nullable()->after('color');

            // Add additional indexes for better performance
            $table->index(['category', 'status']);
            $table->index(['price', 'status']);
            $table->index(['created_at', 'status']);
            $table->index(['views_count', 'status']);
            $table->index(['favorites_count', 'status']);
            $table->index('slug');

            // Full-text search index for title and description (MySQL specific)
            // $table->fullText(['title', 'description']); // Uncomment if using MySQL 5.7+
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'tags',
                'delivery_options',
                'dimensions',
                'weight',
                'brand',
                'model',
                'year',
                'color',
                'material'
            ]);

            $table->dropIndex(['category', 'status']);
            $table->dropIndex(['price', 'status']);
            $table->dropIndex(['created_at', 'status']);
            $table->dropIndex(['views_count', 'status']);
            $table->dropIndex(['favorites_count', 'status']);
            $table->dropIndex(['slug']);
        });
    }
};