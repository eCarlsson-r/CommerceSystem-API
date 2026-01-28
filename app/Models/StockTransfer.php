<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    // No need to define $table if it's named 'stock_transfers'
    protected $fillable = [
        'from_branch_id', 
        'to_branch_id', 
        'date', 
        'status'
    ];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}