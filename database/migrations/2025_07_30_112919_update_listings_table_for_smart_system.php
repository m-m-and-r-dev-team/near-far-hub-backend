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
            $table->string('slug')->unique()->after('title');
            $table->decimal('original_price', 10, 2)->nullable()->after('price');
            $table->string('brand', 50)->nullable()->after('condition');
            $table->string('model', 50)->nullable()->after('brand');
            $table->year('year')->nullable()->after('model');

            $table->json('location_data')->nullable()->after('year');
            $table->string('location_display')->nullable()->after('location_data');
            $table->decimal('latitude', 10, 8)->nullable()->after('location_display');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            $table->json('delivery_options')->nullable()->after('can_deliver_globally');

            $table->json('category_attributes')->nullable()->after('requires_appointment');
            $table->json('tags')->nullable()->after('category_attributes');

            $table->timestamp('featured_until')->nullable()->after('status');

            $table->integer('contact_count')->default(0)->after('favorites_count');

            $table->string('meta_title')->nullable()->after('contact_count');
            $table->text('meta_description')->nullable()->after('meta_title');

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['seller_profile_id', 'status']);
            $table->index(['price', 'status']);
            $table->index(['featured_until', 'status']);
            $table->index(['latitude', 'longitude']);
            $table->index(['brand', 'model']);
            $table->index(['condition', 'status']);

            $table->fullText(['title', 'description']);
        });

        if (Schema::hasColumn('listings', 'location')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['category_id']);

            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropIndex(['seller_profile_id', 'status']);
            $table->dropIndex(['price', 'status']);
            $table->dropIndex(['featured_until', 'status']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['brand', 'model']);
            $table->dropIndex(['condition', 'status']);
            $table->dropFullText(['title', 'description']);

            $table->dropColumn([
                'slug', 'original_price', 'brand', 'model', 'year',
                'location_data', 'location_display', 'latitude', 'longitude',
                'delivery_options', 'category_attributes', 'tags',
                'featured_until', 'contact_count', 'meta_title', 'meta_description'
            ]);

            $table->json('location')->nullable();
        });
    }
};

