---
topic: examples-and-entry-points
files_analyzed: 5
batches:
  - batch-003
generated_at: 2026-07-09T00:00:00Z
rules_extracted: 13
confidence_distribution:
  confirmed: 82%
  inferred: 12%
  uncertain: 6%
complexity_distribution:
  low: 5
  medium: 0
  high: 0
  very_high: 0
---

# Examples and Entry Points

## Overview

Five runnable scripts demonstrating the public API surface for all four data sources, plus a
config include. `esmr.php` and `violations.php` are full Socrata web/CLI entry points (bootstrap
Socrata + phpFastCache); `ocpw.php` and `smarts.php` are file-adapter demos; `config.php` holds
the Socrata token. These scripts are the library's living documentation of intended call
patterns — and `esmr.php` is the single most security-significant file in the repository
because it demonstrates untrusted `$_GET` input flowing into both the SOQL query and an inline
`<script>` block.

## Business Rules

| Rule ID | Description | Source File | Confidence |
|---------|-------------|-------------|------------|
| EX-R1 | Socrata examples bootstrap a `Socrata` client + file cache against greengov.data.ca.gov | esmr.php:6-10; violations.php:6-10 | [Confirmed] |
| EX-R2 | Options preset region_code + date window before scoped queries | esmr.php:14-18; violations.php:14-18 | [Confirmed] |
| EX-R3 | esmr.php builds `within_circle` and `parameter` query args from `$_GET` | esmr.php:57-61 | [Confirmed] |
| EX-R4 | esmr.php echoes `$_GET` fields into an inline `<script>` to repopulate the form | esmr.php:51-55 | [Confirmed] |
| EX-R5 | File examples construct adapters from local CSV/HTML paths and apply parameter filters | ocpw.php:8-18; smarts.php:8-22 | [Confirmed] |
| EX-R6 | config.php declares the `$socrataToken` credential | config.php:3 | [Confirmed] |

## Architecture

Entry points → domain adapters → support infrastructure → external Socrata/file sources.
`esmr.php`/`violations.php` also depend on external `Socrata` and `phpFastCache\CacheManager`.
No routing, no framework — plain PHP scripts.

## Complexity & Modernization Signals

- All trivially simple (2-56 LOC). Value is as documentation/reference, not production logic.
- Modernization: if any example is deployable, it needs output encoding (XSS) and input
  validation (injection). Otherwise, mark clearly as non-deployable samples.

## Key Findings

1. **[Confirmed] esmr.php is the proof-of-reachability for the SOQL injection.** Lines 57-61
   feed `$_GET['latitude'|'longitude'|'radius']` into `within_circle`, which reaches the
   unescaped `compileWhere()` interpolation (domain-adapters SEC-1). This upgrades the injection
   from "possible" to "demonstrated on the reference path."
2. **[Confirmed] Reflected XSS in esmr.php** — `$_GET` values echoed into inline JS (lines
   51-55) and HTML (line 28) with no encoding.
3. **[Confirmed] Dead documentation block in ocpw.php** (lines 20-43, commented out).
4. **[Inferred] config.php is the credential-injection point** — must be populated out-of-band;
   never commit a real token.

## Dead Code Candidates

### DC-002: Large commented-out usage block in ocpw.php
- **File:** examples/ocpw.php:20-43
- **Confidence:** [Confirmed]
- **Evidence:** 23 lines of ESM/NSMP multi-file usage fully commented out; not executed.
- **Category:** unreachable

## Call Graph Gaps

### CG-003: External library bootstrap (Socrata, phpFastCache)
- **Source:** examples/esmr.php:6-7; examples/violations.php:6-7 → `new Socrata(...)`, `phpFastCache\CacheManager::Files(...)`
- **Confidence:** [Confirmed]
- **Type:** external_service
- **Evidence:** Both classes are provided by external Composer packages resolved via
  `vendor/autoload.php`; not present in this repo.
- **Possible targets:** socrata/soda-php, phpfastcache/phpfastcache

## SME Questions

1. Are any `examples/` scripts deployed/reachable in any environment? Determines whether the
   XSS + injection are live (High) or documentation-severity.
2. How is `$socrataToken` supplied in real use?
3. Is the commented ocpw.php block stale (remove) or intentional documentation (keep)?

## Source File Index

| File | Purpose | Tier | Complexity | Analysis Doc |
|------|---------|------|------------|--------------|
| examples/esmr.php | CIWQS eSMR web entry point (demonstrates $_GET flow) | deep | simple | esmr.php-analysis.md |
| examples/violations.php | SMARTS violations example | deep | simple | violations.php-analysis.md |
| examples/ocpw.php | OC Watersheds CSV example | deep | simple | ocpw.php-analysis.md |
| examples/smarts.php | SMARTS HTML example | deep | simple | smarts.php-analysis.md |
| examples/config.php | Socrata token config | deep | simple | config.php-analysis.md |
