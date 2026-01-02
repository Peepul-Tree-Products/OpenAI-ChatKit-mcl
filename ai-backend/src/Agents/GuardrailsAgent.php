<?php
/**
 * Guardrails Agent
 *
 * Performs content moderation and safety checks on user input.
 * Blocks harmful, inappropriate, or policy-violating content.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Agents;

use ChatKit\AI\Core\Agent;
use ChatKit\AI\Core\State;

class GuardrailsAgent extends Agent {
    /**
     * @var array Content categories to moderate
     */
    private const MODERATION_CATEGORIES = [
        'hate',
        'hate/threatening',
        'harassment',
        'harassment/threatening',
        'self-harm',
        'self-harm/intent',
        'self-harm/instructions',
        'sexual',
        'sexual/minors',
        'violence',
        'violence/graphic',
    ];

    /**
     * {@inheritdoc}
     */
    public function execute(State $state): State {
        $userMessage = $state->getLastUserMessage();

        if (empty($userMessage)) {
            $this->log('No message to moderate');
            return $state;
        }

        // Check for prohibited content
        $isSafe = $this->moderateContent($userMessage);

        $state->set('content_safe', $isSafe);

        if (!$isSafe) {
            $state->set('blocked', true);
            $state->set('block_reason', 'content_policy_violation');

            $state->addMessage('assistant', $this->getBlockedMessage());

            $this->log('Content blocked', [
                'message_length' => strlen($userMessage),
            ], 'warning');
        } else {
            $this->log('Content passed moderation');
        }

        return $state;
    }

    /**
     * Moderate content using OpenAI Moderation API
     *
     * @param string $content Content to moderate
     * @return bool True if safe, false if violates policy
     */
    private function moderateContent(string $content): bool {
        // Check message length
        if (strlen($content) > 10000) {
            $this->log('Message too long', ['length' => strlen($content)], 'warning');
            return false;
        }

        // Use OpenAI Moderation API if available
        try {
            $result = $this->callModerationAPI($content);

            if ($result['flagged']) {
                $this->log('Content flagged by moderation API', [
                    'categories' => $result['categories'],
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->log('Moderation API failed, using fallback', [
                'error' => $e->getMessage(),
            ], 'warning');

            // Fallback to basic checks
            return $this->basicContentCheck($content);
        }

        return true;
    }

    /**
     * Call OpenAI Moderation API
     *
     * @param string $content Content to moderate
     * @return array Moderation result
     * @throws \Exception If API call fails
     */
    private function callModerationAPI(string $content): array {
        // Get API key from config
        $apiKey = $this->getConfig('openai_api_key');

        if (!$apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }

        $response = wp_remote_post('https://api.openai.com/v1/moderations', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'body' => json_encode([
                'input' => $content,
            ]),
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['results'][0])) {
            throw new \Exception('Invalid moderation API response');
        }

        $result = $body['results'][0];

        return [
            'flagged' => $result['flagged'] ?? false,
            'categories' => array_filter($result['categories'] ?? []),
        ];
    }

    /**
     * Basic content checks (fallback)
     *
     * @param string $content Content to check
     * @return bool True if safe
     */
    private function basicContentCheck(string $content): bool {
        // Check for spam patterns
        $spamPatterns = [
            '/\b(viagra|cialis|casino|poker)\b/i',
            '/\b(buy now|click here|limited time)\b/i',
            '/http[s]?:\/\/[^\s]+/i', // Block URLs for now (can be relaxed)
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        // Check for excessive caps (> 50% uppercase)
        $upperCount = preg_match_all('/[A-Z]/', $content);
        $letterCount = preg_match_all('/[A-Za-z]/', $content);

        if ($letterCount > 0 && ($upperCount / $letterCount) > 0.5) {
            return false;
        }

        return true;
    }

    /**
     * Get blocked message
     *
     * @return string Message to show when content is blocked
     */
    private function getBlockedMessage(): string {
        return $this->getConfig(
            'blocked_message',
            "I'm sorry, but I cannot process this request as it may violate our content policy. " .
            "Please rephrase your question or contact support if you believe this is an error."
        );
    }

    /**
     * Check for PII (Personally Identifiable Information)
     *
     * @param string $content Content to check
     * @return array Detected PII
     */
    private function detectPII(string $content): array {
        $pii = [];

        // Email addresses
        if (preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $content, $matches)) {
            $pii['emails'] = $matches[0];
        }

        // Phone numbers (North American format)
        if (preg_match_all('/\b(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b/', $content, $matches)) {
            $pii['phones'] = $matches[0];
        }

        // Social Insurance Numbers (Canadian SIN)
        if (preg_match_all('/\b\d{3}[-\s]?\d{3}[-\s]?\d{3}\b/', $content, $matches)) {
            $pii['potential_sin'] = $matches[0];
        }

        return $pii;
    }
}
