<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class Stock extends Model
{
    protected $fillable = [
        'product_id', 
        'branch_id', 
        'quantity', 
        'purchase_price', 
        'sale_price', 
        'discount_percent',
        'min_stock_level'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function logs()
    {
        return $this->hasMany(StockLog::class);
    }
}
