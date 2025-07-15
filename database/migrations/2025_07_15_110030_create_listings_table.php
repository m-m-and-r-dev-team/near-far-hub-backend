<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_profile_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('category');
            $table->string('condition')->nullable();
            $table->json('images')->nullable();
            $table->json('location')->nullable();
            $table->boolean('can_deliver_globally')->default(false);
            $table->boolean('requires_appointment')->default(false);
            $table->enum('status', ['draft', 'active', 'sold', 'expired', 'suspended'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('favorites_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['seller_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};