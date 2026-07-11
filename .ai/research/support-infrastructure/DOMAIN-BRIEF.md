---
topic: support-infrastructure
files_analyzed: 5
batches:
  - batch-002
generated_at: 2026-07-09T00:00:00Z
rules_extracted: 20
confidence_distribution:
  confirmed: 79%
  inferred: 15%
  uncertain: 6%
complexity_distribution:
  low: 4
  medium: 1
  high: 0
  very_high: 0
---

# Support Infrastructure

## Overview

Five shared classes/trait forming the two dataset hierarchies that every domain adapter builds
on. `OptionsTrait` is the cross-cutting option bag mixed into both roots. `FileDataset` is the
file-backed root (in-memory filtering), specialized by `CsvDataset` (CSV parse) and
`HtmlDataset` (HTML/XML parse). `SocrataDataset` is the API-backed root — SODA client wrapper
with optional phpFastCache caching and 1000-row chunked pagination. This layer contains no
business domain logic; it is pure retrieval/caching/parsing plumbing.

## Business Rules

| Rule ID | Description | Source File | Confidence |
|---------|-------------|-------------|------------|
| SI-R1 | Option bag: set / merge / scoped save-restore semantics | OptionsTrait.php:9-27 | [Confirmed] |
| SI-R2 | File filter: match ALL filter keys, array-coerced membership | FileDataset.php:25-47 | [Confirmed] |
| SI-R3 | Scalar path normalized to array in FileDataset constructor | FileDataset.php:12-18 | [Confirmed] |
| SI-R4 | CSV: header-normalized column keys, row hydration, silent skip on unreadable file | CsvDataset.php:12-24 | [Confirmed] |
| SI-R5 | HTML: fixed DOM-path extraction via simplexml, positional row keying | HtmlDataset.php:13-24 | [Confirmed] |
| SI-R6 | Socrata cache key = md5(url + json_encode(options)) | SocrataDataset.php:21-24 | [Confirmed] |
| SI-R7 | Socrata get(): cache-first, API-fallback, cache-populate | SocrataDataset.php:26-44 | [Confirmed] |
| SI-R8 | Chunked pagination in fixed 1000-row windows, terminate on falsy response | SocrataDataset.php:46-58 | [Uncertain] |
| SI-R9 | withoutCache(): temporarily disable + restore cache flag | SocrataDataset.php:80-87 | [Confirmed] |

## Architecture

Two inheritance trees sharing one trait:

```
OptionsTrait (mixin)
├── FileDataset ──┬── CsvDataset ──(OCPW\ParameterDataset)
│                 └── HtmlDataset ─(SMARTS\ParameterDataset)
└── SocrataDataset ──(CIWQS\ESMR, SMARTS\StormwaterViolations)
```

Integration points:
- **phpFastCache** (external): file-backed response cache in `SocrataDataset`.
- **socrata/soda-php** (external): the `Socrata` HTTP client injected into `SocrataDataset`.
- **libxml/SimpleXML** (PHP built-in): HTML parsing in `HtmlDataset`.

Data flow: constructor parses files (file tree) OR options assemble a query (Socrata tree) →
`getData()`/`get()` retrieval → returned to domain adapters for aggregation.

## Complexity & Modernization Signals

- Four of five files are trivially simple (25-47 LOC); `SocrataDataset` (85 LOC) is the only
  moderate one and holds the caching + pagination risk.
- Modernization: pagination termination (SI-R8) is contract-fragile; HtmlDataset's XXE surface
  and CsvDataset's silent-skip are hardening targets. The two file-parser subclasses could be
  unified behind a single configurable parser.

## Key Findings

1. **[Uncertain] Pagination termination depends on the external SDK's empty/error contract.**
   `getForEachChunk` (SocrataDataset.php:51) loops until `get()` is falsy. If the SODA SDK
   returns a truthy error object on failure, or on an exact-multiple-of-1000 result set, the
   loop behavior needs verification. Carried forward from bootstrap uncertain_items.
2. **[Uncertain] XXE surface in HtmlDataset** (`simplexml_load_file`, HtmlDataset.php:12) —
   version-dependent; a hardening target if HTML input provenance is not fully trusted.
3. **[Inferred] OptionsTrait is the natural sanitization chokepoint.** Every injection-relevant
   value passes through `setOption`/`setOptions`; adding validation here would neutralize the
   domain-adapter SOQL injection with a single change surface.
4. **[Confirmed] No database access anywhere in the layer** — retrieval is HTTP (Socrata) or
   local-file only, consistent with the domain profile's `databases: []`.

## Dead Code Candidates

No dead code candidates identified in this topic.

## Call Graph Gaps

### CG-002: phpFastCache handler methods
- **Source:** src/Support/Dataset/SocrataDataset.php:31, 39 → `$this->_cache->get()/set()`
- **Confidence:** [Inferred]
- **Type:** external_service
- **Evidence:** The cache handler is an injected phpFastCache instance (external package);
  its `get`/`set` semantics (TTL, serialization) are defined outside this repo.
- **Possible targets:** phpfastcache/phpfastcache cache driver

## SME Questions

1. Confirm soda-php empty-page and error-response contract for `getForEachChunk` termination
   (SI-R8) — including the exact-multiple-of-1000 edge case.
2. Is `withoutCache` intentionally discarding the callback return value (unlike `withOptions`)?
3. Are SMARTS HTML exports trusted-source only? (Determines XXE exposure.)
4. Should `CsvDataset` raise on unreadable files instead of silently yielding empty data?

## Source File Index

| File | Purpose | Tier | Complexity | Analysis Doc |
|------|---------|------|------------|--------------|
| src/Support/Dataset/SocrataDataset.php | SODA client wrapper, cache, pagination | deep | moderate | SocrataDataset.php-analysis.md |
| src/Support/Dataset/FileDataset.php | File-backed root, in-memory filter | deep | simple | FileDataset.php-analysis.md |
| src/Support/Dataset/CsvDataset.php | CSV parser | deep | simple | CsvDataset.php-analysis.md |
| src/Support/Dataset/HtmlDataset.php | HTML/XML parser | deep | simple | HtmlDataset.php-analysis.md |
| src/Support/Dataset/OptionsTrait.php | Option-bag mixin | deep | simple | OptionsTrait.php-analysis.md |
