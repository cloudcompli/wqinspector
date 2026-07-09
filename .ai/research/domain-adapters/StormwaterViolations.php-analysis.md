---
source_file: StormwaterViolations.php
source_path: src/SMARTS/StormwaterViolations.php
topic: domain-adapters
batch_id: batch-001
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 8
confidence_distribution:
  confirmed: 72%
  inferred: 20%
  uncertain: 8%
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
      resource_id: xsyg-h4ri
      endpoint: /resource/xsyg-h4ri.json
      confidence: confirmed
      source: llm
      note: SMARTS stormwater violations dataset (greengov.data.ca.gov)
extraction_method: llm
---

# StormwaterViolations.php — SMARTS Stormwater Violations Adapter

## Purpose

Domain adapter for the SMARTS stormwater violations dataset via Socrata SODA API
(resource `xsyg-h4ri`). Extends `SocrataDataset`. Structurally parallel to `CIWQS\ESMR`
but adds a third WHERE clause type — a multi-value `violation_type` OR-filter — and maps
`region_code` onto the dataset-specific `regulated_facility_region` column.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | `get()` maps the `region_code` option onto the `regulated_facility_region` query parameter when set and not caller-supplied | 12-14 | [Confirmed] |
| R2 | `compileWhere()` emits `occurred_on > '<after>' and occurred_on < '<before>'` (strict inequalities, not BETWEEN) when both date options set | 23-25 | [Confirmed] |
| R3 | `compileWhere()` emits a `within_circle(location_1, lat, lng, radius)` geo clause (note: `location_1`, distinct from ESMR's `location`) | 27-29 | [Confirmed] |
| R4 | `compileWhere()` emits an OR-joined `violation_type = '<v>'` clause over the `violation_type` option array | 31-35 | [Confirmed] |
| R5 | WHERE fragments AND-combined as `(frag) AND (frag)`; null when empty | 37 | [Confirmed] |
| R6 | `makeQueryParameters()` merges compiled WHERE with caller-supplied `$where` under AND | 40-51 | [Inferred] |
| R7 | `getViolationReports()` retrieves all matching rows via chunked pagination into a flat array | 53-64 | [Confirmed] |
| R8 | `getViolationTypes()` runs `$select=violation_type` and returns de-duplicated distinct types | 66-81 | [Confirmed] |

## Call Relationships

- `getViolationReports()` → `getForEachChunk('/resource/xsyg-h4ri.json')` → `get()` → `Socrata::get()`
- `getViolationTypes()` → `getForEachChunk()` directly
- `get()` overrides and delegates to `parent::get()`
- Consumes options: `region_code`, `before`, `after`, `within_circle`, `violation_type`

## Complexity Assessment

- LOC: 79; 5 public methods; `compileWhere()` has 3 conditional clause builders (vs ESMR's 2)
- Classified `moderate` — matches golden baseline (rule count 8, within [5,9])

## Security Findings

### SEC-3: SOQL injection in `compileWhere()` — three interpolation sites [Confirmed]
- **File:** src/SMARTS/StormwaterViolations.php:24, 28, 33
- Same defect class as ESMR SEC-1. `after`, `before`, `within_circle[0..2]`, AND each
  `violation_type` array element are string-interpolated into SOQL with no escaping.
- The `violation_type` clause (line 33) is the widest surface: an attacker-controlled array
  element such as `x' OR '1'='1` breaks out of the quoted literal.
- **Exploitability:** `examples/violations.php:22-30` shows the option set being passed via
  `withOptions()`; while that example hardcodes values, the identical injection path to
  `esmr.php` (which uses `$_GET`) confirms the library provides no escaping barrier.

## Dead Code Signals

- None. All methods reachable and exercised by `examples/violations.php`.

## Dynamic Pattern Flags

- Closure callback in `array_map()` (line 32) for `violation_type` clause construction — statically resolvable.
- Result-accumulation closures at lines 58, 73.

## SME Questions

1. Is `violation_type` ever sourced from user input? The array-element interpolation at
   line 33 is the highest-risk injection surface in the codebase.
2. Line 44 references `$params['where']` (no `$` sigil) inside the merge branch — same latent
   bug as ESMR:38. Confirm whether the caller-supplied-`$where` merge path has ever executed.
