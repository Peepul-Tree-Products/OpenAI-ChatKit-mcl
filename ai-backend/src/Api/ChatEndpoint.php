<?php
/**
 * Chat API Endpoint
 *
 * REST API endpoint for processing chat messages through the AI backend.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Api;

use ChatKit\AI\Core\Registry;
use ChatKit\AI\Core\State;
use ChatKit\AI\Core\Workflow;

class ChatEndpoint {
    /**
     * @var Registry Agent registry
     */
    private Registry $registry;

    /**
     * @var array Configuration
     */
    private array $config;

    /**
     * Constructor
     *
     * @param Registry $registry Agent registry
     * @param array $config Configuration
     */
    public function __construct(Registry $registry, array $config) {
        $this->registry = $registry;
        $this->config = $config;
    }

    /**
     * Register REST API routes
     */
    public function register(): void {
        register_rest_route('chatkit/v2', '/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'handleChat'],
            'permission_callback' => [$this, 'checkPermissions'],
            'args' => [
                'conversation_id' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Unique conversation identifier',
                ],
                'message' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'User message',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'context' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Additional context (location, user info, etc.)',
                ],
                'workflow' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Workflow to use',
                ],
            ],
        ]);

        register_rest_route('chatkit/v2', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'handleHealth'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Handle chat request
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function handleChat(\WP_REST_Request $request): \WP_REST_Response {
        $startTime = microtime(true);

        // Get parameters
        $conversationId = $request->get_param('conversation_id') ?: $this->generateConversationId();
        $message = $request->get_param('message');
        $context = $request->get_param('context') ?: [];
        $workflowName = $request->get_param('workflow') ?: $this->config['default_workflow'];

        // Load or create state
        $state = State::create($conversationId);

        // Add user message
        $state->addMessage('user', $message);

        // Add context to state
        if (isset($context['location'])) {
            $state->set('location', $context['location']);
        }

        if (isset($context['user_email'])) {
            $state->set('user_email', $context['user_email']);
        }

        if (isset($context['newcomer_profile'])) {
            $state->set('newcomer_profile', $context['newcomer_profile']);
        }

        try {
            // Build and run workflow
            $workflowConfig = $this->config['workflows'][$workflowName] ?? null;

            if ($workflowConfig === null) {
                throw new \Exception("Workflow '{$workflowName}' not found");
            }

            $workflow = Workflow::fromConfig($workflowConfig, $this->registry);
            $state = $workflow->run($state);

            // Save state
            $state->save();

            // Build response
            $response = [
                'success' => true,
                'conversation_id' => $conversationId,
                'message' => $state->getLastAssistantMessage(),
                'metadata' => [
                    'workflow' => $workflowName,
                    'agents_used' => array_map(fn($t) => $t['agent'], $state->getTrace()),
                    'topic' => $state->get('topic'),
                    'location' => $state->get('location'),
                    'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ],
            ];

            // Add suggestions if available
            if ($state->has('suggestions')) {
                $response['suggestions'] = $state->get('suggestions');
            }

            // Add offers if available
            if ($state->has('offers')) {
                $response['offers'] = $state->get('offers');
            }

            // Add events if available
            if ($state->has('events')) {
                $response['events'] = $state->get('events');
            }

            return new \WP_REST_Response($response, 200);
        } catch (\Exception $e) {
            $this->logError('Chat request failed', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
            ]);

            return new \WP_REST_Response([
                'success' => false,
                'error' => [
                    'message' => 'An error occurred while processing your request.',
                    'code' => 'processing_error',
                ],
            ], 500);
        }
    }

    /**
     * Handle health check request
     *
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response Response object
     */
    public function handleHealth(\WP_REST_Request $request): \WP_REST_Response {
        $health = [
            'status' => 'healthy',
            'timestamp' => time(),
            'providers' => [],
            'agents' => [],
        ];

        // Check providers
        foreach ($this->registry->getProviders() as $name => $provider) {
            $health['providers'][$name] = $provider->health();
        }

        // Check agents
        foreach ($this->registry->getAgentNames() as $name) {
            $health['agents'][$name] = 'registered';
        }

        $overallHealthy = !in_array(false, $health['providers'], true);

        return new \WP_REST_Response($health, $overallHealthy ? 200 : 503);
    }

    /**
     * Check permissions for chat endpoint
     *
     * @param \WP_REST_Request $request Request object
     * @return bool True if allowed
     */
    public function checkPermissions(\WP_REST_Request $request): bool {
        // Check rate limiting
        if (!$this->checkRateLimit()) {
            return false;
        }

        // Check referrer for security
        $referer = wp_get_referer();
        if ($referer && strpos($referer, home_url()) !== 0) {
            // Request not from same site
            return false;
        }

        return true;
    }

    /**
     * Check rate limiting
     *
     * @return bool True if within limits
     */
    private function checkRateLimit(): bool {
        $userId = get_current_user_id();
        $isAdmin = current_user_can('manage_options');

        // Different limits for admin vs users
        $limit = $isAdmin ? 100 : 10;
        $identifier = $userId ?: $this->getClientIdentifier();

        $transientKey = 'chatkit_v2_rate_limit_' . md5($identifier);
        $requestCount = get_transient($transientKey);

        if ($requestCount === false) {
            // First request in window
            set_transient($transientKey, 1, 60); // 60 seconds
            return true;
        }

        if ($requestCount >= $limit) {
            return false;
        }

        set_transient($transientKey, $requestCount + 1, 60);
        return true;
    }

    /**
     * Get client identifier for rate limiting
     *
     * @return string Client identifier
     */
    private function getClientIdentifier(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return $ip . '|' . $userAgent;
    }

    /**
     * Generate a unique conversation ID
     *
     * @return string Conversation ID
     */
    private function generateConversationId(): string {
        return 'conv_' . uniqid() . '_' . wp_generate_password(8, false);
    }

    /**
     * Log error
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function logError(string $message, array $context = []): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[ChatKit API ERROR] %s: %s',
                $message,
                json_encode($context)
            ));
        }
    }
}
