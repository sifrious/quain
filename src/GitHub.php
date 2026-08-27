<?php

namespace Quain\Core;

use RuntimeException;
use Symfony\Component\Process\Process;

class GitHub
{
    public function get(string $endpoint): array
    {
        $process = new Process(['gh', 'api', $endpoint]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException($this->explain($endpoint, trim($process->getErrorOutput())));
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("GitHub returned an unreadable response for [{$endpoint}].");
        }

        return $decoded;
    }

    private function explain(string $endpoint, string $stderr): string
    {
        return match (true) {
            str_contains($stderr, 'executable file not found'), str_contains($stderr, 'command not found') =>
                'The gh CLI is not installed, so remote sources cannot be inspected.',
            str_contains($stderr, 'Not Found') => "Repository not found for [{$endpoint}]. Check the owner/repo spelling.",
            str_contains($stderr, 'rate limit') => 'GitHub rate limit reached. Run gh auth login to raise it.',
            default => "GitHub request [{$endpoint}] failed: {$stderr}",
        };
    }
}
