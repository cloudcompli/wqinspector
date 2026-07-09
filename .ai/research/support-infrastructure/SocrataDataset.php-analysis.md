---
source_file: SocrataDataset.php
source_path: src/Support/Dataset/SocrataDataset.php
topic: support-infrastructure
batch_id: batch-002
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 8
confidence_distribution:
  confirmed: 70%
  inferred: 25%
  uncertain: 5%
complexity: moderate
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: Socrata::get
      confidence: confirmed
      source: llm
      kind: external_method
    - name: phpFastCache::get
      confidence: inferred
      source: llm
      kind: external_method
    - name: phpFastCache::set
      confidence: inferred
      source: llm
      kind: external_method
  external_apis:
    - name: Socrata SODA API
      confidence: confirmed
      source: llm
      note: Generic SODA HTTP client wrapper; resource URLs supplied by subclasses
extraction_method: llm
---

# SocrataDataset.php — Socrata SODA Base Class

## Purpose

Infrastructure base class for all Socrata-backed adapters. Wraps the `socrata/soda-php`
`Socrata` client, adds an optional response cache (phpFastCache), chunked pagination, and a
cache-bypass scope. Composes `OptionsTrait`. Subclassed by `CIWQS\ESMR` and
`SMARTS\StormwaterViolations`.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Cache key = `md5(url . json_encode(options))` | 21-24 | [Confirmed] |
| R2 | `get()` returns a cached response when a cache is set AND caching is enabled | 26-34 | [Confirmed] |
| R3 | On cache miss, calls the SODA API and populates the cache when a cache handler is set | 36-41 | [Confirmed] |
| R4 | `getForEachChunk()` paginates in fixed 1000-row windows, invoking the callback per chunk | 46-58 | [Confirmed] |
| R5 | Pagination terminates when `get()` returns a falsy response (empty array) | 51 | [Uncertain] |
| R6 | `withoutCache()` temporarily disables caching for the callback then restores prior state | 80-87 | [Confirmed] |
| R7 | Socrata handler is injected via constructor or `setSocrataHandler()` (type-hinted `Socrata`) | 15-19, 65-68 | [Confirmed] |
| R8 | Cache handler is optional; absent cache means every call hits the API | 30, 38 | [Inferred] |

## Call Relationships

- Called by: `CIWQS\ESMR`, `SMARTS\StormwaterViolations` (via `parent::get()`, `getForEachChunk()`)
- Calls: `Socrata::get()` (external SDK), `$this->_cache->get()/set()` (phpFastCache, external)
- Composes: `OptionsTrait`

## Complexity Assessment

- LOC: 85; 8 public methods; the pagination loop (R4/R5) is the main control-flow risk
- Classified `moderate` — matches golden baseline (rule count 8, within [5,10])

## Security Findings

- No injection here directly, but this class transmits the SOQL `$where` string assembled by
  subclasses (see domain-adapters SEC-1/SEC-3) to the external API without inspection.
- `md5` cache key (line 23) is not a security control (collision-only concern, low risk here).

## Dead Code Signals

- None. All methods reachable; `withoutCache`/`useCache` form a public toggle API.

## Dynamic Pattern Flags

- Callback invocation in `getForEachChunk()` (line 55) and `withoutCache()` (line 85) — statically resolvable closures.

## SME Questions

1. **Pagination termination (R5):** the loop ends when `get()` is falsy. If the SODA SDK
   returns an error object (truthy) instead of `[]` on failure, the loop could spin. Confirm
   soda-php's empty-page and error contract. Also: a dataset whose row count is an exact
   multiple of 1000 triggers one extra empty-page call — confirm that is handled gracefully.
2. `withoutCache()` does not return the callback's return value (line 85 discards it), unlike
   `OptionsTrait::withOptions()` which does — is this intentional asymmetry?
