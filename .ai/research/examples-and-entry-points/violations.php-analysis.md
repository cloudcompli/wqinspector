---
source_file: violations.php
source_path: examples/violations.php
topic: examples-and-entry-points
batch_id: batch-003
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 3
confidence_distribution:
  confirmed: 85%
  inferred: 10%
  uncertain: 5%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: StormwaterViolations::getViolationTypes
      confidence: confirmed
      source: llm
      kind: method
    - name: StormwaterViolations::getViolationReports
      confidence: confirmed
      source: llm
      kind: method
  external_apis:
    - name: Socrata SODA API
      endpoint: https://greengov.data.ca.gov
      confidence: confirmed
      source: llm
extraction_method: llm
---

# violations.php — SMARTS Violations Example

## Purpose

Runnable script demonstrating the SMARTS StormwaterViolations adapter: bootstraps Socrata +
cache, presets region/date, lists violation types, then runs a geo + violation-type scoped
report query.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Bootstraps Socrata + file cache; presets region_code and date window | 6-18 | [Confirmed] |
| R2 | `getViolationTypes()` lists distinct violation types | 20 | [Confirmed] |
| R3 | `withOptions()` scopes a geo circle + a 4-value `violation_type` filter, then runs `getViolationReports()` | 22-31 | [Confirmed] |

## Call Relationships

- → `StormwaterViolations::getViolationTypes()`, `getViolationReports()`, `withOptions()`, `setCacheHandler()`, `setOptions()`

## Complexity Assessment

- LOC: 27; simple.

## Security Findings

- Unlike `esmr.php`, this example hardcodes all option values (no `$_GET`), so it does not
  itself demonstrate untrusted input. However it exercises the identical `violation_type` and
  `within_circle` interpolation path flagged in domain-adapters SEC-3. [Inferred] The injection
  barrier is absent regardless of whether this specific example supplies clean values.

## Dead Code Signals

- None.

## Dynamic Pattern Flags

- Closure passed to `withOptions` (line 30) — statically resolvable.

## SME Questions

1. In production, are `violation_type` / `within_circle` values ever request-derived (as in
   esmr.php) rather than hardcoded (as here)?
