<?php
/**
 * AI Backend Bootstrap
 *
 * Initializes the AI backend, registers agents, providers, and workflows.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI;

use ChatKit\AI\Core\Registry;
use ChatKit\AI\Providers\OpenAIProvider;
use ChatKit\AI\Api\ChatEndpoint;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple autoloader for AI backend classes
 */
spl_autoload_register(function ($class) {
    // Only autoload our classes
    if (strpos($class, 'ChatKit\\AI\\') !== 0) {
        return;
    }

    // Convert namespace to file path
    $classPath = str_replace('ChatKit\\AI\\', '', $class);
    $classPath = str_replace('\\', '/', $classPath);
    $filePath = __DIR__ . '/src/' . $classPath . '.php';

    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

/**
 * Bootstrap the AI backend
 *
 * @return array ['registry' => Registry, 'endpoint' => ChatEndpoint]
 */
function bootstrap(): array {
    // Load configurations
    $modelsConfig = require __DIR__ . '/config/models.php';
    $agentsConfig = require __DIR__ . '/config/agents.php';
    $workflowsConfig = require __DIR__ . '/config/workflows.php';

    // Create registry
    $registry = new Registry([
        'default_provider' => $modelsConfig['default_provider'],
    ]);

    // Register providers
    foreach ($modelsConfig['providers'] as $name => $config) {
        if ($name === 'openai') {
            $apiKey = $config['api_key'];

            if (empty($apiKey)) {
                error_log('[ChatKit AI Backend] Warning: OpenAI API key not configured');
                continue;
            }

            $defaultModel = $config['models'][$config['default_model']] ?? 'gpt-4o-mini';
            $provider = new OpenAIProvider($apiKey, $defaultModel);

            $registry->registerProvider($name, $provider);
        }

        // Future: Add other providers (Anthropic, local models, etc.)
    }

    // Register agents
    foreach ($agentsConfig['agents'] as $name => $agentConfig) {
        $className = $agentConfig['class'];
        $providerName = $agentConfig['provider'] ?? null;
        $config = $agentConfig['config'] ?? [];

        $registry->registerAgentClass($name, $className, $providerName, $config);
    }

    // Create API endpoint
    $endpoint = new ChatEndpoint($registry, $workflowsConfig);

    return [
        'registry' => $registry,
        'endpoint' => $endpoint,
    ];
}

/**
 * Initialize the AI backend for WordPress
 */
function init_wordpress(): void {
    $backend = bootstrap();

    // Register REST API routes
    add_action('rest_api_init', function() use ($backend) {
        $backend['endpoint']->register();
    });

    // Store backend instance globally for access
    $GLOBALS['chatkit_ai_backend'] = $backend;
}

// Auto-initialize if called from WordPress
if (defined('ABSPATH')) {
    init_wordpress();
}
