<?php

use Quain\Core\SkillRepository;
use Quain\Core\SkillRoot;

function writeSkill(string $directory, string $name, string $description, string $body, ?string $script = null): void
{
    mkdir($directory, 0755, true);
    file_put_contents(
        $directory.'/SKILL.md',
        "---\nname: {$name}\ndescription: {$description}\n---\n\n{$body}\n",
    );

    if ($script !== null) {
        mkdir($directory.'/scripts', 0755, true);
        file_put_contents($directory.'/scripts/run.sh', $script);
    }
}

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/quain-skill-scan-'.uniqid();

    // The library copy, and a byte-identical copy vendored into a project.
    writeSkill($this->root.'/library/seam-analysis', 'seam-analysis', 'Price a boundary.', 'Instructions.');
    writeSkill($this->root.'/alpha/.claude/skills/seam-analysis', 'seam-analysis', 'Price a boundary.', 'Instructions.');

    // Same name, different instructions: a fork, not a duplicate.
    writeSkill($this->root.'/beta/.agents/skills/seam-analysis', 'seam-analysis', 'Price a boundary.', 'Different instructions.');

    // Same instructions, different scripts: a variant.
    writeSkill($this->root.'/alpha/.claude/skills/ledger', 'ledger', 'Check citations.', 'Body.', "echo one\n");
    writeSkill($this->root.'/beta/.agents/skills/ledger', 'ledger', 'Check citations.', 'Body.', "echo two\n");

    // A SKILL.md shipped as reference material inside another skill.
    writeSkill($this->root.'/library/bundled', 'bundled', 'Has an example inside.', 'Body.');
    writeSkill($this->root.'/library/bundled/references/example', 'example', 'Not a skill.', 'Body.');

    $this->repository = new SkillRepository([
        new SkillRoot($this->root.'/library', SkillRoot::LIBRARY),
        new SkillRoot($this->root.'/alpha/.claude/skills', SkillRoot::PROJECT, 'alpha'),
        new SkillRoot($this->root.'/beta/.agents/skills', SkillRoot::PROJECT, 'beta'),
    ]);
});

afterEach(fn () => removeTree($this->root));

it('collapses byte-identical copies into one catalogue entry', function () {
    $seam = array_values(array_filter(
        $this->repository->all(),
        fn ($skill) => $skill->name === 'seam-analysis' && $skill->locations() > 1,
    ));

    expect($seam)->toHaveCount(1)
        ->and($seam[0]->locations())->toBe(2);
});

it('keeps every copy in the inventory that the catalogue collapses', function () {
    $paths = array_map(fn ($occurrence) => $occurrence->directory, $this->repository->occurrences());

    expect($paths)->toContain(realpath($this->root.'/library/seam-analysis'))
        ->toContain(realpath($this->root.'/alpha/.claude/skills/seam-analysis'));
});

it('separates a fork that shares a name but not its instructions', function () {
    $named = array_values(array_filter($this->repository->all(), fn ($skill) => $skill->name === 'seam-analysis'));

    expect($named)->toHaveCount(2)
        ->and($named[0]->instructionsHash)->not->toBe($named[1]->instructionsHash)
        ->and($named[0]->id())->not->toBe($named[1]->id());
});

it('separates a variant whose instructions agree but whose scripts do not', function () {
    $ledgers = array_values(array_filter($this->repository->all(), fn ($skill) => $skill->name === 'ledger'));

    expect($ledgers)->toHaveCount(2)
        ->and($ledgers[0]->instructionsHash)->toBe($ledgers[1]->instructionsHash)
        ->and($ledgers[0]->treeHash)->not->toBe($ledgers[1]->treeHash);
});

it('quotes the library copy as the primary one', function () {
    $seam = $this->repository->find('seam-analysis');

    expect($seam->path)->toBe(realpath($this->root.'/library/seam-analysis').'/SKILL.md')
        ->and($seam->occurrences[0]->root->kind)->toBe(SkillRoot::LIBRARY);
});

it('does not mistake reference material inside a skill for a second skill', function () {
    expect($this->repository->names())->not->toContain('example');
});

it('reads one skill twice from overlapping roots as a single copy', function () {
    symlink($this->root.'/library', $this->root.'/mirror');

    $repository = new SkillRepository([
        new SkillRoot($this->root.'/library', SkillRoot::LIBRARY),
        new SkillRoot($this->root.'/mirror', SkillRoot::PROJECT, 'mirror'),
    ]);

    expect($repository->occurrences())->toHaveCount(2);
});

it('counts only the library as installed, so a vendored copy blocks nothing', function () {
    expect($this->repository->installed())->toBe(['bundled', 'seam-analysis'])
        ->and($this->repository->names())->toBe(['bundled', 'ledger', 'seam-analysis']);
});

it('still accepts a bare path as the writable library', function () {
    $repository = new SkillRepository($this->root.'/library');

    expect($repository->names())->toBe(['bundled', 'seam-analysis'])
        ->and($repository->target()?->kind)->toBe(SkillRoot::LIBRARY);
});
