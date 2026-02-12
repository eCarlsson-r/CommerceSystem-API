<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    // No need to define $table if it's named 'stock_transfers'
    protected $fillable = [
        'from_branch_id', 
        'to_branch_id', 
        'created_by',
        'date', 
        'status'
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}