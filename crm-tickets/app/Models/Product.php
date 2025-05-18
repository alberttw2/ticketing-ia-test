<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
    ];
    
    /**
     * Get the ticket items associated with this product.
     */
    public function ticketItems(): HasMany
    {
        return $this->hasMany(TicketItem::class);
    }
    
    /**
     * Get the mappings to establishment items.
     */
    public function itemMappings(): HasMany
    {
        return $this->hasMany(ItemProductMapping::class);
    }
}
