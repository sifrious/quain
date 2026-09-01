<?php

uses()->in(__DIR__);

function repositoryScanPayload(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/fixtures/repository-scan.capability-manifest.1.json'),
        true,
    );
}

/** Used by more than one suite, and link-aware: a fixture may symlink a root. */
function removeTree(string $root): void
{
    if (! is_dir($root)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        if (! $entry instanceof SplFileInfo) {
            continue;
        }

        match (true) {
            $entry->isLink() => unlink($entry->getPathname()),
            $entry->isDir() => rmdir($entry->getPathname()),
            default => unlink($entry->getPathname()),
        };
    }

    rmdir($root);
}
