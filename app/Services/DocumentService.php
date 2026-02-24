<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    public const MODEL_OPENAI_GPT4O = 'openai_gpt4o';
    public const MODEL_GEMINI_FLASH_2 = 'gemini_flash_2';

    private static bool $enableAsynchronousRequests = true;
    private static bool $unifyMetadata = true;
    private const RAW_SNIPPET_MAX = 5000;
    private const PROMPT_SNIPPET_MAX = 5000;

    /**
     * Main entry point.
     */
    public static function processDocument($files, ?string $modelKey = null): array
    {
        $selectedModel = self::resolveModel($modelKey);
        $filePaths = is_array($files) ? $files : [$files];

        $apiKey = self::resolveApiKey($selectedModel);
        if (!$apiKey) {
            throw new Exception(self::missingKeyMessage($selectedModel));
        }

        foreach ($filePaths as $f) {
            if (!file_exists($f)) throw new Exception("File not found: $f");
        }

        usort($filePaths, fn($a, $b) => strnatcasecmp(basename($a), basename($b)));

        $client = new Client(['timeout' => 120]);
        $promises = [];
        $results = [];

        foreach ($filePaths as $index => $path) {
            $isBack = (stripos($path, 'back') !== false) || ($index % 2 !== 0);

            if (self::$enableAsynchronousRequests) {
                $promises[$index] = self::createAsyncRequest($client, $selectedModel, $apiKey, $path, $isBack);
            } else {
                $results[] = self::sendRequest($client, $selectedModel, $apiKey, $path, $isBack);
            }
        }

        if (self::$enableAsynchronousRequests) {
            $settled = \GuzzleHttp\Promise\Utils::settle($promises)->wait();
            ksort($settled);

            foreach ($settled as $r) {
                if ($r['state'] === 'fulfilled') {
                    $results[] = $r['value'];
                } else {
                    Log::error('OCR request failed: ' . $r['reason']->getMessage(), [
                        'model' => $selectedModel,
                    ]);
                }
            }
        }

        if (empty($results)) {
            throw new Exception("No documents were successfully processed.");
        }

        $allTexts = array_column($results, 'recognized_text');
        $allMetas = array_column($results, 'metadata');
        $allRawResponses = array_filter(array_column($results, 'raw_response'));
        $allPrompts = array_filter(array_column($results, 'request_prompt'));

        $finalText = trim(implode("\n\n--- PAGE BREAK ---\n\n", $allTexts));
        $finalMeta = self::unifyMetadata($allMetas);
        $provider = self::providerFromModel($selectedModel);

        return [
            'provider' => $provider,
            'model_key' => $selectedModel,
            'model' => self::apiModelName($selectedModel),
            'recognized_text' => $finalText,
            'metadata' => $finalMeta,
            'request_prompt' => self::truncate(implode("\n\n", $allPrompts), self::PROMPT_SNIPPET_MAX),
            'raw_response' => self::truncate(implode("\n\n", $allRawResponses), self::RAW_SNIPPET_MAX),
        ];
    }

    private static function createAsyncRequest(Client $client, string $modelKey, string $apiKey, string $path, bool $isBack)
    {
        $request = self::buildRequest($modelKey, $apiKey, $path, $isBack);

        return $client
            ->postAsync($request['endpoint'], [
                'headers' => $request['headers'],
                'json' => $request['payload'],
            ])
            ->then(fn($response) => self::parseResponse($modelKey, $response, $request['prompt']));
    }

    private static function sendRequest(Client $client, string $modelKey, string $apiKey, string $path, bool $isBack)
    {
        $request = self::buildRequest($modelKey, $apiKey, $path, $isBack);

        $response = $client->post($request['endpoint'], [
            'headers' => $request['headers'],
            'json' => $request['payload'],
        ]);

        return self::parseResponse($modelKey, $response, $request['prompt']);
    }

    private static function buildRequest(string $modelKey, string $apiKey, string $path, bool $isBack): array
    {
        return $modelKey === self::MODEL_GEMINI_FLASH_2
            ? self::buildGeminiRequest($apiKey, $path, $isBack)
            : self::buildOpenAiRequest($apiKey, $path, $isBack);
    }

    private static function buildOpenAiRequest(string $apiKey, string $path, bool $isBack): array
    {
        $base64 = base64_encode(file_get_contents($path));
        $mime = mime_content_type($path);
        $url = "data:{$mime};base64,{$base64}";
        $prompt = self::buildPrompt($isBack);

        return [
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'payload' => [
                'model' => self::apiModelName(self::MODEL_OPENAI_GPT4O),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a specialized expert in reading historical correspondence (OCR). You output strictly valid JSON.'],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $url, 'detail' => 'high']],
                        ],
                    ],
                ],
                'max_tokens' => 4000,
            ],
            'prompt' => $prompt,
        ];
    }

    private static function buildGeminiRequest(string $apiKey, string $path, bool $isBack): array
    {
        $base64 = base64_encode(file_get_contents($path));
        $mime = mime_content_type($path);
        $prompt = self::buildPrompt($isBack);
        $model = self::apiModelName(self::MODEL_GEMINI_FLASH_2);

        return [
            'endpoint' => "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'payload' => [
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inlineData' => [
                            'mimeType' => $mime,
                            'data' => $base64,
                        ]],
                    ],
                ]],
            ],
            'prompt' => $prompt,
        ];
    }

    private static function parseResponse(string $modelKey, $response, string $requestPrompt): array
    {
        $bodyRaw = $response->getBody()->getContents();
        $body = json_decode($bodyRaw, true) ?: [];

        if ($modelKey === self::MODEL_GEMINI_FLASH_2) {
            $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        } else {
            $content = $body['choices'][0]['message']['content'] ?? '{}';
        }

        $json = json_decode($content, true) ?: [];
        $text = $json['recognized_text'] ?? ($json['text'] ?? ($json['transcription'] ?? ''));
        $meta = $json['metadata'] ?? ($json['meta'] ?? []);

        return [
            'recognized_text' => $text,
            'metadata' => $meta,
            'request_prompt' => self::truncate($requestPrompt, self::PROMPT_SNIPPET_MAX),
            'raw_response' => self::truncate($bodyRaw, self::RAW_SNIPPET_MAX),
        ];
    }

    private static function unifyMetadata(array $allMetas): array
    {
        $final = [];
        foreach ($allMetas as $meta) {
            if (!is_array($meta)) continue;

            foreach ($meta as $key => $value) {
                if (!isset($final[$key]) || !empty($value)) {
                    if (is_array($value)) {
                        $existing = $final[$key] ?? [];
                        $final[$key] = array_unique(array_merge(is_array($existing) ? $existing : [], $value));
                    }
                    elseif (is_bool($value)) {
                        $final[$key] = ($final[$key] ?? false) || $value;
                    }
                    else {
                        $final[$key] = $value;
                    }
                }
            }
        }
        return $final;
    }

    public static function supportedModels(): array
    {
        return [
            self::MODEL_OPENAI_GPT4O => 'ChatGPT 4o',
            self::MODEL_GEMINI_FLASH_2 => 'Gemini 2.0 Flash',
        ];
    }

    private static function resolveModel(?string $modelKey): string
    {
        return array_key_exists($modelKey ?? '', self::supportedModels())
            ? $modelKey
            : self::MODEL_OPENAI_GPT4O;
    }

    private static function providerFromModel(string $modelKey): string
    {
        return $modelKey === self::MODEL_GEMINI_FLASH_2 ? 'gemini' : 'openai';
    }

    private static function apiModelName(string $modelKey): string
    {
        return $modelKey === self::MODEL_GEMINI_FLASH_2
            ? 'gemini-2.0-flash'
            : 'gpt-4o';
    }

    private static function resolveApiKey(string $modelKey): ?string
    {
        return $modelKey === self::MODEL_GEMINI_FLASH_2
            ? (config('services.gemini.api_key') ?? env('GEMINI_API_KEY'))
            : (config('services.openai.api_key') ?? env('OPENAI_API_KEY'));
    }

    private static function missingKeyMessage(string $modelKey): string
    {
        return $modelKey === self::MODEL_GEMINI_FLASH_2
            ? 'Gemini API key is missing in .env (GEMINI_API_KEY)'
            : 'OpenAI API key is missing in .env (OPENAI_API_KEY)';
    }

    private static function buildPrompt(bool $isBack): string
    {
        $fields = [
            'Rok',
            'Měsíc',
            'Den',
            'Datum označené v dopise',
            'Datum je nejisté (bool)',
            'Datum je přibližné (bool)',
            'Datum je odvozené (bool)',
            'Datum je uvedené v rozmezí (bool)',
            'Autor',
            'Jméno autora',
            'Autor je odvozený (bool)',
            'Autor je nejistý (bool)',
            'Příjemce',
            'Jméno příjemce',
            'Příjemce je odvozený (bool)',
            'Příjemce je nejistý (bool)',
            'Místo odeslání',
            'Místo odeslání je odvozené (bool)',
            'Místo odeslání je nejisté (bool)',
            'Místo určení',
            'Místo určení je odvozené (bool)',
            'Místo určení je nejisté (bool)',
            "Jazyk (array of codes like 'cs', 'de')",
            'Abstrakt CS',
            'Abstrakt EN',
            'Incipit',
            'Explicit',
            'Poznámka k datu',
            'Poznámka k autorům',
            'Poznámka k příjemcům',
            'Poznámka pro zpracovatele',
            'Veřejná poznámka',
            'Copyright',
        ];

        $fieldString = implode(', ', $fields);

        return "Transcribe the full text of this document image verbatim.\n" .
            "Then, extract metadata into a 'metadata' JSON object using these keys:\n{$fieldString}.\n" .
            "Return valid JSON with keys: recognized_text (string) and metadata (object).\n" .
            'If a field is not found, use null or false.' .
            ($isBack ? "\nNOTE: This image appears to be the back side or envelope." : '');
    }

    private static function truncate(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max);
    }

    public static function cleanupTempFiles(): void {}
}
