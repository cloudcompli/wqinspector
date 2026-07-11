# Dead Code Candidates — Consolidated

Total: 2 candidates (2 pre-dedup, 0 cross-topic duplicates merged)
Confidence: 1 Confirmed, 1 Inferred, 0 Uncertain

## By Category

### Unreachable Code (2)

### DC-001: Unreachable caller-`$where` merge branch in `makeQueryParameters()`
- **File:** src/CIWQS/ESMR.php:37-38; src/SMARTS/StormwaterViolations.php:43-44
- **Confidence:** [Inferred]
- **Evidence:** Gated on `array_key_exists('$where', $params)`, but no code path (adapters or
  examples) passes a `$where` key into `makeQueryParameters`. The branch also contains a bug
  (`$params['where']` vs `$params['$where']`) that would fail if reached — strong evidence it
  has never executed.
- **Category:** unreachable
- **Seen in:** domain-adapters

### DC-002: Large commented-out usage block in ocpw.php
- **File:** examples/ocpw.php:20-43
- **Confidence:** [Confirmed]
- **Evidence:** 23 lines of ESM/NSMP multi-file usage fully commented out; documentation value
  only, not executed.
- **Category:** unreachable
- **Seen in:** examples-and-entry-points

### Unused Exports (0)
None identified.

### Deprecated API Usage (0)
None classified as dead (the `simplexml_load_file` version hazard is a live-but-risky API, not dead — see dynamic/security).

### Feature-Flagged Code (0)
None.

## Cross-Topic Patterns

The two candidates are unrelated. DC-001 is a genuine latent-bug + dead-branch pair replicated
across the two Socrata adapters (a copy-paste artifact worth fixing or removing). DC-002 is
benign stale documentation. Neither indicates a systemic dead-code problem — expected for a
14-file library.
