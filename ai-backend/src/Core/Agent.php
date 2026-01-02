<?php
/**
 * Base Agent Class
 *
 * Abstract base class for all AI agents. Agents are specialized components
 * that perform specific tasks within the workflow (classification, search, etc.)
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Core;

use ChatKit\AI\Providers\ProviderInterface;

abstract class Agent {
    /**
     * @var ProviderInterface AI model provider
     */
    protected ProviderInterface $provider;

    /**
     * @var array Agent configuration
     */
    protected array $config;

    /**
     * @var string Agent name (for logging/tracing)
     */
    protected string $name;

    /**
     * Constructor
     *
     * @param ProviderInterface $provider AI provider instance
     * @param array $config Agent-specific configuration
     */
    public function __construct(ProviderInterface $provider, array $config = []) {
        $this->provider = $provider;
        $this->config = $config;
        $this->name = $this->getDefaultName();
    }

    /**
     * Execute the agent's task
     *
     * @param State $state Current conversation state
     * @return State Updated state after agent execution
     * @throws \Exception If agent execution fails
     */
    abstract public function execute(State $state): State;

    /**
     * Get agent name
     *
     * @return string Agent identifier
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get default name from class name
     *
     * @return string Default agent name
     */
    protected function getDefaultName(): string {
        $className = (new \ReflectionClass($this))->getShortName();
        return $className;
    }

    /**
     * Call the provider with messages
     *
     * @param array $messages Conversation messages
     * @param array $options Provider options
     * @return array Provider response
     */
    protected function prompt(array $messages, array $options = []): array {
        $startTime = microtime(true);

        try {
            $response = $this->provider->complete($messages, $options);

            $latency = (microtime(true) - $startTime) * 1000; // Convert to ms

            $this->log('Agent executed', [
                'latency_ms' => round($latency, 2),
                'provider' => $this->provider->getName(),
                'input_messages' => count($messages),
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->log('Agent execution failed', [
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Extract structured data using provider
     *
     * @param array $messages Conversation messages
     * @param array $schema JSON schema for extraction
     * @return array Extracted data
     */
    protected function extract(array $messages, array $schema): array {
        return $this->provider->extract($messages, $schema);
    }

    /**
     * Stream a response from the provider
     *
     * @param array $messages Conversation messages
     * @param array $options Provider options
     * @return \Generator Response chunks
     */
    protected function stream(array $messages, array $options = []): \Generator {
        return $this->provider->stream($messages, $options);
    }

    /**
     * Log agent activity
     *
     * @param string $message Log message
     * @param array $context Additional context
     * @param string $level Log level (info, warning, error)
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void {
        $context['agent'] = $this->getName();

        // Use WordPress error_log for now, can be replaced with proper logger
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[%s] [%s] %s: %s',
                strtoupper($level),
                $this->getName(),
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    protected function getConfig(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Validate required configuration keys
     *
     * @param array $required List of required config keys
     * @throws \InvalidArgumentException If required keys are missing
     */
    protected function validateConfig(array $required): void {
        $missing = array_diff($required, array_keys($this->config));

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Agent %s missing required configuration: %s',
                    $this->getName(),
                    implode(', ', $missing)
                )
            );
        }
    }
}
