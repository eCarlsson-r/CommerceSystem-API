<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preview extends Model
{
    protected $fillable = [
        'preview_id',
        'customer_id',
        'product_id',
        'cart_id',
        'product_image',
        'room_dimensions',
        'selected_wall',
        'tile_scale',
        'pattern_repeat',
        'wall_coverage',
        'room_preview_url',
        'metadata',
    ];

    protected $casts = [
        'tile_scale' => 'float',
        'pattern_repeat' => 'integer',
        'room_dimensions' => 'array',
        'wall_coverage' => 'array',
        'metadata' => 'array',
    ];
}
