<?php
// app/Services/GeminiService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class GeminiService
{
    protected string $geminiKey;
    protected string $geminiEndpoint;

    public function __construct()
    {
        $this->geminiKey = config('custom.gemini_api_key');
        $this->geminiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    public function extractSchoolDetails(string $schoolName, string $mainWebsite): array
    {
        // 1. Scrape homepage content
        $mainContent = $this->scrapeWebsiteText($mainWebsite);

        // 2. Search related pages
        $relatedContents = $this->searchAndScrapeGoogleResults($schoolName);

        $combinedContent = substr($mainContent . "\n\n" . implode("\n\n", $relatedContents), 0, 12000); // limit Gemini token budget

        // 3. Prompt
        $prompt = <<<EOT
From the following context about "{$schoolName}", extract this information strictly as a valid JSON object:
{
  "no_of_students": [""],
  "type_of_school": [""],
  "education_philosophy": [""],
  "curriculum_models": [""],
  "facilities": [""],
  "age_groups": [""],
  "teacher_student_ratio": [""],
  "fees": [""],
  "theme": "",
  "other_information": ""
}
Leave fields empty if not found.
Do not include any explanation or commentary — output only valid JSON.

Content:
{$combinedContent}
EOT;

        $response = Http::post("{$this->geminiEndpoint}?key={$this->geminiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
        ]);

        $text = $response->json('candidates.0.content.parts.0.text') ?? '';

        Log::info('Gemini RAW OUTPUT:', [$text]);

        if (empty($text)) {
            throw new \Exception('Gemini returned no content: ' . json_encode($response->json()));
        }

        // Clean output: remove markdown blocks or text before JSON
        $cleaned = trim($text);

        // Remove triple backticks if present
        $cleaned = preg_replace('/^```json|```$/m', '', $cleaned);

        // Remove anything before first { or [
        $firstJsonChar = strpos($cleaned, '{');
        if ($firstJsonChar !== false) {
            $cleaned = substr($cleaned, $firstJsonChar);
        }

        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::error("Gemini JSON parse failed", ['text' => $cleaned]);
            throw new \Exception('Gemini JSON parse error: ' . json_last_error_msg());
        }

        return $data;
    }

    protected function scrapeWebsiteText(string $url): string
    {
        try {
            $html = Http::timeout(10)->get($url)->body();

            $crawler = new Crawler($html);

            return $crawler->filter('body')->text();
        } catch (\Exception $e) {
            Log::warning("Failed to scrape $url: " . $e->getMessage());
            return '';
        }
    }

    protected function searchAndScrapeGoogleResults(string $schoolName): array
    {
        $googleKey = config('custom.google_key');
        $searchEngineId = config('custom.search_engine_id');

        $results = Http::get('https://www.googleapis.com/customsearch/v1?', [
            'key' => $googleKey,
            'cx' => $searchEngineId,
            // 'q' => "$schoolName site:.edu OR site:.org OR site:.my OR site:.com",
            'q' => "$schoolName",
            'num' => 3,
        ])->json('items') ?? [];
        Log::info('search => ');
        Log::info($results);

        $texts = [];

        foreach ($results as $result) {
            $link = $result['link'] ?? null;
            if ($link) {
                $texts[] = $this->scrapeWebsiteText($link);
            }
        }

        return $texts;
    }
}
