<?php

namespace Quain\Core;

class SkillRepository
{
    public function __construct(private readonly string $root) {}

    public function all(): array
    {
        $skills = [];

        foreach (glob($this->root.'/*/SKILL.md') ?: [] as $path) {
            $matter = Frontmatter::parse(file_get_contents($path));
            $dir = dirname($path);

            $skills[] = new Skill(
                name: $matter->attributes['name'] ?? basename($dir),
                description: $matter->attributes['description'] ?? '',
                path: $path,
                hasScripts: is_dir($dir.'/scripts'),
            );
        }

        usort($skills, fn (Skill $a, Skill $b) => strcmp($a->name, $b->name));

        return $skills;
    }

    public function find(string $name): ?Skill
    {
        foreach ($this->all() as $skill) {
            if ($skill->name === $name) {
                return $skill;
            }
        }

        return null;
    }

    public function contents(string $name): ?string
    {
        $skill = $this->find($name);

        return $skill ? file_get_contents($skill->path) : null;
    }

    public function names(): array
    {
        return array_map(fn (Skill $skill) => $skill->name, $this->all());
    }
}
