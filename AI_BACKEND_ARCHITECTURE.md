# AI Backend Architecture Design

## Executive Summary

This document outlines the architecture for a **model-agnostic, agentic AI backend** that will replace the current OpenAI ChatKit integration. The backend is designed for:
- **Separation of concerns** between UI and AI logic
- **Multi-model support** with provider abstraction
- **Agent-based architecture** for complex workflows
- **Future scalability** to serve multiple frontends (WordPress, mobile app, web app)
- **A/B testing** capabilities for experimentation

---

## Current State Analysis

### OpenAI ChatKit Workflow (From Screenshot)

```
Start
  ↓
Guardrails (validation)
  ↓
A. Extract & Classify (Agent) → Extracts location, classifies query type
  ↓
Set state (stores extracted data)
  ↓
If/else (conditional routing)
  ├─→ No location message (Agent) → Prompts for location
  └─→ B. Verify (Web) (Agent) → Web search verification
        ↓
     Step C - Compose Answer (Agent) → Final response
        ↓
     End
```

**Key Agents:**
1. **Guardrails**: Content moderation, input validation
2. **Extract & Classify**: NLU for intent, entities (location, topic)
3. **No Location Message**: Handles missing required info
4. **Web Verify**: Fact-checking via web search
5. **Compose Answer**: Final response generation

### Current WordPress Plugin

- **Architecture**: Proxy to OpenAI ChatKit sessions API
- **Frontend**: JavaScript widget with REST API calls
- **Backend**: PHP REST endpoints for session creation
- **Limitations**:
  - Tightly coupled to OpenAI
  - No custom business logic
  - Cannot add database lookups (events, offers)
  - Limited control over agent behavior

---

## Proposed Architecture

### Design Principles

1. **Model Agnostic**: Support OpenAI, Anthropic, local models, etc.
2. **Agent-Oriented**: Composable agents for specific tasks
3. **Stateful**: Track conversation context across turns
4. **Extensible**: Easy to add new agents and data sources
5. **Portable**: Can be extracted into separate service
6. **Testable**: Each component independently testable
7. **Observable**: Logging, tracing, metrics for debugging

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                        │
│  (WordPress UI, Future Mobile App, Web App)             │
└────────────────┬────────────────────────────────────────┘
                 │ REST API / GraphQL
                 ↓
