<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'link_url' => $this->link_url,
            'order_priority' => $this->order_priority,
            'is_active' => $this->is_active,
            // Map the images to return full URLs
            'images' => $this->media->map(fn($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . $img->path),
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
