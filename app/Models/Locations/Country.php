<?php

// app/Models/Locations/Country.php
declare(strict_types=1);

namespace App\Models\Locations;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'code_alpha3', 'phone_code', 'currency_code', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}