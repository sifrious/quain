<?php

namespace Quain\Core;

class SkillSource
{
    public function __construct(
        private readonly GitHub $github,
        private readonly SkillRepository $skills,
    ) {}

    public function inspect(string $repo): array
    {
        $meta = $this->github->get('repos/'.$repo);
        $tree = $this->github->get('repos/'.$repo.'/git/trees/'.$meta['default_branch'].'?recursive=1');

        $offered = $this->offered($tree['tree'] ?? [], $repo);
        $installed = $this->skills->names();
        $collisions = array_values(array_intersect($offered, $installed));

        return [
            'repo' => $repo,
            'stars' => $meta['stargazers_count'] ?? 0,
            'pushed_at' => substr($meta['pushed_at'] ?? '', 0, 10),
            'archived' => (bool) ($meta['archived'] ?? false),
            'description' => $meta['description'] ?? '',
            'offers' => count($offered),
            'skills' => $offered,
            'collisions' => $collisions,
            'installed_total' => count($installed),
        ];
    }

    private function offered(array $tree, string $repo): array
    {
        $names = [];

        foreach ($tree as $node) {
            $path = $node['path'] ?? '';

            if (! str_ends_with($path, 'SKILL.md')) {
                continue;
            }

            $directory = trim(dirname($path), '.');
            $names[] = $directory === '' ? basename($repo) : basename($directory);
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }
}
