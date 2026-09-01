<?php

namespace Quain\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Finds every copy of every skill under a set of roots, and groups the copies
 * by content.
 *
 * Two hashes, because one cannot tell "the same skill, copied" apart from "the
 * same instructions, drifted scripts". `instructionsHash` covers SKILL.md
 * alone; `treeHash` covers every file in the folder. Equal treeHash is a
 * duplicate the catalogue collapses; equal instructionsHash with an unequal
 * treeHash is a variant it must keep, because the difference is real.
 */
class SkillScanner
{
    private const PRUNE = ['.git', 'node_modules', 'vendor', '.venv', 'dist', 'build'];

    /** @param  list<SkillRoot>  $roots */
    public function __construct(private readonly array $roots) {}

    /** @return list<SkillOccurrence> */
    public function occurrences(): array
    {
        $found = [];

        foreach ($this->roots as $root) {
            foreach ($this->manifests($root) as $manifest) {
                $directory = realpath(dirname($manifest));

                // Roots overlap in practice — a symlinked package checkout is
                // reachable from two of them — and a SKILL.md shipped inside
                // another skill's references is not a second skill.
                if ($directory === false || isset($found[$directory]) || $this->nestedIn($directory, $found)) {
                    continue;
                }

                $found[$directory] = $this->describe($manifest, $directory, $root);
            }
        }

        return array_values($found);
    }

    /** @return list<Skill> */
    public function skills(): array
    {
        $grouped = [];

        foreach ($this->occurrences() as $occurrence) {
            $grouped[$occurrence->treeHash][] = $occurrence;
        }

        $skills = [];

        foreach ($grouped as $occurrences) {
            usort(
                $occurrences,
                fn (SkillOccurrence $a, SkillOccurrence $b) => [$a->root->rank(), $a->path] <=> [$b->root->rank(), $b->path]
            );

            $skills[] = Skill::of($occurrences);
        }

        // Name, then provenance: where a name forks, the library's copy is
        // the one a bare-name lookup should answer with.
        usort($skills, fn (Skill $a, Skill $b) => [$a->name, $a->occurrences[0]->root->rank(), $a->treeHash]
            <=> [$b->name, $b->occurrences[0]->root->rank(), $b->treeHash]);

        return $skills;
    }

    /** @return list<string> */
    private function manifests(SkillRoot $root): array
    {
        $base = realpath($root->path);

        if ($base === false || ! is_dir($base)) {
            return [];
        }

        $manifests = $this->search($base, $root->depth);

        // Shallowest first, so a nested SKILL.md meets its parent already
        // accepted and is recognised as belonging to it.
        usort($manifests, fn (string $a, string $b) => [substr_count($a, '/'), $a] <=> [substr_count($b, '/'), $b]);

        return $manifests;
    }

    /** @return list<string> */
    private function search(string $directory, int $depth): array
    {
        $manifests = is_file($directory.'/SKILL.md') ? [$directory.'/SKILL.md'] : [];

        if ($depth <= 0) {
            return $manifests;
        }

        foreach (glob($directory.'/*', GLOB_ONLYDIR) ?: [] as $child) {
            if (in_array(basename($child), self::PRUNE, true)) {
                continue;
            }

            $manifests = array_merge($manifests, $this->search($child, $depth - 1));
        }

        return $manifests;
    }

    /** @param  array<string, SkillOccurrence>  $found */
    private function nestedIn(string $directory, array $found): bool
    {
        foreach (array_keys($found) as $accepted) {
            if (str_starts_with($directory, $accepted.'/')) {
                return true;
            }
        }

        return false;
    }

    private function describe(string $manifest, string $directory, SkillRoot $root): SkillOccurrence
    {
        $contents = (string) file_get_contents($manifest);
        $matter = Frontmatter::parse($contents);

        return new SkillOccurrence(
            name: $matter->attributes['name'] ?? basename($directory),
            description: $matter->attributes['description'] ?? '',
            path: $manifest,
            directory: $directory,
            root: $root,
            hasScripts: is_dir($directory.'/scripts'),
            instructionsHash: hash('sha256', $contents),
            treeHash: $this->treeHash($directory),
            modifiedAt: (int) filemtime($manifest),
        );
    }

    private function treeHash(string $directory): string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $files = [];

        foreach ($iterator as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile()) {
                $files[ltrim(str_replace($directory, '', $entry->getPathname()), '/')] = $entry->getPathname();
            }
        }

        ksort($files);

        $lines = [];

        foreach ($files as $relative => $absolute) {
            $lines[] = $relative."\0".hash_file('sha256', $absolute);
        }

        return hash('sha256', implode("\n", $lines));
    }
}