┌─────────────────────────────────────────────────────────┐
│              API Gateway (WordPress Plugin)              │
│  - Authentication, Rate Limiting, Request Validation     │
└────────────────┬────────────────────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────────────────────┐
│                   AI Backend Core                        │
│ ┌─────────────────────────────────────────────────────┐ │
│ │            Workflow Orchestrator                    │ │
│ │  (LangGraph state machine for agent coordination)   │ │
│ └──────────┬──────────────────────────────────────────┘ │
│            │                                             │
│ ┌──────────▼──────────────────────────────────────────┐ │
│ │                 Agent Layer                         │ │
│ │  ┌──────────┐ ┌──────────┐ ┌──────────┐           │ │
│ │  │Guardrails│ │Classifier│ │ Events   │  ...      │ │
│ │  │  Agent   │ │  Agent   │ │  Agent   │           │ │
│ │  └──────────┘ └──────────┘ └──────────┘           │ │
│ └─────────────────────────────────────────────────────┘ │
│            │                                             │
│ ┌──────────▼──────────────────────────────────────────┐ │
│ │          Provider Abstraction Layer                 │ │
│ │  ┌─────────┐ ┌──────────┐ ┌──────────┐            │ │
│ │  │ OpenAI  │ │Anthropic │ │  Local   │            │ │
│ │  │Provider │ │ Provider │ │ Provider │            │ │
│ │  └─────────┘ └──────────┘ └──────────┘            │ │
│ └─────────────────────────────────────────────────────┘ │
│            │                                             │
│ ┌──────────▼──────────────────────────────────────────┐ │
│ │              Service Layer                          │ │
│ │  - Event Lookup (MySQL)                             │ │
│ │  - Partner Offers (API/Database)                    │ │
│ │  - Content Search (mycanadianlife.com)              │ │
│ │  - User Management                                  │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
/ai-backend/                          # New directory (can be extracted later)
├── composer.json                     # PHP dependencies (optional for Composer)
├── config/
│   ├── agents.php                   # Agent definitions
│   ├── models.php                   # Model provider configurations
│   ├── workflows.php                # Workflow definitions
│   └── experiments.php              # A/B testing configs
├── src/
│   ├── Core/
│   │   ├── Agent.php                # Base agent interface
│   │   ├── Workflow.php             # Workflow orchestrator
│   │   ├── State.php                # Conversation state manager
│   │   └── Registry.php             # Agent/provider registry
│   ├── Providers/
│   │   ├── ProviderInterface.php   # Model provider interface
│   │   ├── OpenAIProvider.php      # OpenAI implementation
│   │   ├── AnthropicProvider.php   # Claude implementation
│   │   └── LocalProvider.php       # Local model support
│   ├── Agents/
│   │   ├── GuardrailsAgent.php     # Content moderation
│   │   ├── ClassifierAgent.php     # Intent & entity extraction
│   │   ├── EventsAgent.php         # Newcomer events lookup
│   │   ├── OffersAgent.php         # Partner offers
│   │   ├── ContentAgent.php        # MyCanadianLife content
│   │   ├── WebSearchAgent.php      # Web verification
│   │   └── ComposerAgent.php       # Response composition
│   ├── Services/
│   │   ├── EventService.php        # Event database queries
│   │   ├── OfferService.php        # Partner offer logic
│   │   ├── ContentService.php      # Content search/retrieval
│   │   └── UserService.php         # User management
│   ├── Api/
│   │   ├── ChatEndpoint.php        # Main chat API
│   │   ├── ConfigEndpoint.php      # Configuration API
│   │   └── HealthEndpoint.php      # Health checks
│   └── Utils/
│       ├── Logger.php               # Logging utility
│       ├── Cache.php                # Caching layer
│       └── RateLimiter.php          # Rate limiting
├── workflows/
│   └── newcomer-assistant.json      # Workflow definition (JSON)
└── tests/
    ├── Agents/                      # Agent unit tests
    ├── Providers/                   # Provider tests
    └── Integration/                 # End-to-end tests
```

---

## Core Components

### 1. Provider Abstraction Layer

**Purpose**: Allow swapping AI models without changing agent code.

```php
interface ProviderInterface {
    /**
     * Generate a completion from the model
     *
     * @param array $messages Conversation history
     * @param array $options Model-specific options
     * @return array Response with 'content' and 'metadata'
     */
    public function complete(array $messages, array $options = []): array;

    /**
     * Stream a completion (for real-time responses)
     */
    public function stream(array $messages, array $options = []): Generator;

    /**
     * Extract structured data (function calling / tool use)
     */
    public function extract(array $messages, array $schema): array;

    /**
     * Check if provider is available
     */
    public function health(): bool;
}
```

**Implementation Example**:
```php
class OpenAIProvider implements ProviderInterface {
    private string $apiKey;
    private string $model;

    public function complete(array $messages, array $options = []): array {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => $messages,
                ...$options
            ])
        ]);

        return $this->parseResponse($response);
    }
}
```

**Benefits**:
- Switch models via configuration
- A/B test different providers
- Fallback mechanisms (OpenAI → Claude → Local)

---

### 2. Agent Framework

**Purpose**: Reusable components for specific tasks.

```php
abstract class Agent {
    protected ProviderInterface $provider;
    protected array $config;

    abstract public function execute(State $state): State;

