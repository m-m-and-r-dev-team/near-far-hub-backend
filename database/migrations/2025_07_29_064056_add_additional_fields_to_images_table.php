<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn('related_id');

            $table->string('imageable_type')->after('id');
            $table->unsignedBigInteger('imageable_id')->after('imageable_type');

            $table->string('alt_text')->nullable()->after('type');
            $table->integer('sort_order')->default(0)->after('alt_text');
            $table->boolean('is_active')->default(true)->after('is_primary');
            $table->json('metadata')->nullable()->after('is_active');
            $table->integer('width')->nullable()->after('metadata');
            $table->integer('height')->nullable()->after('width');
            $table->integer('file_size')->nullable()->after('height');

            $table->index(['imageable_type', 'imageable_id']);
            $table->index(['type', 'is_active']);
            $table->index(['is_primary', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['imageable_type', 'imageable_id']);
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['is_primary', 'sort_order']);

            $table->dropColumn([
                'imageable_type', 'imageable_id', 'alt_text', 'sort_order',
                'is_active', 'metadata', 'width', 'height', 'file_size'
            ]);

            $table->unsignedBigInteger('related_id')->after('id');
        });
    }
};