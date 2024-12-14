<?php

namespace App\Services;

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Illuminate\Support\Facades\Log;

class GoogleDocumentAIService
{
    protected DocumentProcessorServiceClient $processorClient;
    protected string $processorName;

    public function __construct()
    {
        $config = config('services.google_cloud.document_ai');
        $projectId = config('services.google_cloud.project_id');
        $keyFile = config('services.google_cloud.key_file');

        if (empty($projectId) || empty($keyFile) || !file_exists($keyFile)) {
            throw new \Exception('Google Cloud configuration is incomplete.');
        }

        $this->processorClient = new DocumentProcessorServiceClient([
            'credentials' => $keyFile,
            'apiEndpoint' => "{$config['processor_location']}-documentai.googleapis.com",
        ]);

        $this->processorName = $this->processorClient->processorName(
            $projectId,
            $config['processor_location'],
            $config['processor_id']
        );
    }

    public function processDocument(string $imagePath, string $language): array
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("File not found at: {$imagePath}");
        }

        $rawDocument = new RawDocument([
            'content' => file_get_contents($imagePath),
            'mime_type' => $this->getMimeType($imagePath),
        ]);

        $request = new ProcessRequest([
            'name' => $this->processorName,
            'raw_document' => $rawDocument,
        ]);

        $response = $this->processorClient->processDocument($request);
        $document = $response->getDocument();

        return [
            'text' => $document->getText(),
            'entities' => $this->extractEntities($document),
        ];
    }

    private function getMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function extractEntities($document): array
    {
        $entities = [];
        foreach ($document->getEntities() as $entity) {
            $entities[] = [
                'name' => $entity->getText(),
                'type' => $entity->getType(),
                'mentionText' => $entity->getMentionText(),
                'confidence' => $entity->getConfidence(),
                'pageNumber' => $entity->getPageAnchor()->getPageRefs()[0]->getPage() ?? null,
            ];
        }
        return $entities;
    }

    public function __destruct()
    {
        $this->processorClient->close();
    }
}
