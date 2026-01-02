<?php
/**
 * Workflow Orchestrator
 *
 * Coordinates agent execution based on a state machine.
 * Inspired by LangGraph for flexible agent workflows.
 *
 * @package ChatKit_AI_Backend
 * @since 2.0.0
 */

namespace ChatKit\AI\Core;

class Workflow {
    /**
     * @var array Workflow nodes (agent names or callables)
     */
    private array $nodes = [];

    /**
     * @var array Workflow edges (node transitions)
     */
    private array $edges = [];

    /**
     * @var string Entry point node name
     */
    private string $entryPoint = 'START';

    /**
     * @var string Exit node name
     */
    private string $exitPoint = 'END';

    /**
     * @var string Workflow name
     */
    private string $name;

    /**
     * @var Registry Agent registry
     */
    private Registry $registry;

    /**
     * Constructor
     *
     * @param string $name Workflow name
     * @param Registry $registry Agent registry
     */
    public function __construct(string $name, Registry $registry) {
        $this->name = $name;
        $this->registry = $registry;
    }

    /**
     * Add a node to the workflow
     *
     * @param string $name Node name
     * @param callable|string $action Agent name or callable
     * @return self For method chaining
     */
    public function addNode(string $name, $action): self {
        $this->nodes[$name] = $action;
        return $this;
    }

    /**
     * Add a simple edge (A → B)
     *
     * @param string $from Source node
     * @param string $to Destination node
     * @return self For method chaining
     */
    public function addEdge(string $from, string $to): self {
        $this->edges[$from] = $to;
        return $this;
    }

    /**
     * Add a conditional edge with routing function
     *
     * @param string $from Source node
     * @param callable $condition Function that returns next node name
     * @return self For method chaining
     */
    public function addConditionalEdge(string $from, callable $condition): self {
        $this->edges[$from] = $condition;
        return $this;
    }

    /**
     * Set entry point
     *
     * @param string $nodeName Entry node name
     * @return self For method chaining
     */
    public function setEntryPoint(string $nodeName): self {
        $this->entryPoint = $nodeName;
        return $this;
    }

    /**
     * Execute the workflow
     *
     * @param State $state Initial state
     * @param int $maxIterations Maximum iterations to prevent infinite loops
     * @return State Final state
     * @throws \Exception If workflow execution fails
     */
    public function run(State $state, int $maxIterations = 100): State {
        $currentNode = $this->entryPoint;
        $iterations = 0;

        $state->setMetadata('workflow_name', $this->name);
        $state->setMetadata('workflow_started_at', time());

        while ($currentNode !== $this->exitPoint && $iterations < $maxIterations) {
            $iterations++;

            // Execute current node
            $action = $this->nodes[$currentNode] ?? null;

            if ($action === null) {
                throw new \RuntimeException("Node '{$currentNode}' not found in workflow");
            }

            try {
                $state = $this->executeNode($currentNode, $action, $state);
            } catch (\Exception $e) {
                $this->logError("Node execution failed", [
                    'node' => $currentNode,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }

            // Determine next node
            $edge = $this->edges[$currentNode] ?? null;

            if ($edge === null) {
                // No edge defined, assume END
                $currentNode = $this->exitPoint;
            } elseif (is_callable($edge)) {
                // Conditional edge
                $currentNode = $edge($state);

                $this->log("Conditional routing", [
                    'from' => $currentNode,
                    'to' => $currentNode,
                    'condition_result' => true,
                ]);
            } elseif (is_array($edge)) {
                // Multiple edges (take first for now)
                $currentNode = $edge[0];
            } else {
                // Simple edge
                $currentNode = $edge;
            }

            // Safety check
            if (!is_string($currentNode)) {
                throw new \RuntimeException("Invalid next node: expected string, got " . gettype($currentNode));
            }
        }

        if ($iterations >= $maxIterations) {
            throw new \RuntimeException("Workflow exceeded maximum iterations ({$maxIterations})");
        }

        $state->setMetadata('workflow_completed_at', time());
        $state->setMetadata('workflow_iterations', $iterations);

        return $state;
    }

    /**
     * Execute a single node
     *
     * @param string $nodeName Node name
     * @param callable|string $action Agent name or callable
     * @param State $state Current state
     * @return State Updated state
     */
    private function executeNode(string $nodeName, $action, State $state): State {
        $startTime = microtime(true);

        $this->log("Executing node", ['node' => $nodeName]);

        if (is_string($action)) {
            // Action is an agent name
            $agent = $this->registry->getAgent($action);

            if ($agent === null) {
                throw new \RuntimeException("Agent '{$action}' not found in registry");
            }

            $state = $agent->execute($state);
            $state->addTrace($agent->getName(), [
                'node' => $nodeName,
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);
        } elseif (is_callable($action)) {
            // Action is a callable
            $state = $action($state);
            $state->addTrace($nodeName, [
                'type' => 'callable',
                'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);
        } else {
            throw new \RuntimeException("Invalid node action type: " . gettype($action));
        }

        $this->log("Node completed", [
            'node' => $nodeName,
            'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        return $state;
    }

    /**
     * Get workflow nodes
     *
     * @return array Node definitions
     */
    public function getNodes(): array {
        return $this->nodes;
    }

    /**
     * Get workflow edges
     *
     * @return array Edge definitions
     */
    public function getEdges(): array {
        return $this->edges;
    }

    /**
     * Get workflow name
     *
     * @return string Workflow name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Log workflow activity
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function log(string $message, array $context = []): void {
        $context['workflow'] = $this->name;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Workflow] [%s] %s: %s',
                $this->name,
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Log workflow errors
     *
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function logError(string $message, array $context = []): void {
        $context['workflow'] = $this->name;

        error_log(sprintf(
            '[Workflow ERROR] [%s] %s: %s',
            $this->name,
            $message,
            json_encode($context)
        ));
    }

    /**
     * Build workflow from configuration array
     *
     * @param array $config Workflow configuration
     * @param Registry $registry Agent registry
     * @return self Workflow instance
     */
    public static function fromConfig(array $config, Registry $registry): self {
        $name = $config['name'] ?? 'unnamed_workflow';
        $workflow = new self($name, $registry);

        // Set entry point
        if (isset($config['entry'])) {
            $workflow->setEntryPoint($config['entry']);
        }

        // Add nodes
        foreach ($config['nodes'] ?? [] as $nodeName => $nodeConfig) {
            if (is_string($nodeConfig)) {
                // Simple agent name
                $workflow->addNode($nodeName, $nodeConfig);
            } elseif (isset($nodeConfig['agent'])) {
                // Node with agent specification
                $workflow->addNode($nodeName, $nodeConfig['agent']);
            } elseif (isset($nodeConfig['callable'])) {
                // Node with callable
                $workflow->addNode($nodeName, $nodeConfig['callable']);
            }
        }

        // Add edges
        foreach ($config['edges'] ?? [] as $from => $to) {
            if (is_callable($to)) {
                $workflow->addConditionalEdge($from, $to);
            } else {
                $workflow->addEdge($from, $to);
            }
        }

        return $workflow;
    }

    /**
     * Export workflow to configuration array
     *
     * @return array Workflow configuration
     */
    public function toConfig(): array {
        return [
            'name' => $this->name,
            'entry' => $this->entryPoint,
            'nodes' => array_map(function($action) {
                if (is_string($action)) {
                    return ['agent' => $action];
                }
                return ['type' => 'callable'];
            }, $this->nodes),
            'edges' => $this->edges,
        ];
    }
}
