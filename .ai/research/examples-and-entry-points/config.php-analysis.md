---
source_file: config.php
source_path: examples/config.php
topic: examples-and-entry-points
batch_id: batch-003
analyzed_at: 2026-07-09T00:00:00Z
agent_version: claude-opus-4-8
rules_extracted: 1
confidence_distribution:
  confirmed: 90%
  inferred: 10%
  uncertain: 0%
complexity: simple
analysis_schema_version: "0.7.10"
dependencies:
  tables_read: []
  tables_written: []
  procedures_called: []
extraction_method: llm
---

# config.php — Socrata Token Config

## Purpose

Minimal configuration include defining a single `$socrataToken` global, required by the Socrata
examples (`esmr.php`, `violations.php`).

## Business Rules

| Rule ID | Description | Line | Confidence |
|---------|-------------|------|------------|
| R1 | Declares `$socrataToken` (empty placeholder) consumed by Socrata examples | 3 | [Confirmed] |

## Call Relationships

- Included by `examples/esmr.php`, `examples/violations.php`

## Complexity Assessment

- LOC: 2; trivial.

## Security Findings

- SEC (Advisory): the token is shipped empty here, but this is the credential-injection point.
  If a real token were committed to this file it would leak a secret. Confirm the deployment
  process supplies the token out-of-band. [Inferred]

## Dead Code Signals

- None.

## Dynamic Pattern Flags

- None.

## SME Questions

1. How is `$socrataToken` populated in real use — env var, out-of-band edit, or committed value?
