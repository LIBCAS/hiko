<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    private static string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    /**
     * Process a handwritten letter and extract metadata.
     *
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public static function processHandwrittenLetter(string $filePath): array
    {
        try {
            $apiKey = config('services.gemini.api_key');

            if (!$apiKey) {
                throw new Exception("API key not configured.");
            }

            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception("File does not exist or is unreadable: {$filePath}");
            }

            $fileContent = base64_encode(file_get_contents($filePath));
            $mimeType = mime_content_type($filePath);

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => self::buildPrompt()],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data'      => $fileContent
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            // Send request
            $response = (new Client())->post(
                self::$endpoint . "?key={$apiKey}",
                [
                    'json'    => $payload,
                    'headers' => ['Content-Type' => 'application/json']
                ]
            );

            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('OCR API Response', ['response' => $responseBody]);

            // Extract text
            $responseText = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return self::parseApiResponse($responseText);

        } catch (Exception $e) {
            Log::error("OCR Processing Error: " . $e->getMessage());
            // Optionally inform the user that the service is temporarily unavailable
            throw new Exception("Error processing letter: " . $e->getMessage());
        }
    }

    /**
     * Build prompt for Gemini API.
     *
     * @return string
     */
    private static function buildPrompt(): string
    {
        return "You are analyzing a letter that can be handwritten. "
            . "Return the recognized text as 'recognized_text' in JSON. "
            . "Then extract the following metadata in JSON format:\n"
            . "- date_year, date_month, date_day, date_marked, date_uncertain, date_approximate, date_inferred, date_is_range\n"
            . "- range_year, range_month, range_day, date_note\n"
            . "- author_inferred, author_uncertain, author_note\n"
            . "- recipient_inferred, recipient_uncertain, recipient_note\n"
            . "- origin_inferred, origin_uncertain, origin_note\n"
            . "- destination_inferred, destination_uncertain, destination_note\n"
            . "- languages[], keywords[], abstract_cs, abstract_en, incipit, explicit, mentioned[], people_mentioned_note\n"
            . "- notes_private, notes_public, copyright, status\n"
            . "Ensure all fields are returned with valid JSON keys. If a field is unknown, return an empty string or empty array.";
    }

    /**
     * Parse and clean the API response.
     *
     * @param string $response
     * @return array
     * @throws Exception
     */
    private static function parseApiResponse(string $response): array
    {
        // Remove code blocks ```json ... ```
        $cleaned = preg_replace('/^```json\\s*|```\\s*$/', '', $response);
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse API response.");
        }

        // Trim recognized_text
        if (isset($decoded['recognized_text'])) {
            $decoded['recognized_text'] = trim($decoded['recognized_text']);
        }

        // Concatenate incipit + explicit => full_text
        if (!empty($decoded['incipit']) || !empty($decoded['explicit'])) {
            $decoded['full_text'] = trim(
                ($decoded['incipit'] ?? '') . "\n" . ($decoded['explicit'] ?? '')
            );
        }

        return $decoded;
    }
}
