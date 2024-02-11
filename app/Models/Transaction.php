<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'total_amount',
        'cancelled',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
