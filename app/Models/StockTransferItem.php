<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
        'purchase_price',
        'sale_price',
    ];

    protected $guarded = ['id'];
    
    // An item belongs to one transfer
    public function transfer() {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}