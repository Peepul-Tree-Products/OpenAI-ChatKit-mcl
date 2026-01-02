<?php
/**
 * Conversation State
 *
 * Manages conversation state across agents and turns.
 * Tracks messages, extracted data, and metadata.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Core;

class State {
    /**
     * @var string Unique conversation identifier
     */
    private string $conversationId;

    /**
     * @var array Conversation messages
     */
    private array $messages = [];

    /**
     * @var array State data (extracted entities, metadata, etc.)
     */
    private array $data = [];

    /**
     * @var array Agent execution trace
     */
    private array $trace = [];

    /**
     * @var array Metadata (timing, costs, etc.)
     */
    private array $metadata = [];

    /**
     * Constructor
     *
     * @param string $conversationId Unique conversation ID
     */
    public function __construct(string $conversationId) {
        $this->conversationId = $conversationId;
    }

    /**
     * Set a state value
     *
     * @param string $key State key
     * @param mixed $value State value
     * @return self For method chaining
     */
    public function set(string $key, $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get a state value
     *
     * @param string $key State key
     * @param mixed $default Default value if key not found
     * @return mixed State value
     */
    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if state has a key
     *
     * @param string $key State key
     * @return bool True if key exists
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Remove a state value
     *
     * @param string $key State key
     * @return self For method chaining
     */
    public function remove(string $key): self {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * Get all state data
     *
     * @return array All state data
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * Add a message to the conversation
     *
     * @param string $role Message role (user, assistant, system)
     * @param string $content Message content
     * @param array $metadata Optional message metadata
     * @return self For method chaining
     */
    public function addMessage(string $role, string $content, array $metadata = []): self {
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'timestamp' => time(),
        ];
        return $this;
    }

    /**
     * Get all messages
     *
     * @param bool $includeMetadata Include message metadata
     * @return array All messages
     */
    public function getMessages(bool $includeMetadata = false): array {
        if ($includeMetadata) {
            return $this->messages;
        }

        // Return in OpenAI format (role, content only)
        return array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }, $this->messages);
    }

    /**
     * Get the last user message
     *
     * @return string Last user message content
     */
    public function getLastUserMessage(): string {
        $userMessages = array_filter($this->messages, fn($m) => $m['role'] === 'user');

        if (empty($userMessages)) {
            return '';
        }

        $lastMessage = end($userMessages);
        return $lastMessage['content'];
    }

    /**
     * Get the last assistant message
     *
     * @return string Last assistant message content
     */
    public function getLastAssistantMessage(): string {
        $assistantMessages = array_filter($this->messages, fn($m) => $m['role'] === 'assistant');

        if (empty($assistantMessages)) {
            return '';
        }

        $lastMessage = end($assistantMessages);
        return $lastMessage['content'];
    }

    /**
     * Add an agent to the execution trace
     *
     * @param string $agentName Agent name
     * @param array $metadata Agent execution metadata
     * @return self For method chaining
     */
    public function addTrace(string $agentName, array $metadata = []): self {
        $this->trace[] = [
            'agent' => $agentName,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];
        return $this;
    }

    /**
     * Get execution trace
     *
     * @return array Agent execution trace
     */
    public function getTrace(): array {
        return $this->trace;
    }

    /**
     * Set metadata
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self For method chaining
     */
    public function setMetadata(string $key, $value): self {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata
     *
     * @param string|null $key Metadata key (null for all)
     * @return mixed Metadata value or all metadata
     */
    public function getMetadata(?string $key = null) {
        if ($key === null) {
            return $this->metadata;
        }
        return $this->metadata[$key] ?? null;
    }

    /**
     * Get conversation ID
     *
     * @return string Conversation ID
     */
    public function getConversationId(): string {
        return $this->conversationId;
    }

    /**
     * Save state to persistent storage
     *
     * Uses WordPress transients for now, can be upgraded to Redis/DB later
     *
     * @param int $expiration Expiration time in seconds (default: 1 hour)
     * @return bool True if saved successfully
     */
    public function save(int $expiration = 3600): bool {
        $cacheKey = $this->getCacheKey();

        $stateData = [
            'conversation_id' => $this->conversationId,
            'messages' => $this->messages,
            'data' => $this->data,
            'trace' => $this->trace,
            'metadata' => $this->metadata,
            'updated_at' => time(),
        ];

        return set_transient($cacheKey, $stateData, $expiration);
    }

    /**
     * Load state from persistent storage
     *
     * @param string $conversationId Conversation ID
     * @return self|null State instance or null if not found
     */
    public static function load(string $conversationId): ?self {
        $state = new self($conversationId);
        $cacheKey = $state->getCacheKey();

        $stateData = get_transient($cacheKey);

        if ($stateData === false) {
            return null;
        }

        $state->messages = $stateData['messages'] ?? [];
        $state->data = $stateData['data'] ?? [];
        $state->trace = $stateData['trace'] ?? [];
        $state->metadata = $stateData['metadata'] ?? [];

        return $state;
    }

    /**
     * Create a new state or load existing
     *
     * @param string $conversationId Conversation ID
     * @return self State instance
     */
    public static function create(string $conversationId): self {
        $existing = self::load($conversationId);

        if ($existing !== null) {
            return $existing;
        }

        return new self($conversationId);
    }

    /**
     * Delete state from storage
     *
     * @return bool True if deleted successfully
     */
    public function delete(): bool {
        return delete_transient($this->getCacheKey());
    }

    /**
     * Get cache key for this state
     *
     * @return string Cache key
     */
    private function getCacheKey(): string {
        return 'chatkit_state_' . $this->conversationId;
    }

    /**
     * Export state as array
     *
     * @return array State data
     */
    public function toArray(): array {
        return [
            'conversation_id' => $this->conversationId,
            'messages' => $this->messages,
            'data' => $this->data,
            'trace' => $this->trace,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create state from array
     *
     * @param array $data State data
     * @return self State instance
     */
    public static function fromArray(array $data): self {
        $state = new self($data['conversation_id']);
        $state->messages = $data['messages'] ?? [];
        $state->data = $data['data'] ?? [];
        $state->trace = $data['trace'] ?? [];
        $state->metadata = $data['metadata'] ?? [];

        return $state;
    }
}
