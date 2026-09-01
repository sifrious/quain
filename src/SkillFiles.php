<?php

namespace Quain\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SkillFiles
{
    public function __construct(private readonly SkillRepository $skills) {}

    public function all(string $skill, ?string $at = null): array
    {
        $directory = $this->directory($skill, $at);

        return $directory === null ? [] : $this->inDirectory($directory);
    }

    /**
     * Files of one copy, addressed by where it is rather than what it is
     * called. A forked name resolves to the wrong folder; a directory cannot.
     *
     * @return list<SkillFile>
     */
    public function inDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
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

    public function scripts(string $skill, ?string $at = null): array
    {
        return array_values(array_filter($this->all($skill, $at), fn (SkillFile $file) => $file->kind === 'script'));
    }

    public function read(string $skill, string $path, ?string $at = null): ?string
    {
        $directory = $this->directory($skill, $at);

        return $directory === null ? null : $this->readIn($directory, $path);
    }

    public function readIn(string $directory, string $path): ?string
    {
        $resolved = realpath($directory.'/'.$path);

        if ($resolved === false || ! str_starts_with($resolved, $directory.'/') || ! is_file($resolved)) {
            return null;
        }

        return file_get_contents($resolved);
    }

    /**
     * `$at` picks one copy of a skill that exists in several places; without
     * it the primary copy answers, which is the library's when there is one.
     */
    public function directory(string $skill, ?string $at = null): ?string
    {
        $found = $this->skills->find($skill);

        if ($found === null) {
            return null;
        }

        if ($at === null) {
            return dirname($found->path);
        }

        foreach ($found->occurrences as $occurrence) {
            if ($occurrence->directory === $at) {
                return $occurrence->directory;
            }
        }

        return null;
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
