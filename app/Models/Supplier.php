<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'tax_id',
        'address',
        'phone',
    ];

    protected $guarded = ['id'];

    
}
