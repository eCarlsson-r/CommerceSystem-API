<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'file_name', 'mime_type', 'extension', 'size', 'disk', 'path'
    ];

    /**
     * This allows the file to belong to any other Model (Product, Employee, etc.)
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Helper to get the full browser URL for Angular/Next.js
     */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        return Storage::disk($this->disk)->url($this->path);
    }
}