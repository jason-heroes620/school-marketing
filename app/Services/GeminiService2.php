<?php
// app/Services/GeminiService.php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService2
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('custom.gemini_api_key');
        // $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        $this->endpoint = "https://aiplatform.googleapis.com/v1/publishers/google/models/gemini-2.5-flash:generateContent";
    }

    public function ask(string $query): ?string
    {
        try {
            $response = Http::timeout(600)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->endpoint}?key={$this->apiKey}", [
                'contents' => [
                    [
                        "role" => "user",
                        'parts' => [
                            ['text' => $query]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {

                return $response->json('candidates.0.content.parts.0.text');
            }

            logger()->error('Gemini API failed', ['response' => $response->body()]);
            return null;
        } catch (Exception $e) {
            Log::error('Error running gemini request.');
            Log::error($e);
            return 'error';
        }
    }
}
