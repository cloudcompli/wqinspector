---
source_file: ParameterDataset.php
source_path: src/SMARTS/ParameterDataset.php
topic: domain-adapters
batch_id: batch-001
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 5
confidence_distribution:
  confirmed: 74%
  inferred: 19%
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

# ParameterDataset.php (SMARTS) — SMARTS Stormwater HTML Adapter

## Purpose

Domain adapter over SMARTS stormwater HTML exports. Extends `HtmlDataset` (file-backed via
`simplexml_load_file`). Structurally near-identical to `OCPW\ParameterDataset` but keyed on
`site_facility_name` / `date_time_of_sample_collection` rather than `station` / `date`.
Naming collides with `OCPW\ParameterDataset` — disambiguated by namespace and analysis-doc suffix.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | `getParameters()` returns de-duplicated distinct `parameter` values across loaded rows | 10-19 | [Confirmed] |
| R2 | `getParameterResultsBySite()` groups into `site_facility_name → date_time_of_sample_collection → [samples]` then averages each to a float | 23-42 | [Confirmed] |
| R3 | Guard: explicit `$parameter` conflicting with a `filter.parameter` option throws | 45-46 | [Confirmed] |
| R4 | Guard: non-string `filter.parameter` (multiple values) throws | 47-48 | [Confirmed] |
| R5 | No filter + null `$parameter` throws; otherwise `filter()` then average | 53-58 | [Inferred] |

## Call Relationships

- `getParameterResultsBySite()` → `FileDataset::filter()` → `withOptions()` → callback → `FileDataset::getData()`
- Reads `$this->_data` populated by `HtmlDataset::__construct()` (XML/HTML parsing)

## Complexity Assessment

- LOC: 57; 2 public methods; classified `simple`. Not in the golden dataset (structurally
  analogous to OCPW sibling per phase-3 confidence note) — analyzed here for completeness.

## Security Findings

- No injection surface (file-backed). See HtmlDataset XXE note (support-infrastructure brief,
  CG/SEC): `simplexml_load_file` on caller-supplied paths carries a latent XXE risk if the
  parsed HTML files are ever attacker-influenced.

## Dead Code Signals

- None. Exercised by `examples/smarts.php`.

## Dynamic Pattern Flags

- Closure `$callback` invoked directly or via `filter()`. Statically resolvable.

## SME Questions

1. Same `(float)` averaging concern as OCPW: are non-numeric `result` values possible in the
   SMARTS HTML exports?
2. The `getData()` call inside the callback (line 26) is `FileDataset::getData()` (filter-aware),
   NOT the Socrata `getData()` — confirm no confusion arises from the shared method name across
   the two dataset hierarchies.
