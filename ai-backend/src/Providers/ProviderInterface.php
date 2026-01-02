<?php
/**
 * Provider Interface
 *
 * Abstract interface for AI model providers (OpenAI, Anthropic, local models, etc.)
 * This allows swapping providers without changing agent code.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Providers;

interface ProviderInterface {
    /**
     * Generate a completion from the model
     *
     * @param array $messages Conversation history in OpenAI format
     *                       [['role' => 'user', 'content' => '...']]
     * @param array $options Provider-specific options (temperature, max_tokens, etc.)
     * @return array Response with 'content' and 'metadata'
     *               ['content' => '...', 'metadata' => [...]]
     * @throws \Exception If API call fails
     */
    public function complete(array $messages, array $options = []): array;

    /**
     * Stream a completion (for real-time responses)
     *
     * @param array $messages Conversation history
     * @param array $options Provider-specific options
     * @return \Generator Yields chunks of the response
     * @throws \Exception If API call fails
     */
    public function stream(array $messages, array $options = []): \Generator;

    /**
     * Extract structured data using function calling / tool use
     *
     * @param array $messages Conversation history
     * @param array $schema JSON schema for extraction
     * @return array Extracted structured data
     * @throws \Exception If extraction fails
     */
    public function extract(array $messages, array $schema): array;

    /**
     * Check if provider is available and healthy
     *
     * @return bool True if provider can handle requests
     */
    public function health(): bool;

    /**
     * Get provider name
     *
     * @return string Provider identifier (e.g., 'openai', 'anthropic')
     */
    public function getName(): string;

    /**
     * Get available models for this provider
     *
     * @return array List of model identifiers
     */
    public function getModels(): array;

    /**
     * Estimate cost for a request
     *
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @return float Estimated cost in USD
     */
    public function estimateCost(int $inputTokens, int $outputTokens): float;
}
