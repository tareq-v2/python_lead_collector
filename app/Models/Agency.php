<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    // These fields can be mass-assigned (e.g. Agency::create([...]))
    protected $fillable = [
        'name',
        'slug',
        'website',
        'country',
        'city',
        'email',
        'linkedin_url',
        'github_url',
        'clutch_url',
        'company_size',
        'founded_year',
        'description',
        'clutch_rating',
        'reviews_count',
        'source',
        'is_verified',
        'last_scraped_at',
    ];

    // Cast these columns to their proper PHP types
    protected $casts = [
        'is_verified'     => 'boolean',
        'last_scraped_at' => 'datetime',
        'clutch_rating'   => 'decimal:1',
    ];

    // ── Relationships ───────────────────────────────────────────

    // An agency has many services (Laravel, React, etc.)
    // Connected through the agency_services pivot table
    public function services()
    {
        return $this->belongsToMany(Service::class, 'agency_services');
    }

    // ── Query Scopes (reusable filters) ─────────────────────────

    // Usage: Agency::fromCountry('Bangladesh')->get()
    public function scopeFromCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    // Usage: Agency::withService('Laravel')->get()
    public function scopeWithService($query, $service)
    {
        return $query->whereHas('services', function ($q) use ($service) {
            $q->where('name', $service);
        });
    }

    // Usage: Agency::hasEmail()->get()
    public function scopeHasEmail($query)
    {
        return $query->whereNotNull('email')
                     ->where('email', '!=', '');
    }
}
