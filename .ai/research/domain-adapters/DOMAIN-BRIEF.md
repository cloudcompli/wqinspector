---
topic: domain-adapters
files_analyzed: 4
batches:
  - batch-001
generated_at: 2026-07-09T00:00:00Z
rules_extracted: 27
confidence_distribution:
  confirmed: 71%
  inferred: 21%
  uncertain: 8%
complexity_distribution:
  low: 2
  medium: 2
  high: 0
  very_high: 0
---

# Domain Adapters

## Overview

Four regulatory-source adapter classes, each mapping one external water-quality data source
onto a uniform query/result API. Two extend `SocrataDataset` and hit the Socrata SODA HTTP API
(`CIWQS\ESMR` → resource `64tg-janj`; `SMARTS\StormwaterViolations` → resource `xsyg-h4ri`),
building SOQL WHERE clauses dynamically. Two extend the file-backed hierarchy and parse local
exports (`OCPW\ParameterDataset` → CSV via `CsvDataset`; `SMARTS\ParameterDataset` → HTML/XML
via `HtmlDataset`). All four are single-tenant, stateless-per-request, and driven by the
`OptionsTrait` option bag. This is the security-critical layer of the library — it contains
the only dynamic-query construction in the codebase.

## Business Rules

| Rule ID | Description | Source File | Confidence |
|---------|-------------|-------------|------------|
| DA-R1 | ESMR `region_code` option injected into query params when unset by caller | ESMR.php:12-14 | [Confirmed] |
| DA-R2 | ESMR date filter emits `sample_date between '<after>' and '<before>'` (both required) | ESMR.php:23-25 | [Confirmed] |
| DA-R3 | ESMR/SW geo filter emits `within_circle(<col>, lat, lng, radius)` | ESMR.php:27-29; StormwaterViolations.php:27-29 | [Confirmed] |
| DA-R4 | ESMR pivots reports into reg_meas_id → facility → mon_location → sample_date tree | ESMR.php:67-88 | [Confirmed] |
| DA-R5 | ESMR/SW `getParameters`/`getViolationTypes` return de-duplicated distinct projections | ESMR.php:90-105; StormwaterViolations.php:66-81 | [Confirmed] |
| DA-R6 | SW maps `region_code` onto `regulated_facility_region` column (dataset-specific) | StormwaterViolations.php:12-14 | [Confirmed] |
| DA-R7 | SW date filter uses strict inequalities on `occurred_on` (not BETWEEN) | StormwaterViolations.php:23-25 | [Confirmed] |
| DA-R8 | SW `violation_type` filter OR-joins per-value equality clauses | StormwaterViolations.php:31-35 | [Confirmed] |
| DA-R9 | OCPW/SMARTS `getParameterResultsBy*` group by station/site then date, averaging samples to float | OCPW/ParameterDataset.php:23-42; SMARTS/ParameterDataset.php:23-42 | [Confirmed] |
| DA-R10 | OCPW/SMARTS enforce single-parameter filter guards (throw on conflict / multi-value) | OCPW/ParameterDataset.php:45-58; SMARTS/ParameterDataset.php:45-58 | [Confirmed] |
| DA-R11 | Both Socrata adapters AND-merge compiled WHERE with any caller-supplied `$where` | ESMR.php:34-45; StormwaterViolations.php:40-51 | [Inferred] |

## Architecture

Call graph (Socrata adapters):
`get()` [override, injects region] → `parent::get()` (cache + SODA) ;
`getData()`/`getViolationReports()` → `getForEachChunk()` → `get()` → `Socrata::get()` (external).
`makeQueryParameters()` → `compileWhere()` assembles the SOQL `$where`.

Call graph (file adapters):
`getParameterResultsBy*()` → `filter()` → `withOptions()` → callback → `getData()` (filter-aware).

Integration points:
- **External Socrata SODA API** at `greengov.data.ca.gov`, two hardcoded resource IDs.
- **Support infrastructure** (support-infrastructure topic): all four inherit pagination,
  caching, option-scoping, and file-parsing from base classes.

Data flow: option bag → per-adapter WHERE/param assembly → base-class retrieval → per-adapter
aggregation → nested PHP arrays returned to caller.

## Complexity & Modernization Signals

