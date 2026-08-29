<?php

namespace Quain\Core;

class DependencyOrder
{
    /**
     * @param list<string> $identities
     * @param list<array{code: string, id?: string, missing?: string, cycle?: list<string>}> $issues
     */
    public function __construct(
        public readonly array $identities,
        public readonly array $issues = [],
    ) {}

    /**
     * @param list<CapabilityManifest> $manifests
     */
    public static function resolve(array $manifests): self
    {
        $byId = [];

        foreach ($manifests as $manifest) {
            if (isset($byId[$manifest->id])) {
                return new self([], [[
                    'code' => 'duplicate-identity',
                    'id' => $manifest->id,
                ]]);
            }

            $byId[$manifest->id] = $manifest;
        }

        ksort($byId);

        $issues = [];
        $edges = [];
        $remaining = [];

        foreach ($byId as $id => $manifest) {
            $remaining[$id] = true;
            $edges[$id] = [];

            foreach ($manifest->dependencies as $dependency) {
                if ($dependency->id === '') {
                    continue;
                }

                if (! isset($byId[$dependency->id])) {
                    $issues[] = [
                        'code' => $dependency->optional ? 'missing-optional-dependency' : 'missing-dependency',
                        'id' => $id,
                        'missing' => $dependency->id,
                    ];

                    continue;
                }

                $edges[$id][] = $dependency->id;
            }

            $edges[$id] = array_values(array_unique($edges[$id]));
            sort($edges[$id]);
        }

        $blocking = [];

        foreach ($edges as $id => $dependencies) {
            $blocking[$id] = count($dependencies);
        }

        $dependents = [];

        foreach ($edges as $id => $dependencies) {
            foreach ($dependencies as $dependency) {
                $dependents[$dependency][] = $id;
            }
        }

        $ready = [];

        foreach ($blocking as $id => $count) {
            if ($count === 0) {
                $ready[] = $id;
            }
        }

        sort($ready);

        $order = [];

        while ($ready !== []) {
            $id = array_shift($ready);
            $order[] = $id;
            unset($remaining[$id]);

            foreach ($dependents[$id] ?? [] as $dependent) {
                $blocking[$dependent]--;

                if ($blocking[$dependent] === 0) {
                    $ready[] = $dependent;
                    sort($ready);
                }
            }
        }

        if ($remaining !== []) {
            $issues[] = [
                'code' => 'cyclic-dependency',
                'cycle' => self::cycle(array_keys($remaining), $edges),
            ];
        }

        usort($issues, fn (array $a, array $b) => [$a['code'], $a['id'] ?? '', $a['missing'] ?? ''] <=> [$b['code'], $b['id'] ?? '', $b['missing'] ?? '']);

        $failed = array_values(array_filter(
            $issues,
            fn (array $issue) => in_array($issue['code'], ['missing-dependency', 'cyclic-dependency', 'duplicate-identity'], true),
        ));

        return new self($failed === [] ? $order : [], $issues);
    }

    public function ok(): bool
    {
        foreach ($this->issues as $issue) {
            if (in_array($issue['code'], ['missing-dependency', 'cyclic-dependency', 'duplicate-identity'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $remaining
     * @param array<string, list<string>> $edges
     * @return list<string>
     */
    private static function cycle(array $remaining, array $edges): array
    {
        sort($remaining);

        foreach ($remaining as $start) {
            $path = self::walk($start, $edges, $remaining, []);

            if ($path !== []) {
                return $path;
            }
        }

        return $remaining;
    }

    /**
     * @param array<string, list<string>> $edges
     * @param list<string> $remaining
     * @param list<string> $stack
     * @return list<string>
     */
    private static function walk(string $node, array $edges, array $remaining, array $stack): array
    {
        $index = array_search($node, $stack, true);

        if ($index !== false) {
            $cycle = array_slice($stack, $index);
            $cycle[] = $node;

            return $cycle;
        }

        $stack[] = $node;

        foreach ($edges[$node] ?? [] as $next) {
            if (! in_array($next, $remaining, true)) {
                continue;
            }

            $found = self::walk($next, $edges, $remaining, $stack);

            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }
}
