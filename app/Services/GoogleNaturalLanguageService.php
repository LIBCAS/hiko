<?php

namespace App\Services;

use Google\Cloud\Language\LanguageClient;
use Illuminate\Support\Facades\Log;

class GoogleNaturalLanguageService
{
    protected $client;

    public function __construct()
    {
        $this->client = new LanguageClient([
            'projectId' => config('services.google_cloud.project_id'),
            'keyFilePath' => config('services.google_cloud.key_file'),
        ]);
    }

    /**
     * Analyze entities in the text using Google NLP API.
     */
    public function analyzeEntities(string $text): array
    {
        $response = $this->client->analyzeEntities($text);
        $entities = [];

        foreach ($response->entities() as $entity) {
            $entities[] = [
                'name' => $entity['name'],
                'type' => $entity['type'],
            ];
        }

        return $entities;
    }

    /**
     * Analyze syntax in the text (optional).
     */
    public function analyzeSyntax(string $text): array
    {
        $response = $this->client->analyzeSyntax($text);
        $syntax = [
            'tokens' => [],
        ];

        foreach ($response->tokens() as $token) {
            $syntax['tokens'][] = [
                'text' => [
                    'content' => $token['text']['content'],
                ],
                'partOfSpeech' => [
                    'tag' => $token['partOfSpeech']['tag'],
                ],
            ];
        }

        return $syntax;
    }
}