<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Media;

class MediaController extends Controller
{
    // app/Http/Controllers/MediaController.php
    public function store(UploadMediaRequest $request)
    {
        // 1. Data is already validated at this point!
        $data = $request->validated();

        // 2. Resolve the model (e.g., Product::find(101))
        $modelMap = [
            'product' => Product::class,
            'employee' => Employee::class,
        ];
        
        $parent = $modelMap[$data['model_type']]::findOrFail($data['model_id']);

        // 3. Store file & Save to Database
        $path = $request->file('file')->store("uploads/{$data['model_type']}", 'public');
        
        $media = $parent->media()->create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $request->file('file')->getMimeType(),
            'size' => $request->file('file')->getSize(),
        ]);

        return response()->json($media, 201);
    }

    public function destroy(Media $media)
    {
        // Deletes the physical file and the database record in one click
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
        
        return response()->noContent();
    }
}
