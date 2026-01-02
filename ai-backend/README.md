# ChatKit AI Backend

Model-agnostic, agentic AI backend for the MyCanadianLife newcomer assistant chatbot.

## Overview

This is a custom AI backend that replaces the dependency on OpenAI ChatKit sessions API with a flexible, agent-based architecture. It provides:

- **Model Agnostic**: Easily swap between OpenAI, Anthropic, or local models
- **Agent-Based**: Composable agents for specific tasks (classification, moderation, response generation)
- **Workflow Orchestration**: State machine for complex multi-agent workflows
- **Future-Ready**: Designed to be extracted into a standalone service

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│         Frontend (WordPress, Mobile App, etc.)           │
└────────────────┬────────────────────────────────────────┘
                 │ REST API
                 ↓
┌─────────────────────────────────────────────────────────┐
│              API Gateway (WordPress Plugin)              │
└────────────────┬────────────────────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────────────────────┐
│                   AI Backend Core                        │
│  ┌────────────────────────────────────────────────────┐ │
│  │         Workflow Orchestrator                      │ │
│  │  (State machine for agent coordination)            │ │
│  └──────────┬─────────────────────────────────────────┘ │
│             │                                            │
│  ┌──────────▼────────────────────────────────────────┐  │
│  │              Agent Layer                          │  │
│  │  [Guardrails] [Classifier] [Composer] [Events]   │  │
│  └──────────┬────────────────────────────────────────┘  │
│             │                                            │
│  ┌──────────▼────────────────────────────────────────┐  │
│  │       Provider Abstraction Layer                  │  │
│  │  [OpenAI Provider] [Anthropic Provider] [Local]   │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

## Directory Structure

```
ai-backend/
├── config/
│   ├── agents.php        # Agent definitions and settings
│   ├── models.php        # Model provider configurations
│   └── workflows.php     # Workflow orchestration rules
├── src/
│   ├── Core/
│   │   ├── Agent.php           # Base agent class
│   │   ├── State.php           # Conversation state management
│   │   ├── Workflow.php        # Workflow orchestrator
│   │   └── Registry.php        # Agent/provider registry
│   ├── Providers/
│   │   ├── ProviderInterface.php   # Provider interface
│   │   └── OpenAIProvider.php      # OpenAI implementation
│   ├── Agents/
│   │   ├── ClassifierAgent.php     # Intent & entity extraction
│   │   ├── GuardrailsAgent.php     # Content moderation
│   │   └── ComposerAgent.php       # Response generation
│   ├── Services/            # Business logic (events, offers, etc.)
│   ├── Api/
│   │   └── ChatEndpoint.php    # REST API endpoint
│   └── Utils/               # Utilities
├── workflows/               # Workflow definitions (JSON)
├── tests/                   # Unit and integration tests
└── bootstrap.php            # Initialization
```

## Quick Start

### 1. Configuration

Set your OpenAI API key in `wp-config.php`:

```php
define('CHATKIT_OPENAI_API_KEY', 'sk-proj-...');
```

Or set it in WordPress admin under **Settings → ChatKit**.

### 2. Enable AI Backend

In WordPress admin:
1. Go to **Settings → ChatKit**
2. Check "Use AI Backend (v2)"
3. Save changes

### 3. Test the API

```bash
curl -X POST https://yoursite.com/wp-json/chatkit/v2/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "I need help finding housing in Toronto",
    "context": {
      "location": "Toronto, ON"
    }
  }'
```

### 4. Check Health

```bash
curl https://yoursite.com/wp-json/chatkit/v2/health
```

## Core Concepts

### 1. Providers

Providers are abstractions over AI model APIs. They implement a common interface for:
- `complete()` - Generate completions
- `stream()` - Stream responses
- `extract()` - Structured data extraction
- `health()` - Health checks

**Example: OpenAI Provider**

```php
$provider = new OpenAIProvider('sk-...', 'gpt-4o-mini');
$response = $provider->complete([
    ['role' => 'user', 'content' => 'Hello!']
]);
```

### 2. Agents

Agents are specialized components that perform specific tasks. Each agent:
- Receives conversation state
- Executes its task
- Returns updated state

**Example: Classifier Agent**

```php
$agent = new ClassifierAgent($provider);
$state = new State('conv_123');
$state->addMessage('user', 'I need housing in Toronto');

$state = $agent->execute($state);

echo $state->get('topic');     // 'housing'
echo $state->get('location');  // 'Toronto'
```

### 3. Workflows

Workflows orchestrate multiple agents using a state machine.

**Example Workflow:**

```
Guardrails → Classify → Check Location → Compose → END
```

**Configuration (config/workflows.php):**

