<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'stock', 'category_id'];

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

    public function images()
    {
        return $this->morphMany(Media::class, 'model');
    }
}
