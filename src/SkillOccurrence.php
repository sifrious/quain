<?php

namespace Quain\Core;

/**
 * One copy of a skill on disk.
 *
 * Identity belongs to Skill, which is keyed by content. An occurrence is only
 * where that content was found and when it last changed — the facts that tell
 * you which copy is stale, not which skill this is.
 */
class SkillOccurrence
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $path,
        public readonly string $directory,
        public readonly SkillRoot $root,
        public readonly bool $hasScripts,
        public readonly string $instructionsHash,
        public readonly string $treeHash,
        public readonly int $modifiedAt,
    ) {}
}
