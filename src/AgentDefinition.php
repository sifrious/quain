<?php

namespace Quain\Core;

class AgentDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $body,
        public readonly ?string $model = null,
        public readonly array $tools = [],
        public readonly array $skills = [],
    ) {}

    public static function fromFile(string $path): self
    {
        $matter = Frontmatter::parse(file_get_contents($path));
        $tools = $matter->attributes['tools'] ?? [];

        return new self(
            name: $matter->attributes['name'] ?? basename($path, '.md'),
            description: $matter->attributes['description'] ?? '',
            body: $matter->body,
            model: $matter->attributes['model'] ?? null,
            tools: is_string($tools) ? array_map('trim', explode(',', $tools)) : $tools,
            skills: $matter->attributes['skills'] ?? [],
        );
    }

    public function toMarkdown(): string
    {
        $attributes = ['name' => $this->name, 'description' => $this->description];

        if ($this->tools !== []) {
            $attributes['tools'] = implode(', ', $this->tools);
        }

        if ($this->model !== null) {
            $attributes['model'] = $this->model;
        }

        return Frontmatter::render($attributes, $this->renderBody());
    }

    private function renderBody(): string
    {
        if ($this->skills === []) {
            return $this->body;
        }

        $lines = array_map(fn (string $skill) => "- `{$skill}`", $this->skills);

        return rtrim($this->body)."\n\n## Skills to invoke\n\n".implode("\n", $lines);
    }
}
