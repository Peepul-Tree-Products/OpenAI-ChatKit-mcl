<?php
/**
 * Composer Agent
 *
 * Generates the final response to the user based on collected information.
 * Combines context, data from other agents, and conversation history.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Agents;

use ChatKit\AI\Core\Agent;
use ChatKit\AI\Core\State;

class ComposerAgent extends Agent {
    /**
     * {@inheritdoc}
     */
    public function execute(State $state): State {
        // Build context from state
        $context = $this->buildContext($state);

        // Build messages for completion
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($context),
            ],
        ];

        // Add conversation history (last 5 turns for context)
        $conversationHistory = array_slice($state->getMessages(), -10);
        $messages = array_merge($messages, $conversationHistory);

        // Generate response
        try {
            $response = $this->prompt($messages, [
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            $content = $response['content'];

            // Add response to state
            $state->addMessage('assistant', $content);

            // Store metadata
            $state->setMetadata('composer_model', $response['metadata']['model'] ?? null);
            $state->setMetadata('composer_tokens', $response['metadata']['usage'] ?? null);

            $this->log('Response composed', [
                'length' => strlen($content),
                'tokens' => $response['metadata']['usage'] ?? [],
            ]);
        } catch (\Exception $e) {
            $this->log('Response composition failed', [
                'error' => $e->getMessage(),
            ], 'error');

            // Fallback response
            $fallbackResponse = $this->getFallbackResponse($state);
            $state->addMessage('assistant', $fallbackResponse);
        }

        return $state;
    }

    /**
     * Build context from state
     *
     * @param State $state Conversation state
     * @return array Context data
     */
    private function buildContext(State $state): array {
        return [
            'location' => $state->get('location'),
            'topic' => $state->get('topic'),
            'urgency' => $state->get('urgency'),
            'intent' => $state->get('intent'),
            'events' => $state->get('events', []),
            'offers' => $state->get('offers', []),
            'content' => $state->get('content', []),
            'web_results' => $state->get('web_results', []),
        ];
    }

    /**
     * Get system prompt
     *
     * @param array $context Context data
     * @return string System prompt
     */
    private function getSystemPrompt(array $context): string {
        $prompt = <<<PROMPT
You are a helpful AI assistant for MyCanadianLife, helping newcomers to Canada with their questions and needs.

**Your Role:**
- Provide accurate, helpful information for newcomers to Canada
- Be empathetic and understanding of their challenges
- Offer practical, actionable advice
- Suggest relevant resources and services
- Be concise but thorough

**Guidelines:**
- Use a friendly, professional tone
- Break complex information into clear steps
- Provide specific examples when helpful
- Always verify important information (immigration, legal, medical)
- Suggest follow-up questions to keep the conversation going
PROMPT;

        // Add context if available
        if (!empty($context['location'])) {
            $prompt .= "\n\n**User Location:** {$context['location']}";
        }

        if (!empty($context['topic'])) {
            $prompt .= "\n**Topic:** {$context['topic']}";
        }

        if (!empty($context['urgency']) && $context['urgency'] === 'high') {
            $prompt .= "\n**Note:** This appears to be a time-sensitive request. Prioritize urgent information.";
        }

        // Add data from other agents
        if (!empty($context['events'])) {
            $prompt .= "\n\n**Relevant Events:**\n";
            foreach ($context['events'] as $event) {
                $prompt .= "- {$event['title']} ({$event['date']}): {$event['description']}\n";
            }
        }

        if (!empty($context['offers'])) {
            $prompt .= "\n\n**Partner Offers:**\n";
            foreach ($context['offers'] as $offer) {
                $prompt .= "- {$offer['partner']}: {$offer['title']} - {$offer['description']}\n";
            }
        }

        if (!empty($context['content'])) {
            $prompt .= "\n\n**Related MyCanadianLife Content:**\n";
            foreach ($context['content'] as $article) {
                $prompt .= "- {$article['title']}: {$article['excerpt']}\n";
            }
        }

        return $prompt;
    }

    /**
     * Get fallback response when composition fails
     *
     * @param State $state Conversation state
     * @return string Fallback response
     */
    private function getFallbackResponse(State $state): string {
        $topic = $state->get('topic', 'general');

        $responses = [
            'housing' => "I understand you're looking for housing information. I'm experiencing technical difficulties at the moment, but I'd recommend visiting MyCanadianLife.com for comprehensive housing resources for newcomers.",
            'employment' => "I can help with employment questions. While I'm experiencing technical issues, you can find job search resources and employment guides at MyCanadianLife.com.",
            'healthcare' => "Healthcare is important! Please visit MyCanadianLife.com for information about healthcare access in Canada, or try asking your question again in a moment.",
            'education' => "For education and credential recognition information, please check MyCanadianLife.com. You can also try your question again shortly.",
            'general' => "I'm here to help newcomers to Canada! I'm experiencing a brief technical issue. Please try your question again, or visit MyCanadianLife.com for resources.",
        ];

        return $responses[$topic] ?? $responses['general'];
    }

    /**
     * Add suggestions to response
     *
     * @param State $state Conversation state
     * @return array Suggested follow-up questions
     */
    private function generateSuggestions(State $state): array {
        $topic = $state->get('topic', 'general');

        $suggestions = [
            'housing' => [
                "Tell me about rental assistance programs",
                "What are average rent prices?",
                "How do I find roommates?",
            ],
            'employment' => [
                "How do I get my credentials recognized?",
                "Where can I find job boards?",
                "Tell me about resume writing",
            ],
            'healthcare' => [
                "How do I get a health card?",
                "Where can I find a family doctor?",
                "What's covered by provincial health insurance?",
            ],
            'education' => [
                "Where can I take language classes?",
                "Tell me about credential assessment",
                "What are the school enrollment requirements?",
            ],
            'general' => [
                "What resources are available for newcomers?",
                "Tell me about community programs",
                "How can I connect with other newcomers?",
            ],
        ];

        return $suggestions[$topic] ?? $suggestions['general'];
    }
}
