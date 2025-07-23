<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2)->unique();
            $table->string('code_alpha3', 3)->unique();
            $table->string('phone_code', 10)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->string('type')->default('state');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_id', 'is_active']);
            $table->unique(['country_id', 'code']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('population')->nullable();
            $table->string('google_place_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_id', 'state_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
            $table->index(['name', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email_verified_at');
            $table->text('bio')->nullable()->after('phone');

            $table->string('location_display')->nullable()->after('bio');
            $table->json('location_data')->nullable()->after('location_display');

            $table->foreignId('country_id')->nullable()->constrained('countries')->after('location_data');
            $table->foreignId('state_id')->nullable()->constrained('states')->after('country_id');
            $table->foreignId('city_id')->nullable()->constrained('cities')->after('state_id');

            $table->string('address_line')->nullable()->after('city_id');
            $table->string('postal_code', 20)->nullable()->after('address_line');

            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            $table->string('google_place_id')->nullable()->after('longitude');
        });

        Schema::create('location_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->string('query');
            $table->string('type')->default('search');
            $table->json('data');
            $table->string('source')->default('hybrid');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['cache_key', 'expires_at']);
            $table->index(['query', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn([
                'phone', 'bio', 'location_display', 'location_data',
                'country_id', 'state_id', 'city_id', 'address_line',
                'postal_code', 'latitude', 'longitude', 'google_place_id'
            ]);
        });

        Schema::dropIfExists('location_cache');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};