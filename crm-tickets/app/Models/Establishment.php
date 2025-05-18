<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establishment extends Model
{
    protected $fillable = [
        'name',
        'description',
        'template_data',
    ];
    
    protected $casts = [
        'template_data' => 'array',
    ];
    
    /**
     * Get the tickets associated with the establishment.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
    
    /**
     * Get the product mappings associated with the establishment.
     */
    public function itemProductMappings(): HasMany
    {
        return $this->hasMany(ItemProductMapping::class);
    }
}
