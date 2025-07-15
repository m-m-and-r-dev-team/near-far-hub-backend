<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained('listings')->onDelete('cascade');
            $table->datetime('appointment_datetime');
            $table->integer('duration_minutes')->default(30);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->text('buyer_message')->nullable();
            $table->text('seller_response')->nullable();
            $table->text('meeting_location')->nullable();
            $table->text('meeting_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_appointments');
    }
};
