<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemProductMapping extends Model
{
    protected $fillable = [
        'establishment_id',
        'item_name',
        'product_id',
        'confidence',
        'manually_verified',
    ];
    
    protected $casts = [
        'confidence' => 'float',
        'manually_verified' => 'boolean',
    ];
    
    /**
     * Get the establishment that this mapping belongs to.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }
    
    /**
     * Get the product that this mapping links to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
