<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    protected $guarded = ['id'];

    public function images()
    {
        return $this->morphMany(Media::class, 'model');
    }
}
