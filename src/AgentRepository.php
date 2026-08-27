<?php

namespace Quain\Core;

use RuntimeException;

class AgentRepository
{
    public function __construct(private readonly string $root) {}

    public function all(): array
    {
        $agents = array_map(
            fn (string $path) => AgentDefinition::fromFile($path),
            glob($this->root.'/*.md') ?: []
        );

        usort($agents, fn (AgentDefinition $a, AgentDefinition $b) => strcmp($a->name, $b->name));

        return $agents;
    }

    public function exists(string $name): bool
    {
        return file_exists($this->pathFor($name));
    }

    public function write(AgentDefinition $agent, bool $overwrite = false): string
    {
        $path = $this->pathFor($agent->name);

        if (file_exists($path) && ! $overwrite) {
            throw new RuntimeException("Agent [{$agent->name}] already exists at {$path}.");
        }

        if (! is_dir($this->root) && ! mkdir($this->root, 0755, true) && ! is_dir($this->root)) {
            throw new RuntimeException("Unable to create agents directory at {$this->root}.");
        }

        file_put_contents($path, $agent->toMarkdown());

        return $path;
    }

    public function pathFor(string $name): string
    {
        return $this->root.'/'.$name.'.md';
    }
}
