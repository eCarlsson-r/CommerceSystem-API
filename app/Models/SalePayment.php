<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model {
    protected $fillable = [
        'sale_id',
        'payment_method',
        'bank_name',
        'reference_number',
        'amount_paid',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'amount_paid' => 'integer',
    ];
    
    public function sale() { return $this->belongsTo(Sale::class); }
}
