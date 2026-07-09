# Call Graph Gaps — Consolidated

Total: 3 gaps (3 pre-dedup, 0 duplicates merged)
Confidence: 2 Confirmed, 1 Inferred, 0 Uncertain

## By Category

### External Service (3)

### CG-001: External Socrata SODA API
- **Source:** src/Support/Dataset/SocrataDataset.php:37 (via ESMR.php:52 / StormwaterViolations.php:58) → `Socrata::get()`
- **Confidence:** [Confirmed]
- **Type:** external_service
- **Evidence:** `$this->_socrata->get()` dispatches to the `socrata/soda-php` SDK, not present
  in this repo. Row shape and pagination-termination contract are defined by the remote service.
- **Possible targets:** socrata/soda-php `Socrata::get()`
- **Seen in:** domain-adapters, support-infrastructure

### CG-002: phpFastCache handler methods
- **Source:** src/Support/Dataset/SocrataDataset.php:31, 39 → `$this->_cache->get()/set()`
- **Confidence:** [Inferred]
- **Type:** external_service
- **Evidence:** Injected phpFastCache instance (external package); TTL/serialization semantics
  external to this repo.
- **Possible targets:** phpfastcache/phpfastcache cache driver
- **Seen in:** support-infrastructure

### CG-003: External library bootstrap in examples
- **Source:** examples/esmr.php:6-7; examples/violations.php:6-7 → `new Socrata(...)`, `phpFastCache\CacheManager::Files(...)`
- **Confidence:** [Confirmed]
- **Type:** external_service
- **Evidence:** Both classes resolved via `vendor/autoload.php` from Composer packages not in
  this repo.
- **Possible targets:** socrata/soda-php, phpfastcache/phpfastcache
- **Seen in:** examples-and-entry-points

## Cross-Topic Patterns

All three gaps are external-package boundaries (Socrata SODA client, phpFastCache) — expected
for a thin adapter library. There are no internal unresolved call edges; the codebase is fully
statically resolvable within its own boundary. The only runtime-contract uncertainty is the
Socrata pagination-termination behavior (see SI-R8 / SME questions), which sits behind CG-001.