    protected function prompt(array $messages, array $options = []): array {
        return $this->provider->complete($messages, $options);
    }
}
```

**Example Agent**:
```php
class ClassifierAgent extends Agent {
    public function execute(State $state): State {
        $userMessage = $state->getLastUserMessage();

        $messages = [
            [
                'role' => 'system',
                'content' => 'Extract: location, topic (housing/employment/healthcare/entertainment/education/legal), urgency'
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ];

        // Use function calling for structured extraction
        $extracted = $this->provider->extract($messages, [
            'type' => 'object',
            'properties' => [
                'location' => ['type' => 'string'],
                'topic' => ['type' => 'string', 'enum' => ['housing', 'employment', ...]],
                'urgency' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']]
            ]
        ]);

        $state->set('location', $extracted['location']);
        $state->set('topic', $extracted['topic']);
        $state->set('urgency', $extracted['urgency']);

        return $state;
    }
}
```

**Agent Types Planned**:

| Agent | Purpose | Data Sources |
|-------|---------|--------------|
| `GuardrailsAgent` | Content moderation, PII detection | Provider (OpenAI Moderation API) |
| `ClassifierAgent` | Intent & entity extraction | Provider (GPT-4 / Claude) |
| `EventsAgent` | Find newcomer events by location | MySQL database |
| `OffersAgent` | Surface partner offers | Partner API / Database |
| `ContentAgent` | Fetch mycanadianlife.com articles | WordPress API / Search |
| `WebSearchAgent` | Verify facts via web search | Google Search API / Bing |
| `ComposerAgent` | Generate final response | Provider (GPT-4 / Claude) |

---

### 3. Workflow Orchestrator

**Purpose**: Coordinate agents based on state.

**Approach**: Use a simplified state machine inspired by LangGraph.

```php
class Workflow {
    private array $nodes = [];
    private array $edges = [];
    private string $entryPoint;

    public function addNode(string $name, callable $fn): self {
        $this->nodes[$name] = $fn;
        return $this;
    }

    public function addEdge(string $from, string $to): self {
        $this->edges[$from][] = $to;
        return $this;
    }

    public function addConditionalEdge(string $from, callable $condition): self {
        $this->edges[$from] = $condition;
        return $this;
    }

    public function run(State $state): State {
        $currentNode = $this->entryPoint;

        while ($currentNode !== 'END') {
            // Execute node
            $fn = $this->nodes[$currentNode];
            $state = $fn($state);

            // Determine next node
            $edge = $this->edges[$currentNode] ?? null;

            if (is_callable($edge)) {
                $currentNode = $edge($state); // Conditional routing
            } elseif (is_array($edge)) {
                $currentNode = $edge[0]; // First edge
            } else {
                $currentNode = $edge ?? 'END';
            }
        }

        return $state;
    }
}
```

**Workflow Definition** (config/workflows.php):
```php
return [
    'newcomer-assistant' => [
        'entry' => 'guardrails',
        'nodes' => [
            'guardrails' => ['agent' => 'GuardrailsAgent'],
            'classify' => ['agent' => 'ClassifierAgent'],
            'check_location' => ['type' => 'conditional'],
            'request_location' => ['agent' => 'LocationPromptAgent'],
            'lookup_events' => ['agent' => 'EventsAgent', 'condition' => 'topic == entertainment'],
            'lookup_offers' => ['agent' => 'OffersAgent'],
            'web_search' => ['agent' => 'WebSearchAgent'],
            'compose' => ['agent' => 'ComposerAgent'],
        ],
        'edges' => [
            'guardrails' => 'classify',
            'classify' => 'check_location',
            'check_location' => function($state) {
                return $state->has('location') ? 'lookup_offers' : 'request_location';
            },
            'request_location' => 'END',
            'lookup_offers' => function($state) {
                return $state->get('topic') === 'entertainment' ? 'lookup_events' : 'web_search';
            },
            'lookup_events' => 'web_search',
            'web_search' => 'compose',
            'compose' => 'END'
        ]
    ]
];
```

---

### 4. State Management

**Purpose**: Track conversation context across agents and turns.

```php
class State {
    private array $data = [];
    private array $messages = [];
    private string $conversationId;

