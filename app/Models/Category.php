<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    protected $guarded = ['id'];

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }
}
