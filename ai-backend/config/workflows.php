<?php
/**
 * Workflow Configuration
 *
 * Define agent workflows and orchestration.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

return [
    /**
     * Default workflow to use
     */
    'default_workflow' => 'newcomer-assistant',

    /**
     * Registered workflows
     */
    'workflows' => [
        /**
         * Newcomer Assistant Workflow
         *
         * Replicates the OpenAI ChatKit workflow:
         * 1. Guardrails - Validate content
         * 2. Classify - Extract intent, location, topic
         * 3. Check location - Route based on location availability
         * 4. Compose - Generate final response
         */
        'newcomer-assistant' => [
            'name' => 'newcomer-assistant',
            'description' => 'Main workflow for assisting newcomers to Canada',
            'entry' => 'guardrails',

            'nodes' => [
                'guardrails' => 'GuardrailsAgent',
                'classify' => 'ClassifierAgent',
                'check_blocked' => 'conditional',
                'compose' => 'ComposerAgent',
            ],

            'edges' => [
                // Guardrails → Check if blocked
                'guardrails' => 'check_blocked',

                // If not blocked, classify; otherwise END
                'check_blocked' => function($state) {
                    return $state->get('blocked', false) ? 'END' : 'classify';
                },

                // Classify → Compose
                // Future: Add conditional routing based on topic
                // 'classify' => function($state) {
                //     $topic = $state->get('topic');
                //     if ($topic === 'entertainment') {
                //         return 'lookup_events';
                //     }
                //     return 'compose';
                // },
                'classify' => 'compose',

                // Compose → END
                'compose' => 'END',
            ],
        ],

        /**
         * Future: Advanced workflow with event/offer lookup
         */
        // 'newcomer-assistant-advanced' => [
        //     'name' => 'newcomer-assistant-advanced',
        //     'description' => 'Advanced workflow with events, offers, and content',
        //     'entry' => 'guardrails',
        //
        //     'nodes' => [
        //         'guardrails' => 'GuardrailsAgent',
        //         'classify' => 'ClassifierAgent',
        //         'check_blocked' => 'conditional',
        //         'check_location' => 'conditional',
        //         'request_location' => 'LocationPromptAgent',
        //         'lookup_events' => 'EventsAgent',
        //         'lookup_offers' => 'OffersAgent',
        //         'lookup_content' => 'ContentAgent',
        //         'web_search' => 'WebSearchAgent',
        //         'compose' => 'ComposerAgent',
        //     ],
        //
        //     'edges' => [
        //         'guardrails' => 'check_blocked',
        //         'check_blocked' => function($state) {
        //             return $state->get('blocked', false) ? 'END' : 'classify';
        //         },
        //         'classify' => 'check_location',
        //         'check_location' => function($state) {
        //             return $state->has('location') ? 'lookup_offers' : 'request_location';
        //         },
        //         'request_location' => 'END',
        //         'lookup_offers' => function($state) {
        //             $topic = $state->get('topic');
        //             if ($topic === 'entertainment') {
        //                 return 'lookup_events';
        //             }
        //             return 'lookup_content';
        //         },
        //         'lookup_events' => 'lookup_content',
        //         'lookup_content' => 'web_search',
        //         'web_search' => 'compose',
        //         'compose' => 'END',
        //     ],
        // ],
    ],
];
