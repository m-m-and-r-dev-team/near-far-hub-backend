<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained('listings')->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->enum('fee_type', ['listing', 'promotion', 'featured']);
            $table->enum('status', ['pending', 'paid', 'refunded']);
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_fees');
    }
};
