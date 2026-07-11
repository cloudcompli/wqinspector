---
source_file: smarts.php
source_path: examples/smarts.php
topic: examples-and-entry-points
batch_id: batch-003
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 2
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
    - name: SMARTS\ParameterDataset::getParameterResultsBySite
      confidence: confirmed
      source: llm
      kind: method
    - name: FileDataset::filter
      confidence: confirmed
      source: llm
      kind: method
extraction_method: llm
---

# smarts.php — SMARTS HTML Example

## Purpose

Runnable script demonstrating the SMARTS HTML adapter: loads four local SMARTS HTML export
files, lists parameters, then filters to a single parameter and dumps site-averaged results.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Constructs `SMARTS\ParameterDataset` from four local HTML export paths | 8-13 | [Confirmed] |
| R2 | `filter(['parameter'=>'Selenium, Total'], cb)` scopes then dumps `getParameterResultsBySite()` + `getData()` | 17-22 | [Confirmed] |

## Call Relationships

- → `SMARTS\ParameterDataset::getParameterResultsBySite()`, `FileDataset::filter()`, `getData()`

## Complexity Assessment

- LOC: 17; simple.

## Security Findings

- None directly. Passes four local HTML paths to `HtmlDataset` → `simplexml_load_file`; see
  the version-dependent XXE note (support-infrastructure SEC-4) if these files are untrusted.

## Dead Code Signals

- None.

## Dynamic Pattern Flags

- Closure passed to `filter` (line 19) — statically resolvable.

## SME Questions

1. Are the four SMARTS HTML export files trusted (machine-generated) or potentially attacker-supplied?
