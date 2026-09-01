<?php

namespace Quain\Core;

class SkillRepository
{
    /** @var list<SkillRoot> */
    private readonly array $roots;

    private readonly SkillScanner $scanner;

    /** @var list<Skill>|null */
    private ?array $catalogue = null;

    /**
     * @param  string|list<SkillRoot|array>  $roots  A bare path is the writable
     *                                               library, which is how every
     *                                               caller used to construct this.
     */
    public function __construct(string|array $roots)
    {
        $this->roots = self::normalise($roots);
        $this->scanner = new SkillScanner($this->roots);
    }

    /**
     * The catalogue: one entry per distinct content, so a skill copied into
     * four repositories is read once.
     *
     * @return list<Skill>
     */
    public function all(): array
    {
        return $this->catalogue ??= $this->scanner->skills();
    }

    /**
     * The inventory: every copy on disk, including the duplicates the
     * catalogue collapses.
     *
     * @return list<SkillOccurrence>
     */
    public function occurrences(): array
    {
        return $this->scanner->occurrences();
    }

    /** Ambiguous by construction once names can fork; prefers the library copy. */
    public function find(string $name): ?Skill
    {
        foreach ($this->all() as $skill) {
            if ($skill->name === $name) {
                return $skill;
            }
        }

        return null;
    }

    public function findById(string $id): ?Skill
    {
        foreach ($this->all() as $skill) {
            if ($skill->id() === $id || $skill->treeHash === $id) {
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

    /** Every name known to this machine — the set an agent may reference. */
    public function names(): array
    {
        $names = array_values(array_unique(array_map(fn (Skill $skill) => $skill->name, $this->all())));
        sort($names);

        return $names;
    }

    /**
     * Only what lives in the writable library. Installing answers to this, not
     * to `names()`: a skill vendored in some project must not report a
     * collision against a library that does not have it.
     */
    public function installed(): array
    {
        $target = $this->target();

        if ($target === null) {
            return [];
        }

        $names = [];

        foreach ($this->occurrences() as $occurrence) {
            if ($occurrence->root === $target) {
                $names[] = $occurrence->name;
            }
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    public function target(): ?SkillRoot
    {
        foreach ($this->roots as $root) {
            if ($root->writable()) {
                return $root;
            }
        }

        return null;
    }

    /** @return list<SkillRoot> */
    public function roots(): array
    {
        return $this->roots;
    }

    /** @return list<SkillRoot> */
    private static function normalise(string|array $roots): array
    {
        if (is_string($roots)) {
            return [new SkillRoot($roots)];
        }

        return array_values(array_map(
            fn (SkillRoot|array $root) => $root instanceof SkillRoot ? $root : SkillRoot::fromArray($root),
            $roots,
        ));
    }
}
