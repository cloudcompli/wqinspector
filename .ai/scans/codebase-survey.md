# Codebase Survey: wqinspector (WQInvestigator)

## Entry Points Identified

| Entry Point | Type | Location | Priority |
|-------------|------|----------|----------|
| `examples/esmr.php` | Script / Usage Example | `examples/esmr.php` | Tier 2 |
| `examples/ocpw.php` | Script / Usage Example | `examples/ocpw.php` | Tier 2 |
| `examples/smarts.php` | Script / Usage Example | `examples/smarts.php` | Tier 2 |
| `examples/violations.php` | Script / Usage Example | `examples/violations.php` | Tier 2 |
| `examples/config.php` | Configuration Entry Point | `examples/config.php` | Tier 2 |
| `CIWQS\ESMR::get()` | Public API Method | `src/CIWQS/ESMR.php` | Tier 1 |
| `OCPW\ParameterDataset::get()` | Public API Method | `src/OCPW/ParameterDataset.php` | Tier 1 |
| `SMARTS\ParameterDataset::get()` | Public API Method | `src/SMARTS/ParameterDataset.php` | Tier 1 |
| `SMARTS\StormwaterViolations::get()` | Public API Method | `src/SMARTS/StormwaterViolations.php` | Tier 1 |

## Module Inventory

| Module ID | Description | Files | Est. Complexity | Priority |
|-----------|-------------|-------|-----------------|----------|
| M1: CIWQS | CIWQS eSMR regulatory monitoring adapter. Extends SocrataDataset; builds SOQL WHERE clauses via string interpolation; wraps Socrata SODA API for eSMR data. | 1 | Moderate–Complex (103 LOC, SOQL builder) | Tier 1 |
| M2: OCPW | OC Watersheds CSV file adapter. Extends CsvDataset; parses local CSV exports for parameter data. | 1 | Simple (57 LOC) | Tier 1 |
| M3: SMARTS | SMARTS stormwater data adapters. ParameterDataset extends HtmlDataset (file parsing); StormwaterViolations extends SocrataDataset (SOQL + Socrata API). | 2 | Simple–Moderate (57 + 79 LOC) | Tier 1 |
| M4: Support.Dataset | Shared infrastructure base classes and trait. FileDataset, CsvDataset, HtmlDataset, SocrataDataset (phpFastCache integration), OptionsTrait. | 5 | Simple–Moderate (25–85 LOC) | Tier 1 |
| M5: Examples | Runnable example scripts demonstrating CIWQS, OCPW, SMARTS, and Violations usage. Reference for expected call patterns and option sets. | 5 | Simple (2–56 LOC) | Tier 2 |

## Recommended Analysis Batches

| Batch | Name | Files | Agent Assignment |
|-------|------|-------|------------------|
| batch-001 | Domain Adapters | `src/CIWQS/ESMR.php`, `src/OCPW/ParameterDataset.php`, `src/SMARTS/ParameterDataset.php`, `src/SMARTS/StormwaterViolations.php` | sonnet (deep; ESMR has SOQL builder complexity) |
| batch-002 | Support Infrastructure | `src/Support/Dataset/FileDataset.php`, `src/Support/Dataset/CsvDataset.php`, `src/Support/Dataset/HtmlDataset.php`, `src/Support/Dataset/OptionsTrait.php`, `src/Support/Dataset/SocrataDataset.php` | sonnet (deep; SocrataDataset has cache + pagination logic) |
| batch-003 | Examples and Entry Points | `examples/config.php`, `examples/esmr.php`, `examples/ocpw.php`, `examples/smarts.php`, `examples/violations.php` | sonnet (deep; examples reveal expected API surface and call patterns) |

### Analysis Notes

- **No BOOTSTRAP-COMPLETE.flag found** — golden set calibration batch (batch-000) is skipped per protocol.
- **Single-tenant PHP library** — no tenant discriminator, no routing elevation needed.
- **SOQL injection risk** flagged in domain profile (`compileWhere()` in ESMR and StormwaterViolations) — batch-001 should surface this in findings.
- **3 batches** is within the 3–8 target range for a <100-file codebase.
- All files are `deep` tier (no generated, migrations, or structural-only directories).
