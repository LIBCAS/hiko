<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    private static string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    /**
     * Process a handwritten letter image using Google Gemini API.
     *
     * @param string $imagePath
     * @param string $language
     * @return array
     * @throws Exception
     */
    public static function processHandwrittenLetter(string $imagePath, string $language = 'en'): array
    {
        try {
            // Load API key
            $apiKey = config('services.gemini.api_key');
            if (!$apiKey) {
                throw new Exception("Gemini API key is not configured.");
            }
    
            // Ensure the path is correct
            if (!str_starts_with($imagePath, storage_path())) {
                $absolutePath = storage_path("blekastad/app/{$imagePath}");
            } else {
                $absolutePath = $imagePath;
            }
    
            // Validate file existence
            if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
                throw new Exception("File does not exist or is not readable at path: {$absolutePath}");
            }
    
            // Read and encode the file content
            $fileContent = file_get_contents($absolutePath);
            if (!$fileContent) {
                throw new Exception("File content is empty.");
            }
            $fileContentBase64 = base64_encode($fileContent);
    
            // Dynamically determine mime type
            $mimeType = mime_content_type($absolutePath);
    
            // Build the prompt
            $prompt = "You are an expert in analyzing handwritten documents. Analyze the following HANDWRITTEN letter, which has been provided as an image. Your task is to:\n"
                . "1. Perform OCR on the image to recognize the text.\n"
                . "2. Identify the language(s) used in the letter.\n"
                . "3. Extract metadata (author, date, addressee) if available.\n"
                . "4. Return only a valid JSON object with the keys: 'language', 'summary', 'metadata', and 'full_text'.";
    
            // Prepare the API request payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $fileContentBase64
                            ]]
                        ]
                    ]
                ]
            ];
    
            // Send HTTP request to Google Gemini API
            $client = new Client();
            $response = $client->post(self::$endpoint . "?key={$apiKey}", [
                'json' => $payload,
                'headers' => ['Content-Type' => 'application/json'],
            ]);
    
            // Parse the API response
            $responseBody = json_decode($response->getBody()->getContents(), true);
            $generatedText = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
            if (!$generatedText) {
                throw new Exception("No valid response from Google Gemini API.");
            }
    
            // Clean and decode the response JSON
            $cleanedText = preg_replace('/^```json\s*|```\s*$/', '', $generatedText);
            $decodedResponse = json_decode($cleanedText, true);
    
            if (!$decodedResponse) {
                throw new Exception("Failed to decode JSON response: {$generatedText}");
            }
    
            return $decodedResponse;
    
        } catch (Exception $e) {
            throw new Exception("Error processing handwritten letter: " . $e->getMessage());
        }
    }    
}
