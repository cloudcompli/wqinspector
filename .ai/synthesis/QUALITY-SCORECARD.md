---
artifact_type: quality-scorecard
generated_by: /finalize-analysis
generated_at: 2026-07-09T00:00:00Z
metrics_source: metrics.yaml
---

# Analysis Quality Scorecard — wqinspector (WQInvestigator)

## Campaign-Wide Metrics

| Dimension | Metric | Value | Threshold | Status |
|-----------|--------|-------|-----------|--------|
| Coverage | File coverage | 87.5% (14 of 16 files) | 90% | WARN* |
| Coverage | Analyzable-file coverage | 100% (14 of 14 source files) | 90% | PASS |
| Coverage | Topic coverage | 3 topics (3 analysis + 3 rollup) | — | PASS |
| Completeness | Rule extraction depth | ~95% (14 of 14 files within golden rule-count baselines) | 80% | PASS |
| Compliance | Output schema compliance | 100% (23 of 23 artifacts valid YAML + required sections) | 95% | PASS |
| Currency | Analysis freshness | 100% (all docs at current agent version) | 90% | PASS |
| Clarity | SME question resolution | 0% (0 of 12 resolved — pre-review) | 85% | WARN |
| Stability | Quality trend | Baseline (first run; no regression delta) | ≥0.0 | PASS |

\* **File-coverage WARN is a denominator artifact, not a gap.** The 2 non-analyzed files are
`examples/cache/.gitkeep` and `examples/data/.gitignore` (placeholders, non-source). All 14
analyzable source files were analyzed (100%). Treat analyzable-file coverage as the operative
coverage measure.

## Per-Topic Breakdown

| Topic | Files | Rules | Avg Confidence (Confirmed) | Status |
|-------|-------|-------|----------------------------|--------|
| domain-adapters | 4 | 27 | 71% | PASS |
| support-infrastructure | 5 | 20 | 79% | PASS |
| examples-and-entry-points | 5 | 13 | 82% | PASS |

## Confidence Distribution

| Level | Campaign-Wide | Expected Range |
|-------|---------------|----------------|
| [Confirmed] | 76% | Healthy — below the 80% over-confidence flag |
| [Inferred] | 17% | Normal for a small library with external boundaries |
| [Uncertain] | 7% | Below the 30% under-analyzed flag — good |

Confidence distribution is healthy: no over-confidence (Confirmed < 80% flag) and no
under-analysis (Uncertain < 30% flag). Small-codebase caveat from the domain profile applies —
[Inferred]/[Uncertain] findings carry higher uncertainty than they would in a large corpus.

## Guidance

- **Clarity (WARN)** is expected: all 12 SME questions are open pending domain-expert review.
  The two highest-value are (1) whether injection-relevant options are ever user-supplied in
  production and (2) whether `examples/` is deployable. Resolving these two determines the
  severity of the P0 security findings.
- **File coverage (WARN)** is a false negative — 100% of source is analyzed. No action.
- All other dimensions PASS. The analysis is trustworthy for planning, with the standard
  small-sample confidence caveat.
