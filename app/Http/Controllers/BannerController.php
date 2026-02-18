<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BannerResource;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Banner::with('media')->get();
    }

    public function publicIndex() {
        // Only fetch active banners, ordered by priority
        return Banner::where('is_active', true)
                    ->orderBy('order_priority', 'asc')
                    ->get()
                    ->map(fn($b) => [
                        'id' => $b->id,
                        'image' => asset('storage/' . $b->image_path),
                        'url' => $b->link_url,
                        'title' => $b->title
                    ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'title' => 'required|string',
                'link_url' => 'nullable|string',
                'order_priority' => 'nullable|integer',
                'is_active' => 'nullable|boolean',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            $banner = Banner::create($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('banners/gallery', 'public');
                    $banner->media()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'disk' => 'public',
                        'path' => $path
                    ]);
                }
            }

            return new BannerResource($banner->load('media'));
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banner $banner) {
        return DB::transaction(function () use ($request, $banner) {
            $banner->update($request->all());

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('banners/gallery', 'public');
                    $banner->media()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'size' => $file->getSize(),
                        'disk' => 'public',
                        'path' => $path
                    ]);
                }
            }

            return new BannerResource($banner->load('media'));
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();
        return response()->json($banner);
    }
}
