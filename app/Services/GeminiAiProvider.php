<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Data\AiGeneration;
use App\Data\AiPrompt;
use App\Exceptions\AiProviderNotConfigured;
use App\Models\Conversation;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use RuntimeException;

class GeminiAiProvider implements AiProvider
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private readonly HttpFactory $http) {}

    public function generate(AiPrompt $prompt): AiGeneration
    {
        $key = trim((string) config('ai.providers.gemini.api_key'));
        $model = trim((string) config('ai.providers.gemini.model'));

        if ($key === '' || $model === '') {
            throw new AiProviderNotConfigured('Gemini is not configured.');
        }

        $startedAt = hrtime(true);
        $response = $this->http
            ->withHeaders(['x-goog-api-key' => $key])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout((int) config('ai.providers.gemini.timeout', 30))
            ->retry(2, 400, throw: false)
            ->post(self::API_BASE.'/'.rawurlencode($model).':generateContent', [
                'systemInstruction' => [
                    'parts' => [['text' => $prompt->system]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt->message]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 500,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'required' => ['reply', 'state', 'confidence', 'requires_human'],
                        'properties' => [
                            'reply' => ['type' => 'STRING'],
                            'state' => ['type' => 'STRING', 'enum' => Conversation::STATES],
                            'confidence' => ['type' => 'NUMBER'],
                            'requires_human' => ['type' => 'BOOLEAN'],
                            'reason' => ['type' => 'STRING', 'nullable' => true],
                            'intent' => ['type' => 'STRING', 'nullable' => true],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $providerMessage = trim((string) data_get($response->json(), 'error.message'));
            $detail = $providerMessage !== '' ? ': '.$providerMessage : '.';

            throw new RuntimeException('Gemini request failed with HTTP '.$response->status().$detail);
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $decision = json_decode($text, true);

        if (! is_array($decision)) {
            throw new RuntimeException('Gemini returned an invalid structured response.');
        }

        $inputTokens = (int) data_get($response->json(), 'usageMetadata.promptTokenCount', 0);
        $outputTokens = (int) data_get($response->json(), 'usageMetadata.candidatesTokenCount', 0);
        $requiresHuman = (bool) Arr::get($decision, 'requires_human', false);
        $state = (string) Arr::get($decision, 'state', Conversation::STATE_NEEDS_HUMAN);

        if (! in_array($state, Conversation::STATES, true) || $requiresHuman) {
            $state = Conversation::STATE_NEEDS_HUMAN;
        }

        $estimatedPaidCost = $this->estimatedPaidCost($inputTokens, $outputTokens);

        return new AiGeneration(
            reply: trim((string) Arr::get($decision, 'reply', '')),
            state: $state,
            confidence: max(0, min(1, (float) Arr::get($decision, 'confidence', 0))),
            requiresHuman: $requiresHuman,
            reason: Arr::get($decision, 'reason'),
            intent: Arr::get($decision, 'intent'),
            provider: 'gemini',
            model: $model,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            providerCostUsd: config('ai.providers.gemini.billing_mode') === 'paid' ? $estimatedPaidCost : 0,
            latencyMs: (int) round((hrtime(true) - $startedAt) / 1_000_000),
            metadata: [
                'finish_reason' => data_get($response->json(), 'candidates.0.finishReason'),
                'estimated_paid_cost_usd' => $estimatedPaidCost,
                'billing_mode' => config('ai.providers.gemini.billing_mode'),
            ],
        );
    }

    private function estimatedPaidCost(int $inputTokens, int $outputTokens): float
    {
        $input = $inputTokens / 1_000_000 * (float) config('ai.providers.gemini.input_cost_per_million_usd');
        $output = $outputTokens / 1_000_000 * (float) config('ai.providers.gemini.output_cost_per_million_usd');

        return round($input + $output, 8);
    }
}
