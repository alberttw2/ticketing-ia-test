<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketItem extends Model
{
    protected $fillable = [
        'ticket_id',
        'name',
        'price',
        'quantity',
        'total',
        'product_id',
        'manually_verified',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total' => 'decimal:2',
        'manually_verified' => 'boolean',
    ];
    
    /**
     * Get the ticket that owns the item.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
    
    /**
     * Get the product associated with this item (if any).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Calculate the total price of this item.
     */
    public function calculateTotal()
    {
        return $this->price * $this->quantity;
    }
}
