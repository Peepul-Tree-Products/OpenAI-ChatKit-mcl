<?php
/**
 * OpenAI Provider Implementation
 *
 * Implements the ProviderInterface for OpenAI's API.
 * Supports completions, streaming, and structured extraction.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Providers;

class OpenAIProvider implements ProviderInterface {
    /**
     * @var string OpenAI API key
     */
    private string $apiKey;

    /**
     * @var string Default model to use
     */
    private string $model;

    /**
     * @var string API base URL
     */
    private string $baseUrl = 'https://api.openai.com/v1';

    /**
     * @var array Available models with pricing (per 1M tokens)
     */
    private const MODELS = [
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
        'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
    ];

    /**
     * Constructor
     *
     * @param string $apiKey OpenAI API key
     * @param string $model Default model (default: gpt-4o-mini)
     */
    public function __construct(string $apiKey, string $model = 'gpt-4o-mini') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function complete(array $messages, array $options = []): array {
        $requestBody = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1000,
        ];

        $response = $this->makeRequest('/chat/completions', $requestBody);

        if (isset($response['error'])) {
            throw new \Exception("OpenAI API error: " . $response['error']['message']);
        }

        $choice = $response['choices'][0] ?? null;

        if ($choice === null) {
            throw new \Exception("No choices returned from OpenAI API");
        }

        return [
            'content' => $choice['message']['content'] ?? '',
            'metadata' => [
                'model' => $response['model'],
                'usage' => $response['usage'] ?? [],
                'finish_reason' => $choice['finish_reason'] ?? null,
                'provider' => 'openai',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function stream(array $messages, array $options = []): \Generator {
        $requestBody = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'stream' => true,
        ];

        // WordPress doesn't natively support streaming, so this is a simplified implementation
        // For production, consider using Guzzle or curl directly

        $url = $this->baseUrl . '/chat/completions';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            static $buffer = '';

            $buffer .= $data;
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines); // Keep incomplete line in buffer

            foreach ($lines as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);

                    if ($json === '[DONE]') {
                        return strlen($data);
                    }

                    $chunk = json_decode($json, true);

                    if ($chunk) {
                        $delta = $chunk['choices'][0]['delta']['content'] ?? '';

                        if ($delta) {
                            yield $delta;
                        }
                    }
                }
            }

            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * {@inheritdoc}
     */
    public function extract(array $messages, array $schema): array {
        // Use function calling for structured extraction
        $requestBody = [
            'model' => 'gpt-4o-mini', // Use faster model for extraction
            'messages' => $messages,
            'temperature' => 0, // Deterministic for extraction
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'extract_data',
                        'description' => 'Extract structured data from the conversation',
                        'parameters' => $schema,
                    ],
                ],
            ],
            'tool_choice' => ['type' => 'function', 'function' => ['name' => 'extract_data']],
        ];

        $response = $this->makeRequest('/chat/completions', $requestBody);

        if (isset($response['error'])) {
            throw new \Exception("OpenAI API error: " . $response['error']['message']);
        }

        $choice = $response['choices'][0] ?? null;
        $toolCalls = $choice['message']['tool_calls'] ?? [];

        if (empty($toolCalls)) {
            throw new \Exception("No tool calls returned from extraction");
        }

        $arguments = $toolCalls[0]['function']['arguments'] ?? '{}';

        return json_decode($arguments, true);
    }

    /**
     * {@inheritdoc}
     */
    public function health(): bool {
        try {
            // Test with a minimal request
            $response = $this->makeRequest('/models', [], 'GET');

            return isset($response['data']) && is_array($response['data']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function getModels(): array {
        return array_keys(self::MODELS);
    }

    /**
     * {@inheritdoc}
     */
    public function estimateCost(int $inputTokens, int $outputTokens): float {
        $pricing = self::MODELS[$this->model] ?? ['input' => 0, 'output' => 0];

        $inputCost = ($inputTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($outputTokens / 1_000_000) * $pricing['output'];

        return $inputCost + $outputCost;
    }

    /**
     * Make an API request
     *
     * @param string $endpoint API endpoint
     * @param array $body Request body
     * @param string $method HTTP method
     * @return array Response data
     * @throws \Exception If request fails
     */
    private function makeRequest(string $endpoint, array $body = [], string $method = 'POST'): array {
        $url = $this->baseUrl . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'timeout' => 30,
        ];

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception("HTTP request failed: " . $response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);

        $data = json_decode($responseBody, true);

        if ($statusCode >= 400) {
            $errorMessage = $data['error']['message'] ?? "HTTP {$statusCode} error";
            throw new \Exception($errorMessage);
        }

        return $data;
    }

    /**
     * Set the default model
     *
     * @param string $model Model identifier
     * @return self For method chaining
     */
    public function setModel(string $model): self {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the current model
     *
     * @return string Model identifier
     */
    public function getModel(): string {
        return $this->model;
    }
}
