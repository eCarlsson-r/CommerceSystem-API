<?php

namespace App\Http\Controllers;

use App\Services\LaravelAiKitService;
use App\Events\AIOperationCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

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

        $startTime = microtime(true);
        try {
            $result = $this->aiService->recommendations($payload);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            
            // Dispatch KPI event
            Event::dispatch(new AIOperationCompleted(
                operation: 'recommendations',
                latencyMs: $latencyMs,
                cost: 0.02, // Estimated cost
                success: true,
                context: [
                    'product_id' => $payload['productId'] ?? null,
                    'results_count' => count($result['data'] ?? []),
                    'session_id' => $payload['sessionId'] ?? null,
                    'customer_id' => $payload['customerId'] ?? null,
                ],
            ));

            return response()->json($result);
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            Event::dispatch(new AIOperationCompleted(
                operation: 'recommendations',
                latencyMs: $latencyMs,
                cost: 0.02,
                success: false,
                context: ['error_type' => get_class($e)],
                error: $e,
            ));
            throw $e;
        }
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

        $startTime = microtime(true);
        try {
            $result = $this->aiService->visualSearch($payload);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            
            // Dispatch KPI event
            Event::dispatch(new AIOperationCompleted(
                operation: 'visual-search',
                latencyMs: $latencyMs,
                cost: 0.05, // Estimated cost
                success: true,
                context: [
                    'results_count' => count($result['data'] ?? []),
                    'session_id' => $payload['sessionId'] ?? null,
                    'customer_id' => $payload['customerId'] ?? null,
                ],
            ));

            return response()->json($result);
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            Event::dispatch(new AIOperationCompleted(
                operation: 'visual-search',
                latencyMs: $latencyMs,
                cost: 0.05,
                success: false,
                context: ['error_type' => get_class($e)],
                error: $e,
            ));
            throw $e;
        }
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

        $startTime = microtime(true);
        try {
            $result = $this->aiService->assistant($payload);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            
            // Dispatch KPI event
            Event::dispatch(new AIOperationCompleted(
                operation: 'assistant',
                latencyMs: $latencyMs,
                cost: 0.03, // Estimated cost
                success: true,
                context: [
                    'has_response' => !empty($result['data']['response']),
                    'session_id' => $payload['sessionId'] ?? null,
                    'customer_id' => $payload['customerId'] ?? null,
                ],
            ));

            return response()->json($result);
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            Event::dispatch(new AIOperationCompleted(
                operation: 'assistant',
                latencyMs: $latencyMs,
                cost: 0.03,
                success: false,
                context: ['error_type' => get_class($e)],
                error: $e,
            ));
            throw $e;
        }
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

        $startTime = microtime(true);
        try {
            $result = $this->aiService->translateDraft($payload);
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            
            // Dispatch KPI event
            Event::dispatch(new AIOperationCompleted(
                operation: 'translate',
                latencyMs: $latencyMs,
                cost: 0.01, // Estimated cost
                success: true,
                context: [
                    'source_locale' => $payload['sourceLocale'],
                    'target_locale' => $payload['targetLocale'],
                    'session_id' => $payload['sessionId'] ?? null,
                    'customer_id' => $payload['customerId'] ?? null,
                ],
            ));

            return response()->json($result);
        } catch (\Exception $e) {
            $latencyMs = (int)((microtime(true) - $startTime) * 1000);
            Event::dispatch(new AIOperationCompleted(
                operation: 'translate',
                latencyMs: $latencyMs,
                cost: 0.01,
                success: false,
                context: ['error_type' => get_class($e)],
                error: $e,
            ));
            throw $e;
        }
    }
}
