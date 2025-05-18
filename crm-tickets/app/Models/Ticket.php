<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    // Status constants
    const STATUS_NEW = 'NEW';
    const STATUS_PROCESSED = 'PROCESSED';
    const STATUS_ERROR = 'ERROR';
    const STATUS_REVIEW = 'REVIEW';
    
    protected $fillable = [
        'establishment_id',
        'filename',
        'original_path',
        'status',
        'raw_text',
        'ocr_text',
        'total_amount',
        'ticket_date',
        'processing_log',
        'ai_analysis',
        'manually_reviewed',
    ];
    
    protected $casts = [
        'ticket_date' => 'date',
        'total_amount' => 'decimal:2',
        'manually_reviewed' => 'boolean',
        'ai_analysis' => 'array',
        'processing_log' => 'array',
    ];
    
    /**
     * Get the establishment that owns the ticket.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }
    
    /**
     * Get the items for this ticket.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TicketItem::class);
    }
    
    /**
     * Get the total amount of all items.
     */
    public function calculateTotal()
    {
        return $this->items()->sum('total');
    }
}
