<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_depreciation_rate',
        'depreciation_method',
        'default_useful_life_years',
        'is_active',
    ];

    protected $casts = [
        'default_depreciation_rate' => 'decimal:2',
        'default_useful_life_years' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
