# SME Questions — Consolidated

Grouped by topic, prioritized. 12 questions total.

## Highest Priority (security reachability)

1. **[domain-adapters, examples]** Are `within_circle`, `after`, `before`, `region_code`, and
   `violation_type` ever populated from untrusted input in production? The bundled
   `examples/esmr.php` proves the `$_GET` → `within_circle` path is the intended pattern. If
   production mirrors this, the SOQL injection (ESMR.php:24,28; StormwaterViolations.php:24,28,33)
   is exploitable, not theoretical.
2. **[examples]** Are any `examples/` scripts deployed or reachable in any environment? This
   determines whether the reflected XSS (esmr.php:51-55) and SOQL injection are LIVE (High
   severity) or documentation-only.
3. **[support-infrastructure]** Are SMARTS HTML export files always trusted/machine-generated,
   or can they be user-supplied? Determines whether the `simplexml_load_file` XXE surface
   (HtmlDataset.php:12) is a real exposure.

## Correctness

4. **[domain-adapters]** Has the caller-`$where` merge branch (ESMR.php:37, StormwaterViolations.php:43)
   ever executed? If so, the `$params['where']` typo (missing `$`) is a live bug.
5. **[domain-adapters]** Can `result` values be non-numeric (e.g. "ND", "<0.05") in OCPW CSV /
   SMARTS HTML? `(float)array_sum(...)` averaging would silently zero them.
6. **[support-infrastructure]** Confirm the soda-php empty-page and error-response contract for
   `getForEachChunk` termination (SocrataDataset.php:51), including the exact-multiple-of-1000
   edge case (one extra empty-page call).
7. **[support-infrastructure]** Should `CsvDataset` raise on unreadable files instead of silently
   yielding an empty dataset (CsvDataset.php:12)?

## Design / Maintainability

8. **[domain-adapters]** Is the `ParameterDataset` naming collision (OCPW vs SMARTS) intentional
   duck-typing? Are the two ever used polymorphically?
9. **[support-infrastructure]** Is `withoutCache()` intentionally discarding the callback return
   value (SocrataDataset.php:85), unlike `withOptions()` which returns it?
10. **[support-infrastructure]** Does the `array_slice` shallow snapshot in
    `OptionsTrait::withOptions` (line 21) risk nested-array mutation leaking across a scope?

## Operational

11. **[examples]** How is `$socrataToken` (config.php:3) populated in real use — env var,
    out-of-band edit, or committed value? Ensure no secret is committed.
12. **[examples]** Is the commented-out block in ocpw.php:20-43 stale (remove) or intentional
    documentation (keep)?
