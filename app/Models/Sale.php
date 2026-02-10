<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\BranchScope;

class Sale extends Model {
    use SoftDeletes;
    
    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    protected $fillable = [
        'invoice_number',
        'date',
        'branch_id',
        'employee_id',
        'customer_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'status',
    ];

    protected $guarded = ['id'];
    
    // Relationships
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(SalePayment::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}