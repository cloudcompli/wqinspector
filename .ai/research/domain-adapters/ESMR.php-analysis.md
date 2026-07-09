---
source_file: ESMR.php
source_path: src/CIWQS/ESMR.php
topic: domain-adapters
batch_id: batch-001
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 9
confidence_distribution:
  confirmed: 67%
  inferred: 22%
  uncertain: 11%
complexity: moderate
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: SocrataDataset::getForEachChunk
      confidence: confirmed
      source: llm
      kind: method
    - name: SocrataDataset::get
      confidence: confirmed
      source: llm
      kind: method
  external_apis:
    - name: Socrata SODA API
      resource_id: 64tg-janj
      endpoint: /resource/64tg-janj.json
      confidence: confirmed
      source: llm
      note: CIWQS eSMR regulatory monitoring dataset (greengov.data.ca.gov)
extraction_method: llm
---

# ESMR.php — CIWQS eSMR Adapter

## Purpose

Domain adapter for the California CIWQS Electronic Self-Monitoring Report (eSMR) dataset,
served via the Socrata SODA API (resource `64tg-janj`). Extends `SocrataDataset` and adds
eSMR-specific SOQL WHERE-clause construction, query-parameter assembly, chunked retrieval,
and two aggregation views (`getParameterByRegulatoryMeasureId`, `getParameters`).

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | `get()` injects the `region_code` option into query parameters when set and not already present in the caller's params | 12-14 | [Confirmed] |
| R2 | `compileWhere()` emits a `sample_date between '<after>' and '<before>'` clause only when BOTH `before` and `after` options are set | 23-25 | [Confirmed] |
| R3 | `compileWhere()` emits a `within_circle(location, lat, lng, radius)` geo clause when the `within_circle` option is a 3-element array | 27-29 | [Confirmed] |
| R4 | Multiple WHERE fragments are AND-combined as `(frag1) AND (frag2)`; returns null when no fragments | 31 | [Confirmed] |
| R5 | `makeQueryParameters()` merges the compiled WHERE with any caller-supplied `$where` under AND | 34-45 | [Inferred] |
| R6 | `getData()` retrieves all rows for the compiled query via 1000-row chunked pagination, accumulating into a flat array | 47-58 | [Confirmed] |
| R7 | `getParameterByRegulatoryMeasureId()` pivots reports into a `reg_meas_id → {facility_name, mon_locations{location → {coordinates, data{sample_date → "result units"}}}}` tree | 67-88 | [Confirmed] |
| R8 | `getParameters()` runs a `$select=parameter` projection and returns the de-duplicated list of distinct parameter names | 90-105 | [Confirmed] |
| R9 | Coordinates are read defensively from `location.coordinates` (null if absent) | 80 | [Inferred] |

## Call Relationships

- `getData()` → `SocrataDataset::getForEachChunk()` (`/resource/64tg-janj.json`) → `SocrataDataset::get()` → `Socrata::get()` (external SODA API)
- `getParameterReports()` → `getData()`
- `getParameterByRegulatoryMeasureId()` → `getParameterReports()`
- `getParameters()` → `getForEachChunk()` directly
- `get()` overrides `SocrataDataset::get()` and calls `parent::get()`
- Options (`region_code`, `before`, `after`, `within_circle`) supplied via `OptionsTrait::setOptions()` / `withOptions()`

## Complexity Assessment

- LOC: 103; 7 public methods; max nesting depth 3 (in `getParameterByRegulatoryMeasureId`)
- Cyclomatic complexity concentrated in the nested-array construction of R7
- Classified `moderate` — consistent with the golden baseline (rule count 9, within [6,10])

## Security Findings

### SEC-1: SOQL injection via string interpolation in `compileWhere()` [Confirmed]
- **File:** src/CIWQS/ESMR.php:24, 28
- Option values (`after`, `before`, `within_circle[0..2]`) are concatenated directly into
  the SOQL WHERE string with no escaping or parameterization. A value containing `'` or
  SOQL syntax alters the query. `within_circle` values are interpolated as bare numeric
  literals — a non-numeric value injects raw SOQL.
- **Exploitability is CONFIRMED, not hypothetical:** `examples/esmr.php:57-61` populates
  `within_circle` directly from `$_GET['latitude'|'longitude'|'radius']` with no validation,
  demonstrating the intended usage path carries untrusted HTTP input straight into the
  interpolated clause.

### SEC-2: `region_code` interpolation [Inferred]
- **File:** src/CIWQS/ESMR.php:13 (option passed to Socrata as a query param, not WHERE-interpolated here)
- `region_code` is passed as a discrete query parameter (`$queryParameters['region_code']`),
  which the SODA client sends as a column filter — lower risk than the WHERE interpolation,
  but still unvalidated caller input.

## Dead Code Signals

- No dead code. All methods are reachable from the public API and exercised by `examples/esmr.php`.

## Dynamic Pattern Flags

- Closure callbacks passed to `getForEachChunk()` (lines 52, 97) — result accumulation by reference. Statically resolvable.

## SME Questions

1. Are `within_circle`, `after`, `before`, and `region_code` ever populated from untrusted
   input in production consumers (the bundled example proves the pattern is intended)? If so,
   this is an exploitable injection, not a theoretical one.
2. Is the `$params['where']` reference at line 38 (missing `$` sigil) a latent bug? The guard
   checks `$params['$where']` but the merge reads `$params['where']` — see cross-file note.