```php
'newcomer-assistant' => [
    'entry' => 'guardrails',
    'nodes' => [
        'guardrails' => 'GuardrailsAgent',
        'classify' => 'ClassifierAgent',
        'compose' => 'ComposerAgent',
    ],
    'edges' => [
        'guardrails' => 'classify',
        'classify' => 'compose',
        'compose' => 'END',
    ],
]
```

### 4. State Management

Conversation state is tracked across turns and persisted.

```php
$state = State::create('conv_123');
$state->addMessage('user', 'Hello');
$state->set('location', 'Toronto');
$state->save(); // Persists to WordPress transients
```

## Available Agents

| Agent | Purpose | Input | Output |
|-------|---------|-------|--------|
| **GuardrailsAgent** | Content moderation & safety | User message | `content_safe`, `blocked` |
| **ClassifierAgent** | Intent & entity extraction | User message | `topic`, `location`, `urgency` |
| **ComposerAgent** | Final response generation | All context | Assistant message |

### Future Agents (To Implement)

| Agent | Purpose | Data Source |
|-------|---------|-------------|
| **EventsAgent** | Newcomer events lookup | MySQL database |
| **OffersAgent** | Partner offers | Partner API |
| **ContentAgent** | MyCanadianLife articles | WordPress API |
| **WebSearchAgent** | Web fact-checking | Google/Bing API |

## API Endpoints

### POST /wp-json/chatkit/v2/chat

Process a chat message through the AI backend.

**Request:**
```json
{
  "conversation_id": "conv_123",  // Optional, generated if not provided
  "message": "I need help finding housing in Toronto",
  "context": {
    "location": "Toronto, ON",
    "user_email": "user@example.com",
    "newcomer_profile": {
      "arrival_date": "2024-01-15",
      "country_of_origin": "India"
    }
  },
  "workflow": "newcomer-assistant"  // Optional
}
```

**Response:**
```json
{
  "success": true,
  "conversation_id": "conv_123",
  "message": "I can help you find housing in Toronto! Here are some resources...",
  "metadata": {
    "workflow": "newcomer-assistant",
    "agents_used": ["GuardrailsAgent", "ClassifierAgent", "ComposerAgent"],
    "topic": "housing",
    "location": "Toronto, ON",
    "latency_ms": 1234.56
  },
  "suggestions": [
    "Tell me about rental assistance programs",
    "What neighborhoods are good for families?"
  ]
}
```

### GET /wp-json/chatkit/v2/health

Check backend health.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": 1704153600,
  "providers": {
    "openai": true
  },
  "agents": {
    "GuardrailsAgent": "registered",
    "ClassifierAgent": "registered",
    "ComposerAgent": "registered"
  }
}
```

## Configuration

### Model Configuration (config/models.php)

```php
return [
    'default_provider' => 'openai',

    'providers' => [
        'openai' => [
            'api_key' => CHATKIT_OPENAI_API_KEY,
            'models' => [
                'fast' => 'gpt-4o-mini',
                'smart' => 'gpt-4o',
            ],
            'default_model' => 'fast',
        ],
    ],

    'agent_providers' => [
        'GuardrailsAgent' => 'openai:fast',
        'ClassifierAgent' => 'openai:fast',
        'ComposerAgent' => 'openai:smart',
    ],
];
```

### Agent Configuration (config/agents.php)

```php
return [
    'agents' => [
        'ClassifierAgent' => [
            'class' => 'ChatKit\\AI\\Agents\\ClassifierAgent',
            'provider' => 'openai',
            'config' => [],
        ],
    ],
];
```

## Adding New Agents

### 1. Create Agent Class

```php
namespace ChatKit\AI\Agents;

use ChatKit\AI\Core\Agent;
use ChatKit\AI\Core\State;

class MyCustomAgent extends Agent {
    public function execute(State $state): State {
        // Your logic here
        $data = $this->doSomething($state);

        // Update state
        $state->set('my_data', $data);

        return $state;
    }

    private function doSomething(State $state) {
        // Implementation
    }
}
```

### 2. Register Agent (config/agents.php)

```php
'MyCustomAgent' => [
    'class' => 'ChatKit\\AI\\Agents\\MyCustomAgent',
    'provider' => 'openai',
    'config' => [],
],
```

### 3. Add to Workflow (config/workflows.php)

```php
'nodes' => [
    'my_custom' => 'MyCustomAgent',
    // ...
],
'edges' => [
    'classify' => 'my_custom',
    'my_custom' => 'compose',
],
```

## Adding New Providers

### 1. Implement ProviderInterface

```php
namespace ChatKit\AI\Providers;

class AnthropicProvider implements ProviderInterface {
    public function complete(array $messages, array $options = []): array {
        // Call Anthropic API
    }

    public function extract(array $messages, array $schema): array {
        // Implement tool use
    }

