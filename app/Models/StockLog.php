<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLog extends Model
{
    protected $fillable = [
        'stock_id', 'reference_id', 'type', 
        'description', 'quantity_change', 'balance_after', 'user_id'
    ];

    // Relationships
    public function stock() {
        return $this->belongsTo(Stock::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}