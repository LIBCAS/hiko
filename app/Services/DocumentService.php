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

            // Construct the payload with enhanced instructions and precise JSON structure
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
     * Build prompt for Gemini 2.0 Flash API with enhanced instructions for multilingual and numeral system handling.
     *
     * @return string
     */
    private static function buildPrompt(): string
    {
        return "You are a universal document processing AI, skilled in analyzing scanned documents of any type, language, and format. Your objective is to accurately transcribe the text and extract detailed metadata with a strong focus on contextual understanding and error correction. You must follow all the rules described below.\n\n"
            . "Task 1: Advanced Text Recognition, Contextual Analysis, and Error Correction\n"
            . "   - First, identify all languages present in the document. Return the result as an array of strings under the key 'languages'. If no language can be detected, use an empty array [].\n"
            . "   - Perform advanced text recognition using diacritics and language-specific characters to accurately represent the text. Ensure all words and numbers are correct.\n"
            . "   - Analyze the entire document for context, correcting all grammatical, spelling, and contextual errors. Ensure proper sentence structure and phrasing. Correct all the errors, and provide a polished text.\n"
            . "   - Pay special attention to misinterpretations caused by any imperfections in the document. Use the document's context to fix those errors. Do not include words that are not in the original document. Do not add any spaces in between the characters if not present in the original document.\n"
            . "   - The document may contain a mixture of text and numbers, including Arabic numerals (0-9), Roman numerals (I, II, III, IV, V, VI, VII, VIII, IX, X, L, C, D, M), and any other numeric system. Preserve all numerals, do not convert any of them, or add spaces in between characters.\n"
           . "   - The objective is to return a polished and perfect version of the document, and not just a literal transcription of the words and numbers. The returned text must be grammatically correct, as if the document was originally written in that way, without any mistakes. Do not add extra characters or spaces if they do not exist.\n"
             . "   - Return the contextually improved and perfectly transcribed text under the key 'recognized_text'.\n\n"
            . "Task 2: Accurate Metadata Extraction and Validation\n"
             . "   - Extract the following metadata from the document. If any field is not present, set it to an empty string ('') or empty array ([]), depending on the field's data type. All fields must be present in the output.\n"
            . "   - All metadata fields MUST be present in the JSON output, even if empty, and the JSON must be a valid object.\n"
            . "   - For boolean fields (e.g., 'date_uncertain', 'author_inferred'), return true or false values. If there is no explicit value in the text, use `false` as default.\n"
            . "   - The 'date_marked' is a direct string representation of the date as it appears in the document, with the original formatting. For example: 22/1 84, or 11. November 1900, or 22/II/1920, or 2023-12-22. Always use the original formatting and order of the elements (day, month, year), when available. Extract the year from any place, if available.\n"
            . "   - Date and range dates (year, month, and day) should be extracted from the document to the various fields, and represented in numeric format only. All values must be a string. If the date has no day, month or year, set them as empty strings. When month is represented in roman numerals, convert them to numbers. For example, II should be 2.\n"
             . "   - If the year is represented with only two digits, try to infer the correct century, using the date and the content of the document as a reference. If not possible, return the last 4 digits of the current year. If only one digit is present, use the last digit as is.\n"
            . "   - 'keywords' and 'mentioned' fields should be arrays of strings. If there are no values found, use an empty array [].\n"
           . "   - 'incipit' and 'explicit' are the first and last meaningful sentences of the document. Return them as a string, without modifications, spaces, or any changes. Do not include words that are not in the original document. If there is no explicit or incipit, return an empty string ''.\n"
            . "   - Other text-based fields should be returned as strings. If not found, it should be an empty string ('').\n"
            . "   - Fields like 'date_uncertain', or 'author_inferred' should be set as `false` if they are not explicitly stated in the document or if they can't be reliably determined.\n"
             . "   - 'date_note', 'author_note', 'recipient_note', 'origin_note', 'destination_note', and 'people_mentioned_note' are notes about the respective fields. If these notes are not found, use empty strings ''.\n"
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
            . "     \"status\": string \n"
            . "   }\n\n"
            . "   Output should be a valid JSON object with keys 'recognized_text' and 'metadata'. The JSON must be valid and include all the keys, and follow all the instructions.\n";
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

        // Initialize all metadata fields to ensure completeness
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
            'notes_public', 'copyright', 'status'
        ];

        // Ensure all metadata fields are present
        if (!isset($decoded['metadata'])) {
            $decoded['metadata'] = [];
        }

        foreach ($metadataFields as $field) {
            if (!array_key_exists($field, $decoded['metadata'])) {
                $decoded['metadata'][$field] = is_array($decoded[$field] ?? '') ? [] : '';
            }
        }

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
            $decoded['recognized_text'] = self::validateMetadata($decoded['metadata'], $decoded['recognized_text']);
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
            '/\b1\b/'     => 'I',
            '/\b2\b/'     => 'II',
            '/\b3\b/'     => 'III',
            '/\b4\b/'     => 'IV',
            '/\b5\b/'     => 'V',
            '/\b6\b/'     => 'VI',
            '/\b7\b/'     => 'VII',
            '/\b8\b/'     => 'VIII',
            '/\b9\b/'     => 'IX',
            '/\b10\b/'    => 'X',
            '/\b50\b/'    => 'L',
            '/\b100\b/'   => 'C',
            '/\b500\b/'   => 'D',
            '/\b1000\b/'  => 'M',
            // Additional corrections based on observed OCR errors
            '/\b0\b/'     => 'O',          // '0' misread as 'O'
            '/\b57I\b/'   => 'VII',        // Specific correction for '57I'
            '/\b22\/I\b/' => '22/I',        // Ensuring '22/I' is preserved
            // Add more patterns as needed
        ];

        foreach ($corrections as $pattern => $replacement) {
            $ocrText = preg_replace($pattern, $replacement, $ocrText);
        }

        return $ocrText;
    }

    /**
     * Validate and correct metadata fields.
     *
     * @param array $metadata
     * @param string $ocrText
     * @return string
     */
    private static function validateMetadata(array &$metadata, string $ocrText): string
    {
        // Validate date_day
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
                // Extract numeric part from mixed alphanumeric day
                $numericDay = (int) $matches[1];
                if ($numericDay >= 1 && $numericDay <= 31) {
                    $metadata['date_day'] = (string) $numericDay;
                } else {
                    Log::warning("Invalid numeric part in date_day detected: {$numericDay}. Setting to empty.");
                    $metadata['date_day'] = '';
                }
            } else {
                // Attempt to extract numeric value if possible
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

        // Validate date_month
        if (isset($metadata['date_month'])) {
            $month = $metadata['date_month'];
            if (preg_match('/^[IVX]+$/', $month)) {
                // Convert Roman numeral to integer
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

        // Validate date_year
        if (isset($metadata['date_year'])) {
            $year = $metadata['date_year'];
            if (!is_numeric($year) || (int)$year < 0) {
                Log::warning("Invalid date_year detected: {$year}. Setting to empty.");
                $metadata['date_year'] = '';
            } else {
                $metadata['date_year'] = (string) (int)$year;
            }
        }

        // Validate date_is_range
        if (isset($metadata['date_is_range'])) {
            $isRange = strtolower($metadata['date_is_range']);
            $metadata['date_is_range'] = in_array($isRange, ['yes', 'true', '1'], true) ? true : false;
        }

        // Validate boolean fields
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

        // Additional Validations
        // Example: Validate date_note length
        if (isset($metadata['date_note']) && strlen($metadata['date_note']) > 500) {
            Log::warning("date_note exceeds maximum length. Truncating.");
            $metadata['date_note'] = substr($metadata['date_note'], 0, 500);
        }

        // Similarly, add validations for other fields as needed

        return $ocrText;
    }

    /**
     * Convert Roman numeral to integer.
     *
     * @param string $roman
     * @return int
     */
    private static function romanToInt(string $roman): int
    {
        $romans = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
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
                'I'    => 'I',
                'II'   => 'II',
                'III'  => 'III',
                'IV'   => 'IV',
                'V'    => 'V',
                'VI'   => 'VI',
                'VII'  => 'VII',
                'VIII' => 'VIII',
                'IX'   => 'IX',
                'X'    => 'X',
                'XI'   => 'XI',
                'XII'  => 'XII'
            ];

            return "{$day}/" . ($romanMonths[$month] ?? $month) . "/{$year}";
        }, $ocrText);

        return $ocrText;
    }
}
