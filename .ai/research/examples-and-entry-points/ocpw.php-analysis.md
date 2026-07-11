---
source_file: ocpw.php
source_path: examples/ocpw.php
topic: examples-and-entry-points
batch_id: batch-003
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 2
confidence_distribution:
  confirmed: 82%
  inferred: 13%
  uncertain: 5%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: OCPW\ParameterDataset::getParameterResultsByStation
      confidence: confirmed
      source: llm
      kind: method
    - name: FileDataset::filter
      confidence: confirmed
      source: llm
      kind: method
extraction_method: llm
---

# ocpw.php — OC Watersheds CSV Example

## Purpose

Runnable script demonstrating the OCPW CSV adapter: loads a local `sarme.csv`, applies a
parameter+watershed filter scope, and dumps station-averaged results plus raw filtered data.
Contains a large commented-out block showing multi-file / multi-parameter usage variants.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Constructs `OCPW\ParameterDataset` from a local CSV path under `examples/data/ocwatersheds/` | 6-8 | [Confirmed] |
| R2 | `filter(['parameter'=>'Se','watershed'=>'Newport Bay'], cb)` scopes then dumps `getParameterResultsByStation()` + `getData()` | 12-18 | [Confirmed] |

## Call Relationships

- → `OCPW\ParameterDataset::getParameterResultsByStation()`, `FileDataset::filter()`, `getData()`

## Complexity Assessment

- LOC: 31 active (plus a ~23-line commented block). Simple.

## Security Findings

- None. File-backed, hardcoded local paths, no request input.

## Dead Code Signals

### DC-002: Large commented-out usage block
- **File:** examples/ocpw.php:20-43
- **Confidence:** [Confirmed]
- **Evidence:** A 23-line block demonstrating ESM/NSMP multi-file variants is fully commented
  out. Documentation value only; not executed.
- **Category:** unreachable

## Dynamic Pattern Flags

- Closure passed to `filter` (line 15) — statically resolvable.

## SME Questions

1. The commented block references data files (`esm.csv`, `nsmp_2015-q*.csv`) — are these still
   shipped/relevant, or is the block stale documentation to remove?
