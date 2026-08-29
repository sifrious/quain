<?php

use Quain\Core\SkillFile;
use Quain\Core\SkillFiles;
use Quain\Core\SkillRepository;

beforeEach(function () {
    $this->skillsRoot = sys_get_temp_dir().'/quain-skill-files-'.uniqid();
    mkdir($this->skillsRoot.'/citation-ledger/scripts', 0755, true);
    file_put_contents(
        $this->skillsRoot.'/citation-ledger/SKILL.md',
        "---\nname: citation-ledger\ndescription: test fixture\n---\n\nbody\n",
    );
    file_put_contents(
        $this->skillsRoot.'/citation-ledger/scripts/ledger_check.py',
        "#!/usr/bin/env python3\nprint('ok')\n",
    );

    $this->files = new SkillFiles(new SkillRepository($this->skillsRoot));
});

afterEach(function () {
    if (! isset($this->skillsRoot) || ! is_dir($this->skillsRoot)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->skillsRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        if (! $entry instanceof SplFileInfo) {
            continue;
        }

        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->skillsRoot);
});

it('finds the scripts inside a skill folder', function () {
    $scripts = $this->files->scripts('citation-ledger');

    expect($scripts)->toHaveCount(1)
        ->and($scripts[0]->path)->toBe('scripts/ledger_check.py')
        ->and($scripts[0]->kind)->toBe('script')
        ->and($scripts[0]->bytes)->toBeGreaterThan(0);
});

it('classifies every file in a skill folder', function () {
    $kinds = array_map(fn (SkillFile $file) => $file->kind, $this->files->all('citation-ledger'));

    expect($kinds)->toContain('script')->toContain('skill');
});

it('reads a script by its relative path', function () {
    expect($this->files->read('citation-ledger', 'scripts/ledger_check.py'))
        ->toContain('#!/usr/bin/env python3');
});

it('refuses to read outside the skill folder', function () {
    expect($this->files->read('citation-ledger', '../../settings.json'))->toBeNull()
        ->and($this->files->read('citation-ledger', '../seam-analysis/SKILL.md'))->toBeNull();
});

it('returns nothing for a skill that does not exist', function () {
    expect($this->files->all('no-such-skill'))->toBe([])
        ->and($this->files->directory('no-such-skill'))->toBeNull();
});
