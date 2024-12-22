<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    // Gemini 2.0 Flash endpoint
    private static string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * Process a handwritten letter and extract metadata using Gemini 2.0 Flash.
     *
     * @param string $filePath
     * @return array
     * @throws Exception
     */
    public static function processHandwrittenLetter(string $filePath): array
    {
        try {
            // Retrieve the API key from configuration
            $apiKey = config('services.gemini.api_key');

            if (!$apiKey) {
                throw new Exception("API key not configured.");
            }

            // Validate the file
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception("File does not exist or is unreadable: {$filePath}");
            }

            // Encode the file content in Base64
            $fileContent = base64_encode(file_get_contents($filePath));
            $mimeType = mime_content_type($filePath);

            // Construct the payload without unsupported fields like 'parameters'
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
                ],
            ];

            // Send the POST request to Gemini 2.0 Flash API
            $response = (new Client())->post(
                self::$endpoint . "?key={$apiKey}",
                [
                    'json'    => $payload,
                    'headers' => ['Content-Type' => 'application/json']
                ]
            );

            // Decode the JSON response
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log the API response for debugging
            Log::info('Gemini 2.0 Flash OCR API Response', ['response' => $responseBody]);

            // Extract the generated text from the response
            $responseText = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Parse and process the response text
            return self::parseApiResponse($responseText);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle client exceptions (e.g., 4xx errors)
            $response = $e->getResponse();
            $responseBody = $response ? $response->getBody()->getContents() : 'No response body';
            Log::error("Gemini 2.0 Flash OCR Client Error: " . $e->getMessage(), ['response' => $responseBody]);
            throw new Exception("Error processing letter: " . $e->getMessage());
        } catch (Exception $e) {
            // Handle general exceptions
            Log::error("Gemini 2.0 Flash OCR Processing Error: " . $e->getMessage());
            throw new Exception("Error processing letter: " . $e->getMessage());
        }
    }

    /**
     * Build prompt for Gemini 2.0 Flash API with enhanced instructions for numeral systems.
     *
     * @return string
     */
    private static function buildPrompt(): string
    {
        return "You are analyzing a handwritten letter that may be written in any language. "
            . "The letter may contain both text and numerical data, including Arabic numerals (0-9) and Roman numerals (I, II, III, etc.). "
            . "When encountering Roman numerals, ensure they are preserved accurately and not converted to Arabic numerals, and vice versa. "
            . "Extract the recognized text and detailed metadata in JSON format with all field names in English. "
            . "Return the recognized text under 'recognized_text' and the metadata under 'metadata'. "
            . "Ensure that dates and numerical information correctly reflect the numeral systems used in the original document. "
            . "The JSON structure should follow this example:\n"
            . "```\n"
            . "{\n"
            . "  \"recognized_text\": \"Luna V/5/1967 a fost o zi importantă.\",\n"
            . "  \"metadata\": {\n"
            . "    \"date_year\": \"1967\",\n"
            . "    \"date_month\": \"5\",\n"
            . "    \"date_day\": \"V\",\n"
            . "    \"date_marked\": \"Yes\",\n"
            . "    \"date_uncertain\": \"No\",\n"
            . "    \"date_approximate\": \"No\",\n"
            . "    \"date_inferred\": \"No\",\n"
            . "    \"date_is_range\": \"No\",\n"
            . "    \"range_year\": \"\",\n"
            . "    \"range_month\": \"\",\n"
            . "    \"range_day\": \"\",\n"
            . "    \"date_note\": \"\",\n"
            . "    \"author_inferred\": \"No\",\n"
            . "    \"author_uncertain\": \"No\",\n"
            . "    \"author_note\": \"\",\n"
            . "    \"recipient_inferred\": \"No\",\n"
            . "    \"recipient_uncertain\": \"No\",\n"
            . "    \"recipient_note\": \"\",\n"
            . "    \"origin_inferred\": \"No\",\n"
            . "    \"origin_uncertain\": \"No\",\n"
            . "    \"origin_note\": \"\",\n"
            . "    \"destination_inferred\": \"No\",\n"
            . "    \"destination_uncertain\": \"No\",\n"
            . "    \"destination_note\": \"\",\n"
            . "    \"languages\": [\"Romanian\"],\n"
            . "    \"keywords\": [\"Important\", \"Letter\"],\n"
            . "    \"abstract_cs\": \"\",\n"
            . "    \"abstract_en\": \"\",\n"
            . "    \"incipit\": \"\",\n"
            . "    \"explicit\": \"\",\n"
            . "    \"mentioned\": [\"\"],\n"
            . "    \"people_mentioned_note\": \"\",\n"
            . "    \"notes_private\": \"\",\n"
            . "    \"notes_public\": \"\",\n"
            . "    \"copyright\": \"\",\n"
            . "    \"status\": \"\"\n"
            . "  }\n"
            . "}\n"
            . "```\n"
            . "Include the following metadata fields in English:\n"
            . "- date_year, date_month, date_day, date_marked, date_uncertain, date_approximate, date_inferred, date_is_range\n"
            . "- range_year, range_month, range_day, date_note\n"
            . "- author_inferred, author_uncertain, author_note\n"
            . "- recipient_inferred, recipient_uncertain, recipient_note\n"
            . "- origin_inferred, origin_uncertain, origin_note\n"
            . "- destination_inferred, destination_uncertain, destination_note\n"
            . "- languages (array), keywords (array), abstract_cs, abstract_en, incipit, explicit, mentioned (array), people_mentioned_note\n"
            . "- notes_private, notes_public, copyright, status\n"
            . "Ensure all fields are present. If a field is unknown, assign it an empty string or empty array.";
    }

    /**
     * Parse and clean the Gemini 2.0 Flash API response.
     *
     * @param string $response
     * @return array
     * @throws Exception
     */
    private static function parseApiResponse(string $response): array
    {
        // Remove any code block formatting if present
        $cleaned = preg_replace('/^```json\s*|```\s*$/', '', $response);
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON Decode Error: " . json_last_error_msg());
            throw new Exception("Failed to parse Gemini 2.0 Flash API response.");
        }

        // Log the raw decoded response for debugging
        Log::debug('Decoded Gemini 2.0 Flash API Response', ['decoded_response' => $decoded]);

        // Trim recognized_text
        if (isset($decoded['recognized_text'])) {
            $decoded['recognized_text'] = trim($decoded['recognized_text']);
        }

        // Add language information to metadata based on 'languages' array
        if (isset($decoded['metadata']['languages']) && is_array($decoded['metadata']['languages'])) {
            $decoded['metadata']['language_detected'] = implode(', ', $decoded['metadata']['languages']);
        } else {
            $decoded['metadata']['language_detected'] = 'unknown';
        }

        // Concatenate incipit + explicit => full_text
        if (!empty($decoded['incipit']) || !empty($decoded['explicit'])) {
            $decoded['full_text'] = trim(
                ($decoded['incipit'] ?? '') . "\n" . ($decoded['explicit'] ?? '')
            );
        }

        // Post-processing corrections
        if (isset($decoded['recognized_text'])) {
            $decoded['recognized_text'] = self::correctMisrecognitions($decoded['recognized_text']);
            $decoded['recognized_text'] = self::correctDateMisinterpretations($decoded['recognized_text']);
        }

        return $decoded;
    }

    /**
     * Correct common misrecognitions in the recognized text.
     *
     * @param string $ocrText
     * @return string
     */
    public static function correctMisrecognitions(string $ocrText): string
    {
        // Define common misrecognitions (e.g., '1' -> 'I', '5' -> 'V', etc.)
        $corrections = [
            '/\b1\b/' => 'I',
            '/\b2\b/' => 'II',
            '/\b3\b/' => 'III',
            '/\b4\b/' => 'IV',
            '/\b5\b/' => 'V',
            '/\b6\b/' => 'VI',
            '/\b7\b/' => 'VII',
            '/\b8\b/' => 'VIII',
            '/\b9\b/' => 'IX',
            '/\b10\b/' => 'X',
            '/\b50\b/' => 'L',
            '/\b100\b/' => 'C',
            '/\b500\b/' => 'D',
            '/\b1000\b/' => 'M',
        ];

        foreach ($corrections as $pattern => $replacement) {
            $ocrText = preg_replace($pattern, $replacement, $ocrText);
        }

        return $ocrText;
    }

    /**
     * Correct misinterpretations in date formats.
     *
     * @param string $ocrText
     * @return string
     */
    public static function correctDateMisinterpretations(string $ocrText): string
    {
        // Pattern: Find dates with possible misinterpretations in the month part (Roman vs Arabic)
        $pattern = '/(\d{1,2})\/([IVX]+|\d{1,2})\/(\d{4})/';
        $ocrText = preg_replace_callback($pattern, function ($matches) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Check if month is numeric and between 1-12
            if (is_numeric($month) && $month >= 1 && $month <= 12) {
                return "{$day}/{$month}/{$year}";
            }

            // If month is a Roman numeral, ensure it's valid
            $romanMonths = [
                'I' => 'I', 'II' => 'II', 'III' => 'III', 'IV' => 'IV',
                'V' => 'V', 'VI' => 'VI', 'VII' => 'VII', 'VIII' => 'VIII',
                'IX' => 'IX', 'X' => 'X', 'XI' => 'XI', 'XII' => 'XII'
            ];

            return "{$day}/" . ($romanMonths[$month] ?? $month) . "/{$year}";
        }, $ocrText);

        return $ocrText;
    }
}
