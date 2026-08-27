<?php

namespace Quain\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SkillFiles
{
    public function __construct(private readonly SkillRepository $skills) {}

    public function all(string $skill): array
    {
        $directory = $this->directory($skill);

        if ($directory === null) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $files = [];

        foreach ($iterator as $entry) {
            if (! $entry instanceof SplFileInfo || ! $entry->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($directory, '', $entry->getPathname()), '/');

            $files[] = new SkillFile(
                path: $relative,
                absolutePath: $entry->getPathname(),
                bytes: $entry->getSize(),
                kind: $this->classify($relative),
            );
        }

        usort($files, fn (SkillFile $a, SkillFile $b) => [$a->kind, $a->path] <=> [$b->kind, $b->path]);

        return $files;
    }

    public function scripts(string $skill): array
    {
        return array_values(array_filter($this->all($skill), fn (SkillFile $file) => $file->kind === 'script'));
    }

    public function read(string $skill, string $path): ?string
    {
        $directory = $this->directory($skill);

        if ($directory === null) {
            return null;
        }

        $resolved = realpath($directory.'/'.$path);

        if ($resolved === false || ! str_starts_with($resolved, $directory.'/') || ! is_file($resolved)) {
            return null;
        }

        return file_get_contents($resolved);
    }

    public function directory(string $skill): ?string
    {
        $found = $this->skills->find($skill);

        return $found ? dirname($found->path) : null;
    }

    private function classify(string $relative): string
    {
        return match (true) {
            $relative === 'SKILL.md' => 'skill',
            str_starts_with($relative, 'scripts/') => 'script',
            str_starts_with($relative, 'references/') => 'reference',
            default => 'other',
        };
    }
}
