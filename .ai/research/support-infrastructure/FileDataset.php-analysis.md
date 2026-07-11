---
source_file: FileDataset.php
source_path: src/Support/Dataset/FileDataset.php
topic: support-infrastructure
batch_id: batch-002
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 4
confidence_distribution:
  confirmed: 85%
  inferred: 10%
  uncertain: 5%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called: []
extraction_method: llm
---

# FileDataset.php — File-Backed Base Class

## Purpose

Root of the file-backed dataset hierarchy. Holds parsed rows in `$_data` and a list of source
`$_paths`, composes `OptionsTrait`, and implements in-memory filtering. Subclassed by
`CsvDataset` and `HtmlDataset`.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Constructor normalizes a scalar path into a single-element array | 12-18 | [Confirmed] |
| R2 | `filter()` applies filter options for the duration of the callback via `withOptions()` | 20-23 | [Confirmed] |
| R3 | `getData()` returns rows matching ALL active filter keys; each filter value coerced to an array for `in_array` membership | 25-47 | [Confirmed] |
| R4 | With no filter option set, `getData()` returns all rows unfiltered | 45-46 | [Confirmed] |

## Call Relationships

- Called by: `CsvDataset`, `HtmlDataset` (constructor delegation), and the file adapters
  (`OCPW\ParameterDataset`, `SMARTS\ParameterDataset`) via `filter()`/`getData()`
- Composes: `OptionsTrait`

## Complexity Assessment

- LOC: 47; simple. Single nested filter loop. Matches golden baseline (rules within [3,6]).

## Security Findings

- Source paths (`$_paths`) are caller-supplied and read by subclass constructors. Path-traversal
  / arbitrary-file-read risk depends entirely on the caller (see CsvDataset/HtmlDataset). No
  validation here. [Inferred] low risk in intended single-tenant library usage.

## Dead Code Signals

- None.

## Dynamic Pattern Flags

- None (the closure is passed through to `withOptions`).

## SME Questions

1. `getData()` filter membership uses loose `in_array` (no strict flag) — is loose-type
   matching intentional (e.g. `'8' == 8`)?
