<?php

namespace Tests\Unit;

use App\Livewire\OcrUpload;
use App\Services\DocumentService;
use Exception;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;
use Tests\TestCase;

class OcrModelSelectionTest extends TestCase
{
    public function test_supported_ocr_models_include_latest_options(): void
    {
        $this->assertSame([
            DocumentService::MODEL_OPENAI_GPT4O => 'ChatGPT 4o',
            DocumentService::MODEL_OPENAI_GPT55 => 'ChatGPT 5.5',
            DocumentService::MODEL_GEMINI_FLASH_2 => 'Gemini 2.0 Flash',
            DocumentService::MODEL_GEMINI_31_PRO => 'Gemini 3.1 Pro',
        ], DocumentService::supportedModels());
    }

    public function test_chatgpt_55_is_default_ocr_upload_model(): void
    {
        $this->assertSame(DocumentService::MODEL_OPENAI_GPT55, (new OcrUpload())->selectedModel);
    }

    public function test_model_keys_resolve_to_provider_and_api_model_names(): void
    {
        $this->assertSame('openai', DocumentService::providerFromModel(DocumentService::MODEL_OPENAI_GPT55));
        $this->assertSame('gpt-5.5', DocumentService::apiModelName(DocumentService::MODEL_OPENAI_GPT55));

        $this->assertSame('gemini', DocumentService::providerFromModel(DocumentService::MODEL_GEMINI_31_PRO));
        $this->assertSame('gemini-3.1-pro-preview', DocumentService::apiModelName(DocumentService::MODEL_GEMINI_31_PRO));
    }

    public function test_chatgpt_55_request_uses_selected_model_and_token_parameter(): void
    {
        $file = $this->makeTempFile();

        try {
            $request = $this->invokeDocumentServiceMethod('buildOpenAiRequest', [
                DocumentService::MODEL_OPENAI_GPT55,
                'test-key',
                $file,
                false,
            ]);

            $this->assertSame('gpt-5.5', $request['payload']['model']);
            $this->assertSame(8000, $request['payload']['max_completion_tokens']);
            $this->assertSame('low', $request['payload']['reasoning_effort']);
            $this->assertArrayNotHasKey('max_tokens', $request['payload']);
        } finally {
            @unlink($file);
        }
    }

    public function test_chatgpt_4o_request_keeps_legacy_token_parameter(): void
    {
        $file = $this->makeTempFile();

        try {
            $request = $this->invokeDocumentServiceMethod('buildOpenAiRequest', [
                DocumentService::MODEL_OPENAI_GPT4O,
                'test-key',
                $file,
                false,
            ]);

            $this->assertSame('gpt-4o', $request['payload']['model']);
            $this->assertSame(4000, $request['payload']['max_tokens']);
            $this->assertArrayNotHasKey('max_completion_tokens', $request['payload']);
        } finally {
            @unlink($file);
        }
    }

    public function test_gemini_request_uses_selected_model(): void
    {
        $file = $this->makeTempFile();

        try {
            $request = $this->invokeDocumentServiceMethod('buildGeminiRequest', [
                DocumentService::MODEL_GEMINI_31_PRO,
                'test-key',
                $file,
                false,
            ]);

            $this->assertStringContainsString(
                '/models/gemini-3.1-pro-preview:generateContent',
                $request['endpoint']
            );
        } finally {
            @unlink($file);
        }
    }

    public function test_empty_openai_length_response_is_treated_as_failure(): void
    {
        $response = new Response(200, [], json_encode([
            'choices' => [[
                'message' => ['content' => ''],
                'finish_reason' => 'length',
            ]],
            'usage' => [
                'completion_tokens_details' => [
                    'reasoning_tokens' => 4000,
                ],
            ],
        ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('OpenAI OCR response reached the completion token limit');

        $this->invokeDocumentServiceMethod('parseResponse', [
            DocumentService::MODEL_OPENAI_GPT55,
            $response,
            'test prompt',
        ]);
    }

    private function invokeDocumentServiceMethod(string $method, array $arguments): array|string
    {
        $reflection = new ReflectionClass(DocumentService::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $arguments);
    }

    private function makeTempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'ocr-test-');
        file_put_contents($file, 'test image bytes');

        return $file;
    }
}
