# Capability manifest and contract versions

Quain owns portable capability vocabulary and composition metadata. This package does not own Landing UI, Eloquent persistence, HTTP, queues, or execution orchestration.

The current public contract identifier is `quain.capability-manifest.1`. That identifier is independent of the Composer package release and of a capability's own `version`.

## Manifest fields

A capability manifest declares:

| Field | Meaning |
| --- | --- |
| `id` | Stable capability identity. |
| `name` | Human-readable name. |
| `version` | Capability revision. Bump this when the capability's own behavior or declared shape changes. |
| `contractVersion` | Public DTO/schema identifier. Bump this only when the manifest contract itself changes. |
| `inputs` / `outputs` | Named ports (`name`, `type`, `required`). |
| `dependencies` | Other capability identities, optionally versioned or optional. |
| `readiness` | Conditions that must hold before a consumer may select the capability. |
| `parallelism` | Whether independent work may overlap, and an optional max. |
| `approvals` | Who must approve, and when. |
| `compatibility` | Constraints on other identities or ranges. |
| `exitCriteria` | Observable statements that count as done. |
| `vocabulary` | References to Landing-mapped vocabulary, not Eloquent rows. |
| `deprecation` | Optional replacement, first-deprecated version, and removal condition. |

Unknown keys on a supported contract are ignored. That is how additive fields stay consumable without a coordinated upgrade.

## Versioning convention (MME-1222)

`PublishedContracts` is the catalog consumers such as Orbis, Titan, Wardrobe, Logres, and Burdgen can query. A later catalog entry does not require those packages to release together.

| Kind | Rule | Example |
| --- | --- | --- |
| **Compatible** | Same identifier, same field meanings. Silent semantic changes are not allowed. | `quain.capability-manifest.1` remains `1` while only docs or tests change. |
| **Additive** | New optional fields or a documented additive identifier. Readers that understand the parent version ignore unknown keys. | `notes` on `quain.capability-manifest.1.notes`. A v1 reader still loads `id`, ports, and dependencies. |
| **Deprecated** | Still consumed. Metadata names the replacement, the version that first deprecated it, and when it may be removed. | `quain.capability-manifest.0` → replace with `1`; remove when no published consumer still lists `0`. |
| **Breaking** | New identifier required. Unsupported identifiers fail with a machine-readable reason. | `quain.capability-manifest.2` that renames `exitCriteria` → `completion`. Rejection: `{ "code": "unsupported_contract_version", "requested": "quain.capability-manifest.2", "change": "breaking", "supported": [...] }`. |

A Landing compatibility adapter, when one exists, must name the Landing schema it emulates (for example `emulates: landing.values.schema.v1`). This package does not ship that adapter.

Funes/history payload fixtures are out of this repository. The compatibility fixture here is `tests/fixtures/repository-scan.capability-manifest.1.json`.

## Landing vocabulary mapping

Landing source is not in this repository. The inventory below is taken from Linear evidence (MME-846, 849, 891, 909, 910, 960, 1155, 1192, 1193, 1195, 1196, 1245, 1246, 1247, 1441) and is **not** a second catalogue to implement.

Catalogue tickets and generated browse stories are evidence, not this slice's backlog. Value HTTP stories (MME-1245/1246/1247) are behavioral evidence that Landing already persists and renders values; they are not Quain commands.

| Landing record | Evidence in source/stories | Quain concept | Landing-only projection |
| --- | --- | --- | --- |
| `Algorithm` | `App\Models\Algorithm`, `algorithms` table, model + `2026_06_02_190004` migration + seeder + `CodeKnowledgeSeederTest` (4 files). Browse story MME-1192 is latent, not implemented. | `VocabularyReference` kind `algorithm` | Eloquent model, seeder, browse UI |
| `Architecture` | `App\Models\Architecture`, `architectures` table, model + `2026_06_02_190005` migration + seeder. Browse story MME-1193 is latent. | `VocabularyReference` kind `architecture` | Eloquent model, seeder, diagrams/browse UI |
| `DataStructure` | `App\Models\DataStructure` (MME-891, MME-1195). Distinct from Tlon-inspected schemas. | `VocabularyReference` kind `data-structure` | Eloquent model, Tlon observations, browse UI |
| `DesignPattern` | `App\Models\DesignPattern` (MME-909, MME-1196). Distinct from Tlon-observed code instances. | `VocabularyReference` kind `design-pattern` | Eloquent model, code-analysis occurrences, browse UI |
| `Discipline` | `App\Models\Discipline`, `disciplines` table, migration `2026_05_13_113032`. Loaded with Value CRUD. | `VocabularyReference` kind `discipline` | Eloquent model, user proficiency, HTTP/Blade |
| `Value` | `App\Models\Value`, `values` table, migration `2026_05_13_113033`, `ValueController` index/store/destroy (MME-1245/1246/1247). | `VocabularyReference` kind `value` | Eloquent model, HTTP/Blade, values-history (Funes) |
| `Ideal` | `App\Models\Ideal`, `ideals` table, migration `2026_05_13_113034`. Loaded with Value CRUD. | `VocabularyReference` kind `ideal` | Eloquent model, runtime pass/fail, HTTP/Blade |

Stable Landing identities may be cited as `landing:{table}/{key}`. This package does not migrate rows, copy controllers or views, or install capability bundles.

## Dependency order

`DependencyOrder::resolve()` returns a deterministic topological order. Ready nodes are always taken in identity order, so input array order cannot change the result.

Missing required dependencies and cycles are issues on that result (`missing-dependency`, `cyclic-dependency`). They are not skipped, retried, or hidden in control flow. Optional missing dependencies are recorded as `missing-optional-dependency` and do not fail the resolution.

## Bundle verification and install (MME-1176)

`CapabilityBundleVerifier` and `CapabilityBundleInstaller` implement a minimal bundle flow that does not execute bundle contents.

Bundle shape:

- `bundle.json` descriptor with:
  - `identity`
  - `checksum` (bundle-level SHA-256 over verified capability checksums)
  - `provenance` (`source`, optional `reference`, optional `capturedAt`)
  - `capabilities[]` entries (`path`, optional `sha256`)
- capability manifests stored as JSON files referenced by `capabilities[]`.

Verification behavior:

- Missing descriptor, missing files, checksum mismatch, invalid manifests, and unsupported contract versions are returned as issue data.
- Unsupported contract failures preserve machine-readable reasons through `UnsupportedContract::toArray()`.
- Verification returns identity, computed checksum, provenance, verified manifests, and issues.

Install behavior:

- Installation proceeds only when verification has no issues.
- Installed manifests are copied as data into the target directory by capability identity.
- No script, command, queue job, or provider runtime is invoked.

## Read-only consumer queries (MME-1176)

`CapabilityCatalog` offers read-only selection over canonical manifest definitions:

- `all()` → list
- `inspect(id)` → inspect one
- `search(query)` → id/name/vocabulary search
- `compatibleWith(id, range?)` → compatibility query

The API exposes no mutation operations; queries do not alter stored manifest definitions.
