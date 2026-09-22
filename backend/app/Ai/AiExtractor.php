<?php

namespace App\Ai;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads structured data out of a photo or a transcript with Claude. Always an assist:
 * callers show the result for the person to confirm, and fall back to manual entry when
 * AI is not configured or the reading fails.
 */
class AiExtractor
{
    private ?Client $client = null;

    public function enabled(): bool
    {
        return (string) config('product.ai.api_key') !== '';
    }

    /**
     * @param  array<string, mixed>  $schema  JSON schema of the expected object
     * @return array<string, mixed>|null
     */
    public function fromImage(string $bytes, string $mediaType, string $instruction, array $schema): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->run([
            ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mediaType, 'data' => base64_encode($bytes)]],
            ['type' => 'text', 'text' => $instruction],
        ], $schema);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>|null
     */
    public function fromText(string $text, string $instruction, array $schema): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        return $this->run([
            ['type' => 'text', 'text' => $instruction."\n\n<input>\n".$text."\n</input>"],
        ], $schema);
    }

    /** @param  list<array<string, mixed>>  $content */
    private function run(array $content, array $schema): ?array
    {
        try {
            $message = $this->client()->messages->create(
                maxTokens: 8000,
                messages: [['role' => 'user', 'content' => $content]],
                model: config('product.ai.model'),
                outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            );
            foreach ($message->content as $block) {
                if ($block->type === 'text') {
                    $data = json_decode($block->text, true);

                    return is_array($data) ? $data : null;
                }
            }
        } catch (Throwable $e) {
            Log::warning('AI extraction failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: (string) config('product.ai.api_key'));
    }
}
