<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // A service belongs to many agencies
    public function agencies()
    {
        return $this->belongsToMany(Agency::class, 'agency_services');
    }
}
