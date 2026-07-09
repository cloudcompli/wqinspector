---
source_file: ParameterDataset.php
source_path: src/OCPW/ParameterDataset.php
topic: domain-adapters
batch_id: batch-001
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 5
confidence_distribution:
  confirmed: 75%
  inferred: 18%
  uncertain: 7%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called:
    - name: FileDataset::filter
      confidence: confirmed
      source: llm
      kind: method
    - name: FileDataset::getData
      confidence: confirmed
      source: llm
      kind: method
extraction_method: llm
---

# ParameterDataset.php (OCPW) — OC Watersheds CSV Adapter

## Purpose

Domain adapter over OC Watersheds CSV exports. Extends `CsvDataset` (file-backed, no Socrata,
no cache). Provides distinct-parameter listing and a station-averaged results view.
Note: this is one of two classes named `ParameterDataset` (see SMARTS sibling) — resolved by
namespace only.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | `getParameters()` returns the de-duplicated distinct `parameter` values across all loaded rows | 10-19 | [Confirmed] |
| R2 | `getParameterResultsByStation()` groups results into `station → date → [samples]` then averages each date's samples to a single float | 23-42 | [Confirmed] |
| R3 | Guard: passing an explicit `$parameter` that conflicts with an existing `filter.parameter` option throws | 45-46 | [Confirmed] |
| R4 | Guard: a non-string `filter.parameter` option (i.e. multiple values) throws — the method requires a single parameter | 47-48 | [Confirmed] |
| R5 | When no filter option and no `$parameter`, throws; otherwise applies `filter(['parameter' => [$parameter]], callback)` before averaging | 53-58 | [Inferred] |

## Call Relationships

- `getParameterResultsByStation()` → `FileDataset::filter()` → `OptionsTrait::withOptions()` → callback → `FileDataset::getData()`
- Reads pre-parsed `$this->_data` populated by `CsvDataset::__construct()`

## Complexity Assessment

- LOC: 57; 2 public methods; the filter-guard branch is the only real complexity
- Classified `simple` — matches golden baseline (rule count within [3,6])

## Security Findings

- No SOQL/injection surface — file-backed, no dynamic query construction. Input is a local
  CSV path (see FileDataset trust note in support-infrastructure brief).

## Dead Code Signals

- None. Both methods exercised by `examples/ocpw.php`.

## Dynamic Pattern Flags

- Closure `$callback` (line 23) invoked either directly or via `filter()`. Statically resolvable.
- `throw` with `__METHOD__` — reflection-adjacent but benign (string only).

## SME Questions

1. `getParameterResultsByStation` averages `result` values as `(float)` — are non-numeric
   result strings (e.g. "<0.05", "ND") possible in OCPW CSVs? They would silently cast to 0.0.
2. Is the duck-typed API parity with `SMARTS\ParameterDataset` relied upon polymorphically?
