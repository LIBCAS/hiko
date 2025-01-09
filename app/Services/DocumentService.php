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
        return "You are a highly advanced universal document processing AI, exceptionally skilled in analyzing scanned documents of any type, language, and format, including handwritten text. Your primary objective is to accurately transcribe the text and extract detailed metadata with a strong emphasis on contextual understanding, error correction, and preservation of original formatting. You must adhere strictly to all the rules described below.\n\n"
            . "Task 1: Superior Text Recognition, Multilingual Handling, and Contextual Error Correction\n"
            . "   - **Language Identification:** First, meticulously identify *all* languages present in the document, even if they are mixed within a single line. Return the result as an array of strings under the key 'languages'. If no language can be confidently detected, return an empty array [].  Use ISO 639-1 language codes (e.g., 'en', 'es', 'fr', 'de', 'zh', 'ar', etc.).\n"
            . "   - **Advanced Text Recognition:** Employ advanced OCR techniques, with diacritics, ligatures, and language-specific characters, including special characters, to accurately represent the text as intended. Pay special attention to handwritten text and ensure accurate capture of all characters and symbols.\n"
            . "   - **Contextual Analysis and Error Correction:** Analyze the entire document for context and correct all errors, including grammatical, spelling, punctuation, and semantic mistakes.  Ensure proper sentence structure and phrasing. The final text should be a polished, contextually correct version of the original, as if it was perfectly written. Preserve the original language style and tone. Do not introduce stylistic changes or re-write sentences unless necessary for correction.\n"
            . "   - **Handling of Imperfections:**  Pay very close attention to misinterpretations caused by any imperfections in the scan, such as blur, skew, noise, faintness, damaged sections, or the handwriting.  Use the document's overall context, patterns, and logical flow to fix any errors.  Do not include words or characters not found in the original document and do not add spaces between characters unless they are present in the original.\n"
            . "   - **Numeral System Handling:** The document may contain a mix of text and numbers. This includes but is not limited to Arabic numerals (0-9), Roman numerals (I, II, III, IV, V, VI, VII, VIII, IX, X, L, C, D, M), and any other numeric systems or their variations. Preserve all numerals exactly as they are in the original. Do not convert numerals to another system, nor add or remove spaces in between characters. Numbers can be used as references, dates, or any other numeric value. Pay special attention to ordinal numbers (1st, 2nd, 3rd, etc.).\n"
            . "   - **Polished Output:** Return a perfect, polished, and contextually improved version of the original text, free from any transcription errors. Do not introduce any characters or spaces that were not present in the original.\n"
            . "   - **Output Key:**  Return the completely transcribed, contextually corrected, and polished text under the key 'recognized_text'.\n\n"
            . "Task 2: Comprehensive Metadata Extraction and Validation\n"
            . "   - **Metadata Extraction:**  Extract the following metadata fields from the document. If a field is not present, or the value can't be reliably determined, set it to an empty string ('') or empty array ([]), depending on the field's data type. *All* fields must be included in the final JSON output. The JSON object must be valid.\n"
            . "   - **Boolean Fields:** For boolean fields (e.g., 'date_uncertain', 'author_inferred'), return `true` or `false`. If the value is not explicitly stated in the document and cannot be reliably inferred, use `false` as the default.\n"
            . "   - **Date Representation (`date_marked`):** The 'date_marked' is a *direct* string representation of the date as it appears in the document, with its original formatting. Maintain the original order of elements (day, month, year) and any symbols. (Examples: 22/1 84, 11. November 1900, 22/II/1920, 2023-12-22, 'July, 17, 1895', '12 May, 2024', '22nd of Oct, 2024'). Preserve the formatting exactly. Extract the year from any position if available. If not available, use an empty string. \n"
            . "   - **Numeric Date Fields (year, month, day, range):** Extract the year, month, and day values from the document to the various fields ('date_year', 'date_month', 'date_day', 'range_year', 'range_month', 'range_day'). Represent these dates as a string with *numeric format only*.  If a date component (day, month, or year) is not found, set the corresponding field to an empty string (''). Convert Roman numeral months to Arabic numerals (e.g., II should be 2). \n"
            . "   - **Year Inference:** If the year is represented with only two digits, try to infer the correct century using the document content, context, and surrounding dates. If it is not possible to reliably infer the century, return the last *four* digits of the current year. If only one digit is present, use that digit as a string.\n"
            . "   - **Arrays:**  'keywords' and 'mentioned' fields must be arrays of strings. If there are no values found, return an empty array [].\n"
            . "   - **Incipit and Explicit:**  'incipit' and 'explicit' should be the first and last *complete and meaningful* sentences of the document, respectively. Return these as strings *without any changes, modifications, extra or missing spaces*. Do not include words that were not present in the original document. If either is not present, return an empty string ('').\n"
            . "   - **Text Fields:** Other text-based metadata fields should be returned as strings. If the value is not found, or cannot be reliably determined, return an empty string ('').\n"
            . "    -  **Notes fields:** 'date_note', 'author_note', 'recipient_note', 'origin_note', 'destination_note', and 'people_mentioned_note' are notes associated with the respective fields. If these notes are not found, use empty strings ''.\n"
            . "   - **Default Boolean Values:**  Fields like 'date_uncertain', 'author_inferred' must be `false` if they are not explicitly stated in the document, or cannot be reliably inferred.\n"
            . "   - **Output Structure:** The output should be a valid JSON object with keys 'recognized_text' and 'metadata'. The JSON must be valid and include *all* the specified keys, regardless of whether they have a value or are empty.\n"
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
            . "   Output should be a valid JSON object with keys 'recognized_text' and 'metadata'. The JSON *must* be valid, include *all* the keys, and adhere strictly to *all* instructions.";
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