- All four files are small (57-103 LOC); complexity is `simple`-`moderate`.
- The two `compileWhere()` implementations are the primary risk area (injection, below).
- Duck-typed `ParameterDataset` naming collision across namespaces is a maintainability hazard.
- Modernization: introduce a parameterized SOQL builder or an allowlist/escaping layer; unify
  the two file adapters (near-identical) behind a shared base with a configurable key map.

## Key Findings

1. **[Confirmed] SOQL injection is present in BOTH Socrata adapters.** `compileWhere()` builds
   SOQL WHERE clauses by direct string interpolation of option values with zero escaping —
   ESMR.php:24,28 and StormwaterViolations.php:24,28,33. The `violation_type` array-element
   interpolation (SW:33) and the `within_circle` bare-numeric interpolation are the widest
   surfaces. This confirms the prior light-pass flag with file:line evidence.
2. **[Confirmed] The injection is reachable from untrusted HTTP input on the intended path.**
   `examples/esmr.php:57-61` feeds `$_GET['latitude'|'longitude'|'radius']` straight into the
   `within_circle` option — the bundled example is the reference usage pattern, so the library
   ships an exploitable data flow, not merely a theoretical one.
3. **[Confirmed] Latent bug in `makeQueryParameters()` (both adapters).** The caller-`$where`
   merge branch reads `$params['where']` (no `$` sigil) while the guard checks `$params['$where']`
   — ESMR.php:38, StormwaterViolations.php:44. If a caller ever supplies `$where`, the merged
   clause interpolates a null/undefined key. Likely never exercised (dead branch), which is why
   it has gone unnoticed.
4. **[Inferred] Duck-typed API parity across two `ParameterDataset` classes** (OCPW/CSV vs
   SMARTS/HTML) with differing result-key names — resolvable only by fully-qualified namespace.
5. **[Uncertain] Numeric coercion of result values** — `(float)array_sum(...)` in both file
   adapters silently zeroes non-numeric results ("ND", "<0.05").

## Dead Code Candidates

### DC-001: Unreachable caller-`$where` merge branch in `makeQueryParameters()`
- **File:** src/CIWQS/ESMR.php:37-38; src/SMARTS/StormwaterViolations.php:43-44
- **Confidence:** [Inferred]
- **Evidence:** The branch is gated on `array_key_exists('$where', $params)`, but no code path
  in the repository (adapters or examples) ever passes a `$where` key into `makeQueryParameters`.
  It also contains a bug (`$params['where']` vs `$params['$where']`) that would break if reached,
  suggesting it has never executed.
- **Category:** unreachable

## Call Graph Gaps

### CG-001: External Socrata SODA API boundary
- **Source:** src/Support/Dataset/SocrataDataset.php via ESMR.php:52 / StormwaterViolations.php:58 → `Socrata::get()`
- **Confidence:** [Confirmed]
- **Type:** external_service
- **Evidence:** `$this->_socrata->get()` dispatches to the `socrata/soda-php` SDK, an external
  HTTP client not present in this repo; the response contract (row shape, pagination termination)
  is defined by the remote SODA service.
- **Possible targets:** socrata/soda-php `Socrata::get()`

## SME Questions

1. Are `within_circle`, `after`, `before`, `region_code`, and `violation_type` ever populated
   from untrusted input in production? (The bundled example proves the pattern is intended.)
2. Has the `$where`-merge branch (ESMR:37, SW:43) ever executed? If so, the `$params['where']`
   typo is a live bug.
3. Is the `ParameterDataset` naming collision intentional (interchangeable duck-typing)?
4. Can `result` values be non-numeric in OCPW CSV / SMARTS HTML? `(float)` averaging would zero them.

## Source File Index

| File | Purpose | Tier | Complexity | Analysis Doc |
|------|---------|------|------------|--------------|
| src/CIWQS/ESMR.php | CIWQS eSMR Socrata adapter (SOQL builder) | deep | moderate | ESMR.php-analysis.md |
| src/SMARTS/StormwaterViolations.php | SMARTS violations Socrata adapter (SOQL builder, 3 clause types) | deep | moderate | StormwaterViolations.php-analysis.md |
| src/OCPW/ParameterDataset.php | OC Watersheds CSV adapter | deep | simple | ParameterDataset.php-analysis.md |
| src/SMARTS/ParameterDataset.php | SMARTS stormwater HTML adapter | deep | simple | ParameterDataset.php-SMARTS-analysis.md |
