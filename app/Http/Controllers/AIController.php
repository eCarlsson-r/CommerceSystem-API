<?php

namespace App\Http\Controllers;

use App\Services\LaravelAiKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(private readonly LaravelAiKitService $aiService)
    {
    }

    public function recommendations(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'productId' => 'nullable|integer',
            'contextTags' => 'nullable|array',
            'contextTags.*' => 'string',
            'maxResults' => 'nullable|integer|min:1|max:20',
            'locale' => 'nullable|string|max:10',
            'sessionId' => 'nullable|string|max:100',
            'customerId' => 'nullable|integer',
        ]);

        return response()->json($this->aiService->recommendations($payload));
    }

    public function visualSearch(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'imageUrl' => 'required|string|max:2048',
            'maxResults' => 'nullable|integer|min:1|max:20',
            'locale' => 'nullable|string|max:10',
            'sessionId' => 'nullable|string|max:100',
            'customerId' => 'nullable|integer',
        ]);

        return response()->json($this->aiService->visualSearch($payload));
    }

    public function assistant(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'message' => 'required|string|max:3000',
            'context' => 'nullable|array',
            'locale' => 'nullable|string|max:10',
            'sessionId' => 'nullable|string|max:100',
            'customerId' => 'nullable|integer',
        ]);

        return response()->json($this->aiService->assistant($payload));
    }

    public function translateDraft(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'sourceLocale' => 'required|string|max:10',
            'targetLocale' => 'required|string|max:10',
            'text' => 'required|string|max:10000',
            'glossary' => 'nullable|array',
            'glossary.*' => 'string',
            'locale' => 'nullable|string|max:10',
            'sessionId' => 'nullable|string|max:100',
            'customerId' => 'nullable|integer',
        ]);

        return response()->json($this->aiService->translateDraft($payload));
    }
}
