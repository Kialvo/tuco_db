<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    protected $fillable = ['gateway', 'event_id', 'type', 'payload', 'processed_at', 'processing_error'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];
}
