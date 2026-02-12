<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'return_number',
        'supplier_id',
        'branch_id',
        'reason',
        'total_amount',
        'user_id',
        'return_date'
    ];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
