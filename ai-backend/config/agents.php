<?php
/**
 * Agent Configuration
 *
 * Define available agents and their settings.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

return [
    /**
     * Registered agents
     *
     * Format:
     * 'agent_name' => [
     *     'class' => 'Fully\\Qualified\\ClassName',
     *     'provider' => 'provider_name' (optional, uses default if not set),
     *     'config' => [ ... agent-specific configuration ... ]
     * ]
     */
    'agents' => [
        'GuardrailsAgent' => [
            'class' => 'ChatKit\\AI\\Agents\\GuardrailsAgent',
            'provider' => 'openai',
            'config' => [
                'openai_api_key' => defined('CHATKIT_OPENAI_API_KEY')
                    ? CHATKIT_OPENAI_API_KEY
                    : get_option('chatkit_openai_api_key', ''),
                'blocked_message' => get_option(
                    'chatkit_blocked_message',
                    "I'm sorry, but I cannot process this request as it may violate our content policy."
                ),
            ],
        ],

        'ClassifierAgent' => [
            'class' => 'ChatKit\\AI\\Agents\\ClassifierAgent',
            'provider' => 'openai',
            'config' => [],
        ],

        'ComposerAgent' => [
            'class' => 'ChatKit\\AI\\Agents\\ComposerAgent',
            'provider' => 'openai',
            'config' => [
                'temperature' => 0.7,
                'max_tokens' => 800,
            ],
        ],

        // Future agents
        // 'EventsAgent' => [
        //     'class' => 'ChatKit\\AI\\Agents\\EventsAgent',
        //     'provider' => 'openai',
        //     'config' => [
        //         'database_table' => 'chatkit_newcomer_events',
        //         'max_results' => 5,
        //     ],
        // ],

        // 'OffersAgent' => [
        //     'class' => 'ChatKit\\AI\\Agents\\OffersAgent',
        //     'provider' => 'openai',
        //     'config' => [
        //         'partner_api_url' => get_option('chatkit_partner_api_url'),
        //         'max_offers' => 3,
        //     ],
        // ],

        // 'ContentAgent' => [
        //     'class' => 'ChatKit\\AI\\Agents\\ContentAgent',
        //     'provider' => 'openai',
        //     'config' => [
        //         'wordpress_api_url' => 'https://mycanadianlife.com/wp-json/wp/v2',
        //         'max_articles' => 3,
        //     ],
        // ],

        // 'WebSearchAgent' => [
        //     'class' => 'ChatKit\\AI\\Agents\\WebSearchAgent',
        //     'provider' => 'openai',
        //     'config' => [
        //         'search_api_key' => get_option('chatkit_search_api_key'),
        //         'max_results' => 5,
        //     ],
        // ],
    ],

    /**
     * Agent execution settings
     */
    'execution' => [
        'max_iterations' => 100,
        'timeout' => 60, // seconds
        'parallel_execution' => false, // Future feature
    ],
];
