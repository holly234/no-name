<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiVoiceTranscriptionService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private readonly HttpFactory $http) {}

    public function transcribe(Message $message): ?string
    {
        $message->loadMissing('attachments');
        $attachment = $message->attachments->first(fn ($item) =>
            str_starts_with((string) $item->mime_type, 'audio/')
            || ($item->metadata['media_type'] ?? null) === 'voice'
        );

        if (! $attachment) {
            return null;
        }

        $key = trim((string) config('ai.providers.gemini.api_key'));
        $model = trim((string) config('ai.providers.gemini.model'));
        $contents = Storage::disk($attachment->disk)->get($attachment->storage_path);

        if ($key === '' || $model === '' || strlen($contents) > 10 * 1024 * 1024) {
            return null;
        }

        $response = $this->http
            ->withHeaders(['x-goog-api-key' => $key])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout((int) config('ai.providers.gemini.timeout', 30))
            ->retry(2, 400, throw: false)
            ->post(self::API_BASE.'/'.rawurlencode($model).':generateContent', [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Transcribe this customer voice note accurately. Return only the spoken words, with no commentary.'],
                        ['inlineData' => [
                            'mimeType' => $attachment->mime_type ?: 'audio/ogg',
                            'data' => base64_encode($contents),
                        ]],
                    ],
                ]],
                'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 500],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Voice transcription failed with HTTP '.$response->status().'.');
        }

        $transcription = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));

        return $transcription !== '' ? $transcription : null;
    }
}