    // ... implement other methods
}
```

### 2. Register Provider (config/models.php)

```php
'providers' => [
    'anthropic' => [
        'api_key' => CHATKIT_ANTHROPIC_API_KEY,
        'models' => [
            'fast' => 'claude-3-haiku-20240307',
            'smart' => 'claude-3-5-sonnet-20241022',
        ],
    ],
],
```

### 3. Use in Agents

```php
'agent_providers' => [
    'ComposerAgent' => 'anthropic:smart',
],
```

## Testing

### Manual Testing

```bash
# Test classification
curl -X POST http://localhost/wp-json/chatkit/v2/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "I need housing in Toronto"}'

# Test guardrails
curl -X POST http://localhost/wp-json/chatkit/v2/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "spam spam spam"}'
```

### Unit Tests (Future)

```bash
cd ai-backend/tests
phpunit
```

## Performance Optimization

### 1. Caching

```php
// In config/models.php
'caching' => [
    'enabled' => true,
    'ttl' => 3600,
    'cache_identical_requests' => true,
],
```

### 2. Model Selection

- Use `gpt-4o-mini` for simple tasks (classification, moderation)
- Use `gpt-4o` for complex tasks (composition, reasoning)

### 3. Token Limits

```php
'limits' => [
    'max_tokens_per_request' => 2000,
    'max_tokens_per_user_per_day' => 50000,
],
```

## Migration from ChatKit

### Phase 1: Parallel Running

1. Enable AI Backend v2 in settings
2. Both ChatKit (v1) and AI Backend (v2) are available
3. Test v2 endpoint thoroughly

### Phase 2: Full Migration

1. Switch all traffic to v2
2. Remove ChatKit dependencies
3. Update frontend to use `/chatkit/v2/chat`

### Phase 3: Service Extraction

1. Move `ai-backend/` to separate repository
2. Deploy as standalone Docker service
3. Update WordPress plugin to call external service

## Future Enhancements

### Planned Features

- [ ] Events database lookup (EventsAgent)
- [ ] Partner offers integration (OffersAgent)
- [ ] MyCanadianLife content search (ContentAgent)
- [ ] Web search verification (WebSearchAgent)
- [ ] User registration workflow
- [ ] Streaming responses
- [ ] Anthropic Claude support
- [ ] A/B testing framework
- [ ] Advanced analytics
- [ ] Cost tracking

### Advanced Workflows

```php
'newcomer-assistant-advanced' => [
    'entry' => 'guardrails',
    'nodes' => [
        'guardrails' => 'GuardrailsAgent',
        'classify' => 'ClassifierAgent',
        'lookup_events' => 'EventsAgent',
        'lookup_offers' => 'OffersAgent',
        'web_search' => 'WebSearchAgent',
        'compose' => 'ComposerAgent',
    ],
    'edges' => [
        'guardrails' => 'classify',
        'classify' => function($state) {
            return $state->get('topic') === 'entertainment'
                ? 'lookup_events'
                : 'lookup_offers';
        },
        'lookup_events' => 'compose',
        'lookup_offers' => 'web_search',
        'web_search' => 'compose',
        'compose' => 'END',
    ],
],
```

## Troubleshooting

### Issue: "OpenAI API key not configured"

**Solution:** Set `CHATKIT_OPENAI_API_KEY` in `wp-config.php` or WordPress admin.

### Issue: "Provider 'openai' not found"

**Solution:** Check that `bootstrap.php` is loaded. Enable `WP_DEBUG` to see errors.

### Issue: "Agent execution failed"

**Solution:** Check WordPress error log. Common causes:
- API rate limits
- Invalid API key
- Network issues

### Enable Debug Logging

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at `wp-content/debug.log`.

## Security

### API Key Storage

**Best Practice:** Store in `wp-config.php`

```php
define('CHATKIT_OPENAI_API_KEY', 'sk-proj-...');
```

**Never** commit API keys to version control.

### Rate Limiting

- 10 requests/minute for regular users
- 100 requests/minute for admins

### Content Moderation

All user input is checked by GuardrailsAgent using OpenAI Moderation API.

## Cost Management

### Monitor Usage

```php
// Check token usage in response metadata
$response['metadata']['composer_tokens'] = [
    'prompt_tokens' => 123,
    'completion_tokens' => 456,
    'total_tokens' => 579,
];
```

### Estimate Costs

- gpt-4o-mini: ~$0.60 per 1M tokens (output)
- gpt-4o: ~$10.00 per 1M tokens (output)

**Tip:** Use gpt-4o-mini for 90% of tasks, gpt-4o only for complex reasoning.

## Support

For questions or issues:
- GitHub Issues: [OpenAI-ChatKit-mcl/issues](https://github.com/Peepul-Tree-Products/OpenAI-ChatKit-mcl/issues)
- Documentation: See `AI_BACKEND_ARCHITECTURE.md` for detailed design

## License

GPL v2 or later (same as WordPress plugin)

---

**Version:** 2.0.0
**Last Updated:** 2026-01-02
