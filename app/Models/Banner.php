<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'link_url',
        'order_priority',
        'is_active',
    ];

    protected $guarded = ['id'];

    public function media()
    {
        return $this->morphOne(Media::class, 'model');
    }
}
