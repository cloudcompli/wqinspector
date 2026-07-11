---
source_file: esmr.php
source_path: examples/esmr.php
topic: examples-and-entry-points
batch_id: batch-003
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 5
confidence_distribution:
  confirmed: 78%
  inferred: 15%
  uncertain: 7%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: ESMR::getParameters
      confidence: confirmed
      source: llm
      kind: method
    - name: ESMR::getParameterByRegulatoryMeasureId
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

# esmr.php — CIWQS eSMR Example / Web Entry Point

## Purpose

Runnable HTML form + handler demonstrating the CIWQS eSMR adapter. Instantiates `Socrata` and
phpFastCache, sets region/date options, renders a parameter dropdown, and on GET submission
runs a geo-scoped query. This is the reference usage pattern shipped with the library — and it
demonstrates the intended flow of untrusted HTTP input into the adapter.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Bootstraps Socrata client against `greengov.data.ca.gov` with a token from config.php | 6 | [Confirmed] |
| R2 | File-backed phpFastCache configured under `examples/cache` | 7-10 | [Confirmed] |
| R3 | Region + date options preset; parameter dropdown populated from `getParameters()` | 14-21 | [Confirmed] |
| R4 | On GET with all four fields, runs `getParameterByRegulatoryMeasureId` inside a `within_circle` scope built from request params | 46-62 | [Confirmed] |
| R5 | Echoes submitted field values back into a `<script>` block to repopulate the form | 51-55 | [Confirmed] |

## Call Relationships

- → `ESMR::getParameters()`, `ESMR::getParameterByRegulatoryMeasureId()` (domain-adapters)
- → `ESMR::withOptions()`, `setCacheHandler()`, `setOptions()`

## Complexity Assessment

- LOC: 56; simple procedural script.

## Security Findings

### SEC-5: Reflected XSS via `$_GET` echoed into inline `<script>` [Confirmed]
- **File:** examples/esmr.php:51-55 (also line 28 for the option dropdown)
- Each of `parameter`, `latitude`, `longitude`, `radius` is echoed directly from `$_GET` into a
  JavaScript string literal with no escaping. A value like `";alert(1)//` breaks out and executes.

### SEC-6: SOQL injection reachability — untrusted input into `within_circle` [Confirmed]
- **File:** examples/esmr.php:57-61
- `$_GET['latitude'|'longitude'|'radius']` are passed straight into the `within_circle` option,
  which `ESMR::compileWhere()` interpolates unescaped into SOQL (domain-adapters SEC-1). This is
  the concrete proof that the SOQL injection is reachable from untrusted HTTP input on the
  library's own reference path. `$_GET['parameter']` (line 60) similarly reaches the query.

## Dead Code Signals

- None — but this is example code, not production. See SME question.

## Dynamic Pattern Flags

- Closure passed to `withOptions` (line 59) — statically resolvable.

## SME Questions

1. Is `examples/` ever deployed or reachable in any environment? If these scripts are only
   local dev references, SEC-5/SEC-6 are documentation-severity; if deployable, they are live
   XSS + injection. The library ships no guard either way.
