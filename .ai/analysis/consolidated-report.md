# Consolidated Code Branch Analysis Report — wqinspector (WQInvestigator)

Generated: 2026-07-09 | Campaign: wqinspector-scan-20260708T000000Z

## Executive Summary

- **Files analyzed:** 14 of 14 analyzable source files (87.5% of 16 total; the 2 remainder are
  `.gitignore`/`.gitkeep` placeholders)
- **Topics:** 3 (domain-adapters, support-infrastructure, examples-and-entry-points)
- **Business rules extracted:** 60 (post-dedup 60 — no cross-topic duplicates)
- **Security findings:** 6 (2 SOQL injection, 1 reflected XSS, 1 XXE-version-hazard, 2 advisory)
- **Dead code candidates:** 2 ([1] Confirmed, [1] Inferred)
- **Call graph gaps:** 3 (all external-service boundaries)
- **Dynamic patterns:** 0 (all closures statically resolvable; no eval/reflection/dynamic dispatch)
- **SME questions:** 12
- **Confidence:** 76% Confirmed, 17% Inferred, 7% Uncertain

This is a small (16-file), single-tenant PHP library that adapts four California regulatory
water-quality data sources (CIWQS eSMR, OC Watersheds CSV, SMARTS HTML, SMARTS stormwater
violations) behind a uniform query/result API. It has **no database** — retrieval is via the
Socrata SODA HTTP API and local file parsing. The dominant finding is a **confirmed SOQL
injection** in the two Socrata adapters, **demonstrably reachable from untrusted HTTP input**
on the library's own reference example.

## Key Findings by Priority

### P0 — Security: SOQL injection (Confirmed)
`compileWhere()` in both `CIWQS\ESMR` (src/CIWQS/ESMR.php:24,28) and
`SMARTS\StormwaterViolations` (src/SMARTS/StormwaterViolations.php:24,28,33) builds Socrata SOQL
WHERE clauses by direct string interpolation of option values (`after`, `before`,
`within_circle[0..2]`, `violation_type[]`) with **zero escaping or parameterization**. The
`violation_type` array-element interpolation (SW:33) and the bare-numeric `within_circle`
interpolation are the widest surfaces. **Reachability is confirmed**: `examples/esmr.php:57-61`
feeds `$_GET['latitude'|'longitude'|'radius']` directly into `within_circle`. This is the prior
light-pass flag, now confirmed with file:line evidence and a concrete untrusted-input path.

### P0 — Security: Reflected XSS in reference example (Confirmed)
`examples/esmr.php:51-55` echoes `$_GET` values into an inline `<script>` block with no
encoding (also line 28 into HTML). Live XSS if the example is deployable.

### P1 — Security: XXE version hazard (Uncertain)
`src/Support/Dataset/HtmlDataset.php:12` uses `simplexml_load_file` on caller-supplied paths.
Version-dependent external-entity risk if SMARTS HTML exports are attacker-influenced.

### P1 — Correctness: latent bug in `makeQueryParameters()` (Inferred)
Both Socrata adapters reference `$params['where']` (no `$` sigil) in the caller-`$where` merge
branch while the guard checks `$params['$where']` — ESMR.php:38, StormwaterViolations.php:44.
Appears to be an unreached dead branch (DC-001); would break if a caller ever supplies `$where`.

### P2 — Upgrade blockers / supply-chain hazards
- **composer.json wildcard version pins:** `phpfastcache/phpfastcache: "*"` and
  `socrata/soda-php: "*"` (composer.json:10-11) — unpinned, non-reproducible builds; any
  transitive major bump can break the library silently.
- **phpunit 4.0.\*** dev dependency (composer.json:14) — abandoned, incompatible with PHP 7.2+
  test runners; blocks modern CI.
- No PHP version constraint declared (`require.php` absent) — unclear supported runtime.

### P2 — Maintainability
- Duck-typed `ParameterDataset` naming collision across `OCPW` and `SMARTS` namespaces
  (differing result-key names) — resolvable only by FQN.
- Numeric coercion `(float)array_sum(...)` in both file adapters silently zeroes non-numeric
  results ("ND", "<0.05").

## Dead Code Candidates (2)

- **DC-001** [Inferred] Unreachable caller-`$where` merge branch — ESMR.php:37-38,
  StormwaterViolations.php:43-44 (also carries the `$params['where']` typo).
- **DC-002** [Confirmed] 23-line commented-out usage block — examples/ocpw.php:20-43.

## Call Graph Gaps (3)

- **CG-001** [Confirmed] External Socrata SODA API (`Socrata::get()`) — soda-php SDK.
- **CG-002** [Inferred] phpFastCache handler `get/set` — phpfastcache package.
- **CG-003** [Confirmed] External library bootstrap in examples (`new Socrata`, `CacheManager::Files`).

## Dynamic Patterns (0)

No eval, reflection, dynamic dispatch, or variable-variable patterns. All closures (option
scoping, chunk accumulation, filter callbacks) are statically resolvable.

## Action Items

1. **[P0]** Parameterize or escape SOQL in both `compileWhere()` implementations; add
   validation at the `OptionsTrait` chokepoint (single change surface for both adapters).
2. **[P0]** If any `examples/` script is deployable, add output encoding (XSS) and input
   validation; otherwise mark examples as explicitly non-deployable.
3. **[P1]** Fix or remove the `$params['where']` merge branch (DC-001).
4. **[P1]** Harden `HtmlDataset` XXE (disable entity loading / `LIBXML_NONET`) if input is untrusted.
5. **[P2]** Pin composer dependencies to explicit version ranges; retire phpunit 4; declare a PHP version constraint.
6. **[P2]** Confirm SME questions (see synthesis/sme-questions.md) — injection reachability and pagination termination are the highest-value.

## Shared-DB / External-API Summary

- **Databases:** none.
- **External APIs:** Socrata SODA at `greengov.data.ca.gov`, resources `64tg-janj` (ESMR) and
  `xsyg-h4ri` (violations); phpFastCache file cache.
