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
        $documentAIConfig = config('services.google_cloud.document_ai');
        $projectId = config('services.google_cloud.project_id');
        $keyFile = config('services.google_cloud.key_file');

        if (empty($projectId)) {
            throw new \Exception("GOOGLE_CLOUD_PROJECT_ID is not set in your .env file.");
        }

        if (empty($keyFile) || !file_exists($keyFile)) {
            throw new \Exception("GOOGLE_CLOUD_KEY_FILE is not set correctly or the file does not exist.");
        }

        if (empty($documentAIConfig['processor_id']) || empty($documentAIConfig['processor_location'])) {
            throw new \Exception("GOOGLE_CLOUD_PROCESSOR_ID or GOOGLE_CLOUD_PROCESSOR_LOCATION is not set in your .env file.");
        }

        // Set the apiEndpoint based on the processor location
        $apiEndpoint = "{$documentAIConfig['processor_location']}-documentai.googleapis.com";

        $this->processorClient = new DocumentProcessorServiceClient([
            'credentials' => $keyFile,
            'apiEndpoint' => $apiEndpoint,
        ]);

        $this->processorName = $this->processorClient->processorName(
            $projectId,
            $documentAIConfig['processor_location'],
            $documentAIConfig['processor_id']
        );
    }

    /**
     * Process a document image and extract text and entities.
     *
     * @param string $imagePath Absolute path to the document image.
     * @param string $language Selected language for processing.
     * @return array Extracted text and entities.
     * @throws \Exception
     */
    public function processDocument(string $imagePath, string $language): array
    {
        try {
            if (!file_exists($imagePath)) {
                throw new \Exception("File does not exist at path: {$imagePath}");
            }

            $imageContent = file_get_contents($imagePath);

            $rawDocument = new RawDocument([
                'content' => $imageContent,
                'mime_type' => $this->getMimeType($imagePath),
            ]);

            $request = new ProcessRequest([
                'name' => $this->processorName,
                'raw_document' => $rawDocument,
            ]);

            $response = $this->processorClient->processDocument($request);
            $document = $response->getDocument();

            $extractedText = $document->getText();

            $entities = $this->extractEntities($document);

            return [
                'text' => $extractedText,
                'entities' => $entities,
            ];
        } catch (\Exception $e) {
            Log::error('Document AI Processing Error: ' . $e->getMessage());
            throw new \Exception('Error processing document: ' . $e->getMessage());
        }
    }

    /**
     * Dynamically determine MIME type based on file extension.
     *
     * @param string $imagePath
     * @return string
     */
    private function getMimeType(string $imagePath): string
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpeg', 'jpg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /**
     * Extract entities from the processed document.
     *
     * @param \Google\Cloud\DocumentAI\V1\Document $document
     * @return array
     */
    private function extractEntities($document): array
    {
        $entities = [];
        foreach ($document->getEntities() as $entity) {
            $entities[] = [
                'name' => $entity->getText(),
                'type' => $entity->getType(),
                'mentionText' => $entity->getMentionText(),
                'confidence' => $entity->getConfidence(),
                'pageNumber' => $entity->getPageNumber(),
            ];
        }
        return $entities;
    }

    /**
     * Close the DocumentProcessorServiceClient when the service is destroyed.
     */
    public function __destruct()
    {
        $this->processorClient->close();
    }
}