    public function set(string $key, $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    public function addMessage(string $role, string $content): self {
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function getLastUserMessage(): string {
        $userMessages = array_filter($this->messages, fn($m) => $m['role'] === 'user');
        return end($userMessages)['content'] ?? '';
    }

    // Persist to database or cache
    public function save(): void {
        $cache_key = "chatkit_state_{$this->conversationId}";
        set_transient($cache_key, $this->data, 3600); // 1 hour
    }

    public static function load(string $conversationId): self {
        $state = new self();
        $state->conversationId = $conversationId;
        $cache_key = "chatkit_state_{$conversationId}";
        $state->data = get_transient($cache_key) ?: [];
        return $state;
    }
}
```

---

### 5. API Endpoints

**Purpose**: Frontend-backend communication.

#### **POST /wp-json/chatkit/v2/chat**

Request:
```json
{
  "conversation_id": "conv_123",
  "message": "I need help finding housing in Toronto",
  "context": {
    "user_email": "user@example.com",
    "location": "Toronto, ON",
    "newcomer_profile": {
      "arrival_date": "2024-01-15",
      "country_of_origin": "India"
    }
  }
}
```

Response:
```json
{
  "conversation_id": "conv_123",
  "message": "I can help you find housing in Toronto! Here are some resources...",
  "metadata": {
    "agents_used": ["ClassifierAgent", "OffersAgent", "ComposerAgent"],
    "topic": "housing",
    "offers": [
      {
        "partner": "RentBoard",
        "title": "15% off first month",
        "url": "https://..."
      }
    ]
  },
  "suggestions": [
    "Tell me about rental assistance programs",
    "What neighborhoods are good for families?"
  ]
}
```

---

## Configuration & Experimentation

### Model Configuration (config/models.php)

```php
return [
    'providers' => [
        'openai' => [
            'api_key' => getenv('OPENAI_API_KEY') ?: get_option('chatkit_openai_api_key'),
            'models' => [
                'fast' => 'gpt-4o-mini',
                'smart' => 'gpt-4o',
                'legacy' => 'gpt-3.5-turbo'
            ],
            'default' => 'fast'
        ],
        'anthropic' => [
            'api_key' => getenv('ANTHROPIC_API_KEY'),
            'models' => [
                'fast' => 'claude-3-haiku-20240307',
                'smart' => 'claude-3-5-sonnet-20241022'
            ],
            'default' => 'smart'
        ]
    ],

    // Agent-to-provider mapping
    'agent_providers' => [
        'GuardrailsAgent' => 'openai:fast',      // Fast moderation
        'ClassifierAgent' => 'anthropic:smart',  // Better understanding
        'ComposerAgent' => 'openai:smart',       // Better writing
        'WebSearchAgent' => 'anthropic:fast'
    ]
];
```

### A/B Testing (config/experiments.php)

```php
return [
    'experiments' => [
        'composer_model' => [
            'enabled' => true,
            'variants' => [
                'control' => [
                    'weight' => 50,
                    'provider' => 'openai:smart'
                ],
                'treatment' => [
                    'weight' => 50,
                    'provider' => 'anthropic:smart'
                ]
            ],
            'metrics' => ['response_time', 'user_satisfaction']
        ]
    ]
];
```

---

## Migration Path

### Phase 1: Parallel Implementation (Current Sprint)
- Create `ai-backend/` directory structure
- Implement provider abstraction
- Build core agents
- Create new API endpoint `/v2/chat`
- **WordPress Plugin**: Support both ChatKit (v1) and new backend (v2)

### Phase 2: Full Integration
- Migrate all traffic to v2
- Remove ChatKit dependencies
- Add event/offer database lookups
- Implement user registration flow

### Phase 3: Service Extraction
- Move `ai-backend/` to separate repository
- Deploy as standalone service (Docker/Kubernetes)
- WordPress plugin becomes API client
- Build mobile app using same backend

---

## Technology Stack

| Component | Technology | Rationale |
|-----------|-----------|-----------|
| **Language** | PHP 8.1+ | Matches WordPress environment |
| **HTTP Client** | `wp_remote_post` | WordPress native |
| **State Storage** | WordPress Transients → Redis | Start simple, scale later |
| **Caching** | WordPress Object Cache | Built-in support |
| **Logging** | Monolog (optional) | Industry standard |
| **Testing** | PHPUnit | WordPress standard |
| **Documentation** | PHPDoc | Code documentation |

### Alternative Stack (For Service Extraction)

| Component | Technology |
|-----------|-----------|
| **Language** | Python 3.11+ |
| **Framework** | FastAPI |
| **Agent Framework** | LangGraph |
| **State** | Redis + PostgreSQL |
| **Deployment** | Docker + Kubernetes |

---

## Security Considerations

1. **API Key Management**
   - Store in `wp-config.php` or environment variables
   - Never commit to version control
   - Rotate regularly

2. **Input Validation**
   - Sanitize all user inputs
   - Rate limiting (10 req/min for users, 100 for admins)
   - Content moderation via GuardrailsAgent

3. **Data Privacy**
   - PII detection and redaction
   - GDPR compliance (data retention policies)
   - Conversation encryption at rest

4. **Authentication**
   - WordPress nonce for same-origin requests
   - JWT tokens for external clients (future)

---

## Performance Optimization

1. **Caching Strategy**
   - Cache agent responses for identical inputs
   - Cache database queries (events, offers)
   - CDN for static content

2. **Async Processing**
   - Queue long-running agents (web search)
   - Streaming responses for better UX

3. **Database Optimization**
   - Index location fields for event lookups
   - Materialized views for common queries

---

## Monitoring & Observability

1. **Logging**
   ```php
   Logger::info('ClassifierAgent executed', [
       'conversation_id' => $conversationId,
       'extracted' => $extracted,
       'latency_ms' => $latency
   ]);
   ```

2. **Metrics**
   - Agent execution time
   - Provider API latency
   - Error rates
   - Token usage costs

3. **Tracing**
   - Full conversation traces
   - Agent decision paths
   - State transitions

---

## Cost Management

1. **Token Optimization**
   - Use cheaper models for simple tasks (classification → gpt-4o-mini)
   - Compress conversation history (summarization)
   - Cache repeated queries

2. **Budget Alerts**
   - Daily/monthly spend limits
   - Per-user quotas
   - Provider cost comparison

---

## Testing Strategy

1. **Unit Tests**
   - Each agent in isolation
   - Provider implementations
   - State management

2. **Integration Tests**
   - Full workflow execution
   - API endpoint tests
   - Database interactions

3. **E2E Tests**
   - User conversation flows
   - Error handling
   - Edge cases

---

## Documentation

1. **Code Documentation**
   - PHPDoc for all public methods
   - Inline comments for complex logic

2. **Architecture Docs**
   - This document
   - API specifications (OpenAPI/Swagger)
   - Workflow diagrams

3. **Developer Guide**
   - How to add new agents
   - How to add new providers
   - How to modify workflows

---

## Timeline Estimate

| Phase | Tasks | Effort |
|-------|-------|--------|
| **Setup** | Directory structure, base classes | 1-2 days |
| **Providers** | OpenAI, Anthropic abstractions | 2-3 days |
| **Agents** | Core 7 agents | 3-5 days |
| **Workflow** | Orchestration, state management | 2-3 days |
| **API** | Endpoints, WordPress integration | 2-3 days |
| **Testing** | Unit + integration tests | 2-3 days |
| **Documentation** | Code docs, guides | 1-2 days |
| **Total** | | **13-21 days** |

---

## Next Steps

1. **Review & Approval**: Stakeholder review of this architecture
2. **Prototype**: Build minimal viable workflow (Classify → Compose)
3. **Iterate**: Add agents incrementally based on priority
4. **Launch**: Deploy to production with feature flag
5. **Monitor**: Collect metrics and user feedback
6. **Optimize**: Improve based on data

---

## Questions for Discussion

1. **Priority of Agents**: Which agents are most critical for MVP?
2. **Data Sources**: Where is event/offer data currently stored?
3. **Budget**: What's the monthly budget for AI API costs?
4. **Timeline**: When is target launch date?
5. **Mobile App**: Timeline for mobile app development?
6. **Content Integration**: How to access mycanadianlife.com content programmatically?

---

## References

- [LangGraph Documentation](https://python.langchain.com/docs/langgraph)
- [OpenAI Function Calling](https://platform.openai.com/docs/guides/function-calling)
- [Anthropic Claude API](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [Agentic AI Patterns](https://www.deeplearning.ai/short-courses/ai-agentic-design-patterns-with-autogen/)

---

**Document Version**: 1.0
**Last Updated**: 2026-01-02
**Author**: AI Backend Architecture Team
