<?php

use Quain\Core\GitHub;
use Quain\Core\SkillRepository;
use Quain\Core\SkillSource;

function fakeGitHub(array $paths, array $meta = []): GitHub
{
    return new class($paths, $meta) extends GitHub
    {
        public function __construct(private array $paths, private array $meta) {}

        public function get(string $endpoint): array
        {
            if (str_contains($endpoint, 'git/trees')) {
                return ['tree' => array_map(fn (string $path) => ['path' => $path], $this->paths)];
            }

            return array_merge([
                'default_branch' => 'main',
                'stargazers_count' => 1234,
                'pushed_at' => '2026-08-26T00:00:00Z',
                'archived' => false,
                'description' => 'stub',
            ], $this->meta);
        }
    };
}

it('extracts skill names from SKILL.md paths', function () {
    $source = new SkillSource(
        fakeGitHub(['skills/alpha/SKILL.md', 'skills/beta/SKILL.md', 'README.md']),
        new SkillRepository(sys_get_temp_dir().'/quain-empty-skills')
    );

    $report = $source->inspect('owner/repo');

    expect($report['skills'])->toBe(['alpha', 'beta'])
        ->and($report['offers'])->toBe(2)
        ->and($report['stars'])->toBe(1234);
});

it('treats a root SKILL.md as the repository itself', function () {
    $source = new SkillSource(
        fakeGitHub(['SKILL.md']),
        new SkillRepository(sys_get_temp_dir().'/quain-empty-skills')
    );

    expect($source->inspect('blader/humanizer')['skills'])->toBe(['humanizer']);
});

it('reports collisions against skills already installed', function () {
    $installed = sys_get_temp_dir().'/quain-collide-'.getmypid();
    @mkdir($installed.'/seam-analysis', 0755, true);
    file_put_contents($installed.'/seam-analysis/SKILL.md', "---\nname: seam-analysis\ndescription: d\n---\n\nbody\n");

    $source = new SkillSource(
        fakeGitHub(['skills/seam-analysis/SKILL.md', 'skills/brand-new/SKILL.md']),
        new SkillRepository($installed)
    );

    $report = $source->inspect('owner/repo');

    expect($report['collisions'])->toBe(['seam-analysis'])
        ->and($report['installed_total'])->toBe(1);

    unlink($installed.'/seam-analysis/SKILL.md');
    rmdir($installed.'/seam-analysis');
    rmdir($installed);
});

it('deduplicates skills that appear more than once in a tree', function () {
    $source = new SkillSource(
        fakeGitHub(['a/qa/SKILL.md', 'b/qa/SKILL.md']),
        new SkillRepository(sys_get_temp_dir().'/quain-empty-skills')
    );

    expect($source->inspect('owner/repo')['skills'])->toBe(['qa']);
});
