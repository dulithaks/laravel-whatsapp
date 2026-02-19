<?php

namespace Duli\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'wa_messages';

    protected $fillable = [
        'wa_message_id',
        'from_phone',
        'to_phone',
        'direction',
        'message_type',
        'body',
        'status',
        'status_updated_at',
        'payload',
    ];

    protected $casts = [
        'payload'           => 'array',
        'status_updated_at' => 'datetime',
    ];

    /**
     * Scope for incoming messages
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    /**
     * Scope for outgoing messages
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    /**
     * Scope for a specific phone number
     */
    public function scopeForPhone($query, string $phone)
    {
        return $query->where(function ($q) use ($phone) {
            $q->where('from_phone', $phone)
                ->orWhere('to_phone', $phone);
        });
    }
}
