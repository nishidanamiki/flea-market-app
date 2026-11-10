<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutReservation extends Model
{
    protected $fillable = [
        'item_id',
        'user_id',
        'status',
        'expires_at',
        'stripe_session_id',
        'attempt_key',
        'address_id',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
