<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preview extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'cart_id',
        'image_url',
        'tile_scale',
        'blend_intensity',
        'wall_polygon',
        'metadata',
    ];

    protected $casts = [
        'tile_scale' => 'float',
        'blend_intensity' => 'float',
        'wall_polygon' => 'array',
        'metadata' => 'array',
    ];
}
