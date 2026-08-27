<?php

namespace Quain\Core;

use Symfony\Component\Yaml\Yaml;

class Frontmatter
{
    public function __construct(
        public readonly array $attributes,
        public readonly string $body,
    ) {}

    public static function parse(string $contents): self
    {
        if (! str_starts_with($contents, "---\n")) {
            return new self([], $contents);
        }

        $end = strpos($contents, "\n---", 4);

        if ($end === false) {
            return new self([], $contents);
        }

        $yaml = substr($contents, 4, $end - 4);
        $body = ltrim(substr($contents, $end + 4), "\n");

        return new self(Yaml::parse($yaml) ?? [], $body);
    }

    public static function render(array $attributes, string $body): string
    {
        $lines = [];

        foreach ($attributes as $key => $value) {
            $lines[] = $key.': '.self::scalar($value);
        }

        return "---\n".implode("\n", $lines)."\n---\n\n".rtrim($body)."\n";
    }

    private static function scalar(mixed $value): string
    {
        if (! is_string($value)) {
            return trim(Yaml::dump($value));
        }

        return self::needsQuoting($value) ? trim(Yaml::dump($value)) : $value;
    }

    private static function needsQuoting(string $value): bool
    {
        return $value === ''
            || str_contains($value, ': ')
            || str_contains($value, "\n")
            || str_ends_with($value, ':')
            || preg_match('/^[-?*&!|>%@`"\'\[{#]/', $value) === 1;
    }
}
