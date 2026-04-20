<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Str;

class LaravelAiKitService
{
    /**
     * Placeholder implementation designed to be provider-agnostic.
     * This service keeps endpoint contracts stable while model providers evolve.
     */
    public function recommendations(array $payload): array
    {
        $maxResults = (int) ($payload['maxResults'] ?? 6);

        $items = Stock::query()
            ->where('quantity', '>', 0)
            ->inRandomOrder()
            ->take($maxResults)
            ->get()
            ->map(fn ($stock, $index) => [
                'productId' => (int) $stock->product_id,
                'score' => round(max(0.5, 1 - ($index * 0.1)), 2),
                'reason' => 'In-stock and related style match',
            ])
            ->values()
            ->all();

        return ['items' => $items];
    }

    public function visualSearch(array $payload): array
    {
        // Reuse recommendation strategy for now to keep API behavior stable.
        return $this->recommendations($payload);
    }

    public function assistant(array $payload): array
    {
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return ['reply' => 'Please share your room style, colors, and wall size to get suggestions.'];
        }

        return [
            'reply' => 'Based on your request, start with medium-contrast patterns for focal walls and order one sample roll before full purchase. '
                .'If you share wall dimensions, I can estimate roll quantities.',
            'followUps' => [
                'What room type is this wallpaper for?',
                'Do you prefer subtle or statement patterns?',
                'Would you like washable material recommendations?',
            ],
        ];
    }

    public function translateDraft(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $targetLocale = (string) ($payload['targetLocale'] ?? 'en');

        if ($text === '') {
            return ['translatedText' => '', 'qualityHint' => 'review_needed'];
        }

        return [
            'translatedText' => '['.Str::upper($targetLocale).'] '.$text,
            'qualityHint' => 'draft',
        ];
    }
}
