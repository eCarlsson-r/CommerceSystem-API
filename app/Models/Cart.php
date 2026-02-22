<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'quantity'
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer'
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }
}
