---
source_file: OptionsTrait.php
source_path: src/Support/Dataset/OptionsTrait.php
topic: support-infrastructure
batch_id: batch-002
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 3
confidence_distribution:
  confirmed: 80%
  inferred: 15%
  uncertain: 5%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called: []
extraction_method: llm
---

# OptionsTrait.php — Option-Bag Mixin

## Purpose

Trait providing an option bag (`$_options`) mixed into both `FileDataset` and `SocrataDataset`.
Supplies point-set, bulk-merge, and scoped (save/restore) option semantics. This is the
foundational cross-hierarchy component — the injection-relevant option values (`within_circle`,
`region_code`, etc.) all flow through here.

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | `setOption($key,$value)` sets a single option | 9-12 | [Confirmed] |
| R2 | `setOptions($options)` merges (array_merge) into the existing bag | 14-17 | [Confirmed] |
| R3 | `withOptions($options,$callback)` snapshots the bag, applies options, runs the callback, then restores the snapshot and returns the callback result | 19-27 | [Confirmed] |

## Call Relationships

- Mixed into: `FileDataset`, `SocrataDataset`
- `withOptions` is the mechanism behind `FileDataset::filter()` and the adapters' `withOptions` usage in examples

## Complexity Assessment

- LOC: 26; simple. Matches golden baseline (rule count 3, within [3,5]).

## Security Findings

- No validation/sanitization of option keys or values. This is the entry point for the
  untrusted values that reach `compileWhere()` — a natural place to add an escaping/allowlist
  layer during remediation (see domain-adapters SEC-1/SEC-3). [Inferred]

## Dead Code Signals

- None.

## Dynamic Pattern Flags

- Callback invocation in `withOptions` (line 24) — statically resolvable.

## SME Questions

1. `array_slice($this->_options, 0, count(...))` (line 21) is an idiomatic shallow-copy for the
   save/restore snapshot. Nested option arrays (e.g. `within_circle`, `filter`) are copied by
   reference-safe value for scalars but shallow for sub-arrays — confirm no nested mutation
   leaks across a `withOptions` scope.
