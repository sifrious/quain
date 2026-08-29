# quain/core

The portable half of Quain — the capability catalogue. Reads skills and agent definitions from disk, reports what a skill folder holds, inspects a remote capability source before anything is installed, and owns versioned capability manifests plus vocabulary references.

No framework. Requires PHP 8.3, `symfony/yaml`, and `symfony/process`; nothing else. That constraint is the point: Quain is meant to be runtime-portable, so the core cannot depend on the runtime that happens to consume it today.

```
CapabilityManifest   identity, contract version, ports, dependencies, readiness, parallelism, approvals, compatibility, exit criteria, vocabulary
PublishedContracts   supported contract versions; additive vs deprecated vs breaking
DependencyOrder      deterministic topological order; missing deps and cycles as data
VocabularyReference  algorithm / architecture / data-structure / design-pattern / discipline / value / ideal
SkillRepository      skills on disk, by name, with frontmatter
SkillFiles           what a skill folder holds — scripts, references — and safe reads inside it
AgentRepository      agent definitions, read and written
AgentDefinition      an agent as data: name, trigger, model, tools, skills
Frontmatter          YAML frontmatter parse and render, quoting only when required
SkillSource          what a GitHub repo would add, and what it collides with
GitHub               gh-CLI transport, so requests inherit existing auth
```

Contract versioning and the Landing-vocabulary mapping live in [docs/capability-manifest.md](docs/capability-manifest.md).

## Against the ticketed contract surface

Honest mapping.

| Ticket | State |
| --- | --- |
| QUAIN-001 versioned portable capability manifest | **This slice.** `CapabilityManifest` declares identity, `contractVersion` (separate from package release and from capability `version`), inputs, outputs, dependencies, readiness, parallelism, approvals, compatibility, and exit criteria. |
| QUAIN-002 distinct capability kinds | **Adjacent, not satisfied.** `SkillFile::kind` classifies files *inside* a skill (skill/script/reference/other). The ticket means skills vs tools vs rules vs prompts vs templates. |
| QUAIN-003 transitive dependency resolution | **This slice.** `DependencyOrder` is deterministic; missing dependencies and cycles are issues, not hidden control flow. |
| QUAIN-004 compatibility and readiness validation | **Declared, not executed.** Manifests can name readiness and compatibility; nothing here selects or blocks a run. |
| QUAIN-005 composition without hidden global behavior | **Partial.** Dependencies and parallelism are explicit on the manifest. No always-on composition engine. |
| QUAIN-006 install and distribute signed bundles | **Partial, one half.** `SkillSource::inspect()` reports what a remote source offers without executing or installing it. No install, no signing, no verification. |
| QUAIN-007 import Landing knowledge-catalog semantics | **Mapped, not migrated.** Seven Landing catalogue records become `VocabularyReference` kinds or stay Landing-only projections. Eloquent models, controllers, and browse UI are not copied. |
| QUAIN-008 read-only capability catalogue for Orbis and Titan | **Partial.** A read-only catalogue exists, but only over Claude Code's on-disk layout, and no Orbis or Titan consumer exists to serve. |

## Consumer

`stacks` (`../stacks`) — a Laravel Zero CLI and MCP server that browses and authors capabilities. It is a consumer of this package, not part of it.

## Tests

```
composer install && vendor/bin/pest
```
