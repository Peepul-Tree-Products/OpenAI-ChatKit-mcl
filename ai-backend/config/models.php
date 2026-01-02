<?php
/**
 * Model Provider Configuration
 *
 * Configure AI model providers and their settings.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

return [
    /**
     * Default provider to use when not specified
     */
    'default_provider' => 'openai',

    /**
     * Provider configurations
     */
    'providers' => [
        'openai' => [
            'api_key' => defined('CHATKIT_OPENAI_API_KEY')
                ? CHATKIT_OPENAI_API_KEY
                : get_option('chatkit_openai_api_key', ''),
            'models' => [
                'fast' => 'gpt-4o-mini',
                'smart' => 'gpt-4o',
                'legacy' => 'gpt-3.5-turbo',
            ],
            'default_model' => 'fast',
        ],
        // Future: Add Anthropic, local models, etc.
        // 'anthropic' => [
        //     'api_key' => defined('CHATKIT_ANTHROPIC_API_KEY')
        //         ? CHATKIT_ANTHROPIC_API_KEY
        //         : get_option('chatkit_anthropic_api_key', ''),
        //     'models' => [
        //         'fast' => 'claude-3-haiku-20240307',
        //         'smart' => 'claude-3-5-sonnet-20241022',
        //     ],
        //     'default_model' => 'smart',
        // ],
    ],

    /**
     * Agent-to-provider mapping
     * Allows using different models for different agents
     */
    'agent_providers' => [
        'GuardrailsAgent' => 'openai:fast',      // Fast moderation
        'ClassifierAgent' => 'openai:fast',       // Fast classification
        'ComposerAgent' => 'openai:smart',        // Better writing
        'EventsAgent' => 'openai:fast',           // Simple queries
        'WebSearchAgent' => 'openai:smart',       // Better reasoning
    ],

    /**
     * A/B testing experiments
     */
    'experiments' => [
        // Example: Test different models for composer
        // 'composer_model' => [
        //     'enabled' => false,
        //     'variants' => [
        //         'control' => [
        //             'weight' => 50,
        //             'provider' => 'openai:smart',
        //         ],
        //         'treatment' => [
        //             'weight' => 50,
        //             'provider' => 'anthropic:smart',
        //         ],
        //     ],
        // ],
    ],

    /**
     * Token limits and budgets
     */
    'limits' => [
        'max_tokens_per_request' => 2000,
        'max_tokens_per_user_per_day' => 50000,
        'budget_alert_threshold' => 100.00, // USD per day
    ],

    /**
     * Caching settings
     */
    'caching' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'cache_identical_requests' => true,
    ],
];
