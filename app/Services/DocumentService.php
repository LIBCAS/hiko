<?php

namespace App\Services;

use Exception;
use Google\GenerativeAI\GenerativeModel;
use Google\GenerativeAI\GenerativeClient;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    private static $client = null;
    private static $model = null;
    private static function getClient()
    {
        if (!self::$client) {
            $apiKey = config('services.gemini.api_key');
            if (!$apiKey) {
                throw new Exception("Gemini API key not configured");
            }
            self::$client = new GenerativeClient($apiKey);
        }
        return self::$client;
    }
    private static function getModel()
    {
        if (!self::$model) {
            self::$model = self::getClient()->getGenerativeModel('gemini-pro-vision');
        }
        return self::$model;
    }

    public static function processHandwrittenLetter(string $imagePath): array
    {
        try {

            $prompt = "You are an expert in analyzing handwritten documents. Analyze the following HANDWRITTEN letter, which has been provided as an image. Your task is to:\n"
                . "1. Perform OCR on the image to recognize the text, regardless of the language.\n"
                . "2. Identify the language(s) used in the handwritten letter. If multiple languages are present, include all of them in an array, otherwise, just use a string.\n"
                . "3. Extract key metadata from the handwritten letter. This may include 'author', 'date', 'addressee', and any other relevant information that you can recognize from the text. If the information is not present, set the value to null. Return the metadata as a JSON object.\n"
                . "4. Return the full, transcribed text of the handwritten letter, preserving line breaks, formatting, and special characters. \n"
                . "Provide a JSON object with the keys 'language', 'summary', 'metadata' and 'full_text'. Use the following guidelines:\n"
                . "- The 'language' key should contain the language or languages detected in the handwritten letter. If multiple languages are present, it must be an array, otherwise, just a string.\n"
                . "- The 'summary' key should contain a short summary of the handwritten letter.\n"
                . "- The 'metadata' key should contain the JSON object with the metadata that you extracted from the letter.\n"
                . "- The 'full_text' key should contain the full, transcribed text of the handwritten letter.\n"
                . "Here is the image of the handwritten letter:\n";


            $model = self::getModel();

            $file_content = Storage::get($imagePath);

            $response = $model->generateContent([$prompt,  genai()->part()->inlineData($file_content, 'image/png')]);

            try {
                $decoded_response = json_decode($response->text, true);
                if ($decoded_response) {
                    return $decoded_response;
                } else {
                    throw new Exception("Gemini did not return a JSON");
                }
            } catch (Exception $e) {
                throw new Exception("Error decoding Gemini response to JSON: " . $e->getMessage() . " Raw Gemini Response: " . $response->text);
            }
        } catch (Exception $e) {
            throw new Exception("Error during Gemini document processing: " . $e->getMessage());
        }
    }
}
