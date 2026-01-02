<?php
/**
 * Agent and Provider Registry
 *
 * Central registry for managing agents and providers.
 * Handles instantiation, configuration, and retrieval.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Core;

use ChatKit\AI\Providers\ProviderInterface;

class Registry {
    /**
     * @var array Registered providers
     */
    private array $providers = [];

    /**
     * @var array Registered agent factories
     */
    private array $agentFactories = [];

    /**
     * @var array Agent instances cache
     */
    private array $agentInstances = [];

    /**
     * @var array Configuration
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Registry configuration
     */
    public function __construct(array $config = []) {
        $this->config = $config;
    }

    /**
     * Register a provider
     *
     * @param string $name Provider name
     * @param ProviderInterface $provider Provider instance
     * @return self For method chaining
     */
    public function registerProvider(string $name, ProviderInterface $provider): self {
        $this->providers[$name] = $provider;
        return $this;
    }

    /**
     * Get a provider by name
     *
     * @param string $name Provider name
     * @return ProviderInterface|null Provider instance or null
     */
    public function getProvider(string $name): ?ProviderInterface {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all providers
     *
     * @return array All registered providers
     */
    public function getProviders(): array {
        return $this->providers;
    }

    /**
     * Register an agent factory
     *
     * @param string $name Agent name
     * @param callable $factory Factory function that returns Agent instance
     * @return self For method chaining
     */
    public function registerAgentFactory(string $name, callable $factory): self {
        $this->agentFactories[$name] = $factory;
        return $this;
    }

    /**
     * Register an agent class
     *
     * @param string $name Agent name
     * @param string $className Fully qualified agent class name
     * @param string|null $providerName Provider to use (null for default)
     * @param array $config Agent configuration
     * @return self For method chaining
     */
    public function registerAgentClass(
        string $name,
        string $className,
        ?string $providerName = null,
        array $config = []
    ): self {
        $this->agentFactories[$name] = function() use ($className, $providerName, $config) {
            $provider = $this->resolveProvider($providerName);

            if (!class_exists($className)) {
                throw new \RuntimeException("Agent class '{$className}' not found");
            }

            return new $className($provider, $config);
        };

        return $this;
    }

    /**
     * Get an agent instance
     *
     * @param string $name Agent name
     * @param bool $fresh Force new instance (default: use cached)
     * @return Agent|null Agent instance or null
     */
    public function getAgent(string $name, bool $fresh = false): ?Agent {
        // Return cached instance if available
        if (!$fresh && isset($this->agentInstances[$name])) {
            return $this->agentInstances[$name];
        }

        // Get factory
        $factory = $this->agentFactories[$name] ?? null;

        if ($factory === null) {
            return null;
        }

        // Create instance
        $agent = $factory();

        // Cache instance
        if (!$fresh) {
            $this->agentInstances[$name] = $agent;
        }

        return $agent;
    }

    /**
     * Get all registered agent names
     *
     * @return array Agent names
     */
    public function getAgentNames(): array {
        return array_keys($this->agentFactories);
    }

    /**
     * Clear agent instance cache
     *
     * @param string|null $name Specific agent name or null for all
     * @return self For method chaining
     */
    public function clearAgentCache(?string $name = null): self {
        if ($name === null) {
            $this->agentInstances = [];
        } else {
            unset($this->agentInstances[$name]);
        }

        return $this;
    }

    /**
     * Resolve provider from configuration
     *
     * @param string|null $providerName Provider name (null for default)
     * @return ProviderInterface Provider instance
     * @throws \RuntimeException If provider not found
     */
    private function resolveProvider(?string $providerName): ProviderInterface {
        // Use specified provider
        if ($providerName !== null) {
            $provider = $this->getProvider($providerName);

            if ($provider === null) {
                throw new \RuntimeException("Provider '{$providerName}' not found");
            }

            return $provider;
        }

        // Use default provider
        $defaultProvider = $this->config['default_provider'] ?? null;

        if ($defaultProvider === null) {
            throw new \RuntimeException("No default provider configured");
        }

        $provider = $this->getProvider($defaultProvider);

        if ($provider === null) {
            throw new \RuntimeException("Default provider '{$defaultProvider}' not found");
        }

        return $provider;
    }

    /**
     * Load configuration from array
     *
     * @param array $config Configuration array
     * @return self For method chaining
     */
    public function loadConfig(array $config): self {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    public function getConfig(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if provider exists
     *
     * @param string $name Provider name
     * @return bool True if provider is registered
     */
    public function hasProvider(string $name): bool {
        return isset($this->providers[$name]);
    }

    /**
     * Check if agent is registered
     *
     * @param string $name Agent name
     * @return bool True if agent is registered
     */
    public function hasAgent(string $name): bool {
        return isset($this->agentFactories[$name]);
    }
}
