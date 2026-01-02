<?php
/**
 * Classifier Agent
 *
 * Extracts and classifies user intent, location, topic, and urgency.
 * Uses structured extraction via function calling.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Agents;

use ChatKit\AI\Core\Agent;
use ChatKit\AI\Core\State;

class ClassifierAgent extends Agent {
    /**
     * {@inheritdoc}
     */
    public function execute(State $state): State {
        $userMessage = $state->getLastUserMessage();

        if (empty($userMessage)) {
            $this->log('No user message to classify');
            return $state;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];

        // Extract structured data
        try {
            $extracted = $this->extract($messages, $this->getExtractionSchema());

            // Store extracted data in state
            if (isset($extracted['location']) && !empty($extracted['location'])) {
                $state->set('location', $extracted['location']);
            }

            if (isset($extracted['topic'])) {
                $state->set('topic', $extracted['topic']);
            }

            if (isset($extracted['urgency'])) {
                $state->set('urgency', $extracted['urgency']);
            }

            if (isset($extracted['intent'])) {
                $state->set('intent', $extracted['intent']);
            }

            if (isset($extracted['entities'])) {
                $state->set('entities', $extracted['entities']);
            }

            $this->log('Classification completed', [
                'extracted' => $extracted,
            ]);
        } catch (\Exception $e) {
            $this->log('Classification failed, using defaults', [
                'error' => $e->getMessage(),
            ], 'warning');

            // Set defaults
            $state->set('topic', 'general');
            $state->set('urgency', 'medium');
        }

        return $state;
    }

    /**
     * Get system prompt for classification
     *
     * @return string System prompt
     */
    private function getSystemPrompt(): string {
        return <<<PROMPT
You are an AI assistant helping newcomers to Canada. Your task is to analyze user queries and extract key information.

Extract the following information:
1. **Location**: City or region in Canada (e.g., "Toronto", "Vancouver", "Ontario")
2. **Topic**: Primary category of the query
   - housing: Finding places to live, rental assistance
   - employment: Job search, resume help, work permits
   - healthcare: Medical services, insurance, doctors
   - education: Schools, language classes, credentials recognition
   - entertainment: Events, activities, community programs
   - legal: Immigration, taxes, legal rights
   - transportation: Public transit, driver's license
   - finance: Banking, credit, budgeting
   - general: Other topics or unclear
3. **Intent**: What the user wants to accomplish
4. **Urgency**: How time-sensitive is this (low, medium, high)
5. **Entities**: Other relevant entities (dates, organizations, etc.)

Be accurate and extract only information that is explicitly stated or clearly implied.
PROMPT;
    }

    /**
     * Get JSON schema for extraction
     *
     * @return array JSON schema
     */
    private function getExtractionSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'City or region in Canada mentioned by the user',
                ],
                'topic' => [
                    'type' => 'string',
                    'enum' => [
                        'housing',
                        'employment',
                        'healthcare',
                        'education',
                        'entertainment',
                        'legal',
                        'transportation',
                        'finance',
                        'general',
                    ],
                    'description' => 'Primary topic category',
                ],
                'intent' => [
                    'type' => 'string',
                    'description' => 'What the user wants to accomplish',
                ],
                'urgency' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                    'description' => 'How time-sensitive is this request',
                ],
                'entities' => [
                    'type' => 'object',
                    'description' => 'Other relevant entities',
                    'properties' => [
                        'dates' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'organizations' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'required' => ['topic', 'urgency'],
        ];
    }
}
