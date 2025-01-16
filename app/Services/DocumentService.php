<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    // Gemini 2.0 Flash endpoint
    private static string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * Processes a document (handwritten or other) and extracts data using Gemini 2.0 Flash API.
     *
     * @param string $filePath The path to the document file.
     * @return array An array containing 'recognized_text' and 'metadata'.
     * @throws Exception If there is a problem with the API or file processing.
     */
    public static function processDocument(string $filePath): array
    {
        try {
            // 1. Validate API Key
            $apiKey = config('services.gemini.api_key');
            if (!$apiKey) {
                throw new Exception("API key not configured in services.gemini.api_key");
            }

            // 2. Validate File
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception("File does not exist or is unreadable: {$filePath}");
            }

            // 3. Encode File Content
            $fileContent = base64_encode(file_get_contents($filePath));
            $mimeType = mime_content_type($filePath);

            // 4. Construct API Payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => self::buildPrompt()],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $fileContent,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // 5. Send API Request
            $client = new Client();
            $response = $client->post(
                self::$endpoint . "?key={$apiKey}",
                [
                    'json' => $payload,
                    'headers' => ['Content-Type' => 'application/json'],
                ]
            );

            // 6. Decode API Response
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // 7. Log API Response
            Log::info('Gemini 2.0 Flash OCR API Response', ['response' => $responseBody]);

            // 8. Extract Response Text
            $responseText = Arr::get($responseBody, 'candidates.0.content.parts.0.text', '');

            // 9. Parse and Process Response
            // Return the parsed data
            return self::parseApiResponse($responseText);

        } catch (ClientException $e) {
            // Handle Guzzle Client Exceptions (e.g., 4xx errors)
            $response = $e->getResponse();
            $responseBody = $response ? $response->getBody()->getContents() : 'No response body';
            Log::error("Gemini 2.0 Flash OCR Client Error: " . $e->getMessage(), ['response' => $responseBody]);
            throw new Exception("Error processing document: " . $e->getMessage());
        } catch (Exception $e) {
            // Handle General Exceptions
            Log::error("Gemini 2.0 Flash OCR Processing Error: " . $e->getMessage());
            throw new Exception("Error processing document: " . $e->getMessage());
        }
    }

    /**
     * Builds the prompt for the Gemini 2.0 Flash API, including extensive instructions for accurate OCR
     * and structured metadata extraction.
     *
     * @return string The constructed prompt.
     */
    private static function buildPrompt(): string
    {
        $prompt = "You are an advanced, universal document processing AI. Your task is to analyze scanned documents of any type, language, and format, including handwritten text, and provide accurate text transcription and detailed metadata extraction. You must adhere strictly to all the rules described below.\n\n"
            . "Task 1: Superior Text Recognition, Multilingual Handling, and Contextual Error Correction\n"
            . "  - **Language Identification:** Identify *all* languages present, even mixed within a line. Return as an array of ISO 639-1 codes under 'languages'. If no language is detected, return an empty array [].\n"
            . "  - **Advanced Text Recognition:** Use advanced OCR techniques, handling diacritics, ligatures, special characters, and handwritten text with high precision.\n"
            . "  - **Contextual Analysis and Correction:** Analyze the full document context to correct all errors (grammar, spelling, punctuation, semantic), producing a perfect text version, preserving the original language style and tone without introducing changes.\n"
            . "  - **Handling Imperfections:**  Address any scan imperfections (blur, skew, noise, faintness, damage) using the document's overall context, patterns, and logical flow. Do not add or remove words/characters or spaces unless they are present in the original document.\n"
            . "  - **Numeral System Handling:** Preserve all numerals (Arabic, Roman, etc.) exactly as in the original without conversions or altering spacing. Treat numbers as references, dates, or any other numeric value.\n"
            . "  - **Polished Output:** Provide a polished and error-free transcription, without adding or removing characters or spaces that were not in the original document.\n"
            . "  - **Output Key:** Return the completely transcribed, contextually corrected, and polished text under the key 'recognized_text'.\n\n"
            . "Task 2: Comprehensive Metadata Extraction and Validation\n"
            . "  - **Metadata Extraction:** Extract the following metadata fields. If a field is absent or cannot be reliably determined, set it to an empty string ('') or empty array ([]), depending on the field's data type. *All* fields must be in the final JSON, and the JSON must be valid.\n"
            . "  - **Boolean Fields:** Return `true` or `false`. If not stated or cannot be inferred, set to `false`.\n"
        . "  - **Date Representation (`date_marked`):** The original string representation of the date as it appears in the document, with its original formatting and order of elements (day, month, year) and any symbols. Preserve the formatting exactly. Extract the year from any position if available. If not available, use an empty string. \n"
            . " - **Numeric Date Fields:** Extract the year, month, and day values to 'date_year', 'date_month', 'date_day', 'range_year', 'range_month', 'range_day' as strings with numeric format. If a component is missing, set the corresponding field to an empty string (''). Convert Roman numeral months to Arabic (e.g., II to 2).\n"
        . "  - **Year Inference:** If a year is represented with only two digits, infer the correct century. If not possible, use the last *four* digits of the current year. If only one digit is present, use that digit as a string.\n"
        . "  - **Arrays:** 'keywords' and 'mentioned' must be arrays of strings, and empty arrays [] if no values are found.\n"
            . "  - **Incipit and Explicit:** The first and last *complete and meaningful* sentences of the document, without modifications, extra or missing spaces. If absent, return an empty string.\n"
            . "  - **Text Fields:** Return other text-based metadata fields as strings, or an empty string if not found.\n"
            . "  - **Notes Fields:** 'date_note', 'author_note', 'recipient_note', 'origin_note', 'destination_note', and 'people_mentioned_note' are notes associated with the respective fields. Use empty strings '' if not found.\n"
            . "  - **Default Boolean Values:** Fields like 'date_uncertain', 'author_inferred' must be `false` if not stated or inferred.\n"
            . "  - **Output Structure:** The output should be a valid JSON object with keys 'recognized_text' and 'metadata'. The JSON *must* be valid, include *all* the keys, and adhere strictly to *all* instructions.\n"
            . "   Metadata Fields (Output exactly as shown):\n"
            . "   {\n"
            . "     \"date_year\": string,  \n"
            . "     \"date_month\": string,  \n"
            . "     \"date_day\": string,  \n"
            . "     \"date_marked\": string, \n"
            . "     \"date_uncertain\": boolean, \n"
            . "     \"date_approximate\": boolean, \n"
            . "     \"date_inferred\": boolean, \n"
            . "     \"date_is_range\": boolean, \n"
            . "     \"range_year\": string, \n"
        . "     \"range_month\": string, \n"
            . "     \"range_day\": string, \n"
            . "     \"date_note\": string, \n"
            . "     \"author_inferred\": boolean, \n"
            . "     \"author_uncertain\": boolean, \n"
            . "     \"author_note\": string, \n"
            . "     \"recipient_inferred\": boolean, \n"
            . "     \"recipient_uncertain\": boolean,  \n"
            . "     \"recipient_note\": string, \n"
            . "     \"origin_inferred\": boolean, \n"
            . "     \"origin_uncertain\": boolean, \n"
            . "     \"origin_note\": string, \n"
            . "     \"destination_inferred\": boolean, \n"
            . "     \"destination_uncertain\": boolean, \n"
            . "     \"destination_note\": string, \n"
            . "     \"languages\": array,  \n"
            . "     \"keywords\": array, \n"
            . "     \"abstract_cs\": string, \n"
            . "     \"abstract_en\": string, \n"
            . "     \"incipit\": string, \n"
            . "     \"explicit\": string, \n"
        . "     \"mentioned\": array, \n"
            . "     \"people_mentioned_note\": string,\n"
            . "     \"notes_private\": string, \n"
            . "     \"notes_public\": string, \n"
        . "     \"copyright\": string, \n"
        . "     \"status\": string, \n"
        . "     \"full_text_translation\": string \n"
            . "   }\n\n"
            . "  Output should be a valid JSON object with keys 'recognized_text' and 'metadata'. The JSON *must* be valid, include *all* the keys, and adhere strictly to *all* instructions.";

        return $prompt;
    }

    /**
     * Parses the Gemini 2.0 Flash API response, cleans it, and prepares it for further use.
     *
     * @param string $response The raw API response text.
     * @return array The parsed response including 'recognized_text' and 'metadata'.
     * @throws Exception If JSON decoding fails or if essential data is missing.
     */
    private static function parseApiResponse(string $response): array
    {
        // 1. Remove JSON code block if present
        $cleaned = preg_replace('/^```json\s*|```\s*$/', '', $response);

        // 2. Decode JSON Response
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON Decode Error: " . json_last_error_msg() . ". Raw response: " . $response);
        throw new Exception("Failed to parse Gemini 2.0 Flash API response.");
        }

        // 3. Log Decoded Response
        Log::debug('Decoded Gemini 2.0 Flash API Response', ['decoded_response' => $decoded]);


        // 4. Initialize all metadata fields to ensure completeness
        $metadataFields = [
            'date_year', 'date_month', 'date_day', 'date_marked', 'date_uncertain',
            'date_approximate', 'date_inferred', 'date_is_range', 'range_year',
            'range_month', 'range_day', 'date_note', 'author_inferred',
            'author_uncertain', 'author_note', 'recipient_inferred',
            'recipient_uncertain', 'recipient_note', 'origin_inferred',
            'origin_uncertain', 'origin_note', 'destination_inferred',
            'destination_uncertain', 'destination_note', 'languages',
            'keywords', 'abstract_cs', 'abstract_en', 'incipit', 'explicit',
            'mentioned', 'people_mentioned_note', 'notes_private',
        'notes_public', 'copyright', 'status', 'full_text_translation'
        ];


        // 5. Ensure Metadata Array Exists
        if (!isset($decoded['metadata'])) {
            $decoded['metadata'] = [];
        }

        // 6. Populate missing Metadata fields with empty values
        foreach ($metadataFields as $field) {
            if (!array_key_exists($field, $decoded['metadata'])) {
                $decoded['metadata'][$field] = is_array($decoded['metadata'][$field] ?? '') ? [] : '';
        }
        }

        // 7. Trim 'recognized_text'
    if (isset($decoded['recognized_text'])) {
            $decoded['recognized_text'] = trim($decoded['recognized_text']);
        }

        // 8. Add language information from 'languages' array
        if (isset($decoded['metadata']['languages']) && is_array($decoded['metadata']['languages'])) {
            $decoded['metadata']['language_detected'] = implode(', ', $decoded['metadata']['languages']);
        } else {
            $decoded['metadata']['language_detected'] = 'unknown';
        }

        // 9. Concatenate incipit + explicit into 'full_text'
        if (!empty($decoded['incipit']) || !empty($decoded['explicit'])) {
            $decoded['metadata']['full_text'] = trim(
            ($decoded['incipit'] ?? '') . "\n" . ($decoded['explicit'] ?? '')
            );
        }

        // 10. Post-processing Corrections and Validations
        if (isset($decoded['recognized_text'])) {
        $decoded['recognized_text'] = self::correctMisrecognitions($decoded['recognized_text']);
        $decoded['recognized_text'] = self::correctDateMisinterpretations($decoded['recognized_text']);
            $decoded['recognized_text'] = self::validateMetadata($decoded['metadata'], $decoded['recognized_text']);
        }

        return $decoded;
    }


    /**
     * Corrects common OCR misrecognitions, like Roman numerals and number/letter confusions,
     * within the recognized text.
     *
     * @param string $ocrText The text to correct.
     * @return string The corrected text.
     */
    public static function correctMisrecognitions(string $ocrText): string
    {
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
            '/\b0\b/' => 'O',
            '/\b57I\b/' => 'VII',
            '/\b22\/I\b/' => '22/I',
            '/\b22\/II\b/' => '22/II',
        '/\b22\/III\b/' => '22/III',
        '/\b22\/IV\b/' => '22/IV',
            '/\b22\/V\b/' => '22/V',
            '/\b22\/VI\b/' => '22/VI',
        '/\b22\/VII\b/' => '22/VII',
            '/\b22\/VIII\b/' => '22/VIII',
        '/\b22\/IX\b/' => '22/IX',
            '/\b22\/X\b/' => '22/X',
        '/\b22\/XI\b/' => '22/XI',
        '/\b22\/XII\b/' => '22/XII',
        ];

        foreach ($corrections as $pattern => $replacement) {
            $ocrText = preg_replace($pattern, $replacement, $ocrText);
        }

        return $ocrText;
    }

    /**
     * Validates and corrects metadata fields, ensuring data consistency and correctness.
    *
    * @param array $metadata The metadata array to validate.
    * @param string $ocrText The OCR recognized text (used for context if needed).
    * @return string The (potentially) modified OCR text
    */
    private static function validateMetadata(array &$metadata, string $ocrText): string
    {
        // 1. Validate date_day
        if (isset($metadata['date_day'])) {
            $day = $metadata['date_day'];
            if (is_numeric($day)) {
                $day = (int) $day;
                if ($day < 1 || $day > 31) {
                    Log::warning("Invalid date_day detected: {$day}. Setting to empty.");
                    $metadata['date_day'] = '';
            } else {
                    $metadata['date_day'] = (string) $day;
                }
            } elseif (preg_match('/(\d{1,2})[IVX]+/', $day, $matches)) {
            $numericDay = (int) $matches[1];
                if ($numericDay >= 1 && $numericDay <= 31) {
                    $metadata['date_day'] = (string) $numericDay;
                } else {
                    Log::warning("Invalid numeric part in date_day detected: {$numericDay}. Setting to empty.");
                    $metadata['date_day'] = '';
                }
            } else {
                $numericDay = filter_var($day, FILTER_SANITIZE_NUMBER_INT);
                if ($numericDay && is_numeric($numericDay)) {
                    $numericDay = (int) $numericDay;
                    if ($numericDay >= 1 && $numericDay <= 31) {
                        $metadata['date_day'] = (string) $numericDay;
                } else {
                    Log::warning("Invalid numeric day extracted from date_day: {$numericDay}. Setting to empty.");
                    $metadata['date_day'] = '';
                }
                } else {
                    Log::warning("Non-numeric date_day detected: {$day}. Setting to empty.");
                $metadata['date_day'] = '';
                }
            }
        }


        // 2. Validate date_month
        if (isset($metadata['date_month'])) {
            $month = $metadata['date_month'];
            if (preg_match('/^[IVX]+$/', $month)) {
                $monthNumeric = self::romanToInt($month);
                if ($monthNumeric >= 1 && $monthNumeric <= 12) {
                    $metadata['date_month'] = (string) $monthNumeric;
                } else {
                    Log::warning("Invalid date_month detected (Roman): {$month}. Setting to empty.");
                    $metadata['date_month'] = '';
                }
        } elseif (is_numeric($month)) {
                $month = (int) $month;
            if ($month < 1 || $month > 12) {
                    Log::warning("Invalid date_month detected: {$month}. Setting to empty.");
                    $metadata['date_month'] = '';
            } else {
                    $metadata['date_month'] = (string) $month;
            }
            } else {
                Log::warning("Invalid date_month format: {$month}. Setting to empty.");
                $metadata['date_month'] = '';
            }
        }


        // 3. Validate date_year
        if (isset($metadata['date_year'])) {
        $year = $metadata['date_year'];
            if (!is_numeric($year) || (int)$year < 0) {
                Log::warning("Invalid date_year detected: {$year}. Setting to empty.");
                $metadata['date_year'] = '';
            } else {
                $metadata['date_year'] = (string) (int)$year;
            }
    }

        // 4. Validate date_is_range
        if (isset($metadata['date_is_range'])) {
            $isRange = strtolower($metadata['date_is_range']);
            $metadata['date_is_range'] = in_array($isRange, ['yes', 'true', '1'], true) ? true : false;
    }

        // 5. Validate Boolean Fields
    $booleanFields = [
            'date_uncertain', 'date_approximate', 'date_inferred',
            'author_inferred', 'author_uncertain',
        'recipient_inferred', 'recipient_uncertain',
            'origin_inferred', 'origin_uncertain',
            'destination_inferred', 'destination_uncertain',
        ];


        foreach ($booleanFields as $field) {
            if (isset($metadata[$field])) {
                $value = strtolower($metadata[$field]);
                $metadata[$field] = in_array($value, ['yes', 'true', '1'], true) ? true : false;
            }
        }

        // 6. Additional Validations (Example: date_note length)
        if (isset($metadata['date_note']) && strlen($metadata['date_note']) > 500) {
            Log::warning("date_note exceeds maximum length. Truncating.");
        $metadata['date_note'] = substr($metadata['date_note'], 0, 500);
        }

        // 7. Validate Mentioned field (Ensure only values from the original document are returned)
        if (isset($metadata['mentioned']) && is_array($metadata['mentioned'])) {
        $metadata['mentioned'] = array_filter($metadata['mentioned'], function($item) use ($ocrText) {
                return strpos($ocrText, $item) !== false;
            });
        }

        return $ocrText;
    }

    /**
     * Converts a Roman numeral string to its integer value.
     *
     * @param string $roman The Roman numeral string.
     * @return int The integer value.
     */
    private static function romanToInt(string $roman): int
    {
        $romans = [
            'M' => 1000,
        'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
        'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
        'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];

        $result = 0;
        foreach ($romans as $romanChar => $value) {
            while (strpos($roman, $romanChar) === 0) {
            $result += $value;
                $roman = substr($roman, strlen($romanChar));
            }
        }
        return $result;
    }


    /**
     * Corrects misinterpretations in date formats, specifically handling Roman vs. Arabic months.
     *
     * @param string $ocrText The text to correct.
     * @return string The corrected text.
     */
    public static function correctDateMisinterpretations(string $ocrText): string
    {
        $pattern = '/(\d{1,2})\/([IVX]+|\d{1,2})\/(\d{4})/';
        $ocrText = preg_replace_callback($pattern, function ($matches) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            if (is_numeric($month) && $month >= 1 && $month <= 12) {
            return "{$day}/{$month}/{$year}";
            }

        $romanMonths = [
            'I' => 'I',
            'II' => 'II',
                'III' => 'III',
                'IV' => 'IV',
                'V' => 'V',
                'VI' => 'VI',
                'VII' => 'VII',
                'VIII' => 'VIII',
                'IX' => 'IX',
            'X' => 'X',
                'XI' => 'XI',
                'XII' => 'XII'
            ];

            return "{$day}/" . ($romanMonths[$month] ?? $month) . "/{$year}";
        }, $ocrText);

        return $ocrText;
    }

    /**
     * Cleans up the temporary OCR files folder.
    */
    public static function cleanupTempFiles(): void
    {
        $directory = 'temp/ocr';
        if(Storage::disk('local')->exists($directory)) {
        $files = Storage::disk('local')->files($directory);

            foreach($files as $file) {
            Storage::disk('local')->delete($file);
            }
        }
}
}
