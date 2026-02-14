<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'total_amount',
        'shipping_address',
        'courier_service',
        'tracking_number',
        'sale_id',
        'branch_id'
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2'
    ];

    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

    public function sale() {
        return $this->belongsTo(Sale::class);
    }
}
