<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model {
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'purchase_price',
        'sale_price',
        'discount_amount',
        'total_price',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'purchase_price' => 'integer',
        'sale_price' => 'integer',
        'discount_amount' => 'integer',
        'total_price' => 'integer',
    ];
    
    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
}