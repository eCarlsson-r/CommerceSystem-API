<?php

namespace App\Http\Controllers;

use App\Services\LaravelAiKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    /**
     * Magic Button: Generate Text Descriptions
     */
    public function generateDescription(Request $request)
    {
        $prompt = "Generate a professional, SEO-optimized retail description for: " . $request->input('context');
        
        // Use Vertex AI / Gemini 2.5 Flash for speed
        $response = $this->aiService->postToVertex("gemini-3.1-flash-lite-002", "generateContent", [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]]
                ]
            ]
        ]);

        return response()->json(['text' => $response->json('candidates.0.content.parts.0.text')]);
    }

    /**
     * Magic Button: Generate Product Image
     */
    public function generateImage(Request $request)
    {
        // Use Imagen 3 via Vertex AI
        $response = $this->aiService->postToVertex("imagen-3", "predict", [
            'instances' => [['prompt' => $request->input('prompt')]],
            'parameters' => ['sampleCount' => 1]
        ]);

        return response()->json(['image_base64' => $response->json('predictions.0.bytesBase64Encoded')]);
    }

    /**
     * Magic Button: Edit Product Image (Wallpaper Room Preview / Generative Fill)
     */
    public function editImage(Request $request)
    {
        $payload = $request->validate([
            'prompt' => 'required|string|max:1000',
            'baseImageBase64' => 'required|string',
            'maskImageBase64' => 'nullable|string',
        ]);

        return response()->json($this->aiService->editImage($payload));
    }
}
