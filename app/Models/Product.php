<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'description', 'category_id', 'base_price', 'min_stock_alert'];

    protected $casts = [
        'base_price' => 'decimal:2',
        'min_stock_alert' => 'integer',
    ];

    protected $guarded = ['id'];

    // Accessor for eCommerce to show "Out of Stock"
    protected $appends = ['is_available'];

    public function getIsAvailableAttribute()
    {
        return $this->stock > 0;
    }

    // Scope for Angular POS to find low stock items
    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('stock', '<=', $threshold);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function stocks() {
        return $this->hasMany(Stock::class); // This links to your 'stocks' table (the branch-product pivot)
    }
}
