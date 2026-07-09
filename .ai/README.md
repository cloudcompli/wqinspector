---
artifact_type: navigation-hub
generated_by: /finalize-analysis
generated_at: 2026-07-09T00:00:00Z
---

# wqinspector (WQInvestigator) — Analysis Navigation

## Where to Start

- **New to this project?** Start with [DELIVERY-SUMMARY.md](DELIVERY-SUMMARY.md) (2 min read)
- **Decision-maker?** Read [EXECUTIVE-READOUT.md](EXECUTIVE-READOUT.md) (20 min read)
- **Architect?** Browse research/ — each topic has a DOMAIN-BRIEF.md with architecture and business rules
- **Developer?** Go to research/{your-domain}/DOMAIN-BRIEF.md, then drill into per-file *-analysis.md docs
- **Security reviewer?** Start with research/domain-adapters/DOMAIN-BRIEF.md (SOQL injection) and research/examples-and-entry-points/esmr.php-analysis.md (XSS + injection reachability)

## Analysis Corpus

| Domain | Files | Description | Entry Point |
|--------|-------|-------------|-------------|
| Domain Adapters | 4 | CIWQS/SMARTS/OCPW source adapters; SOQL builders (injection risk) | research/domain-adapters/DOMAIN-BRIEF.md |
| Support Infrastructure | 5 | Base classes + OptionsTrait; parsing, caching, pagination | research/support-infrastructure/DOMAIN-BRIEF.md |
| Examples / Entry Points | 5 | Runnable reference scripts; esmr.php `$_GET` web form | research/examples-and-entry-points/DOMAIN-BRIEF.md |

## Directory Structure

```
.ai/
  README.md                          ← You are here
  DELIVERY-SUMMARY.md                ← Report card (start here)
  EXECUTIVE-READOUT.md               ← Executive brief
  bootstrap/                         ← Domain profiling artifacts
  scans/                             ← Codebase survey and execution state
  research/                          ← Domain-organized deep research
    {topic}/DOMAIN-BRIEF.md          ← Topic roll-up (read first per topic)
    {topic}/*-analysis.md            ← Per-source-file analysis (drill down)
  analysis/                          ← Consolidated report + batch shims
  synthesis/                         ← Cross-topic synthesis + quality scorecard
```

## Methodology

This analysis was produced using the **MRI (Codebase Analysis Methodology)** framework with the
**6C Quality Framework**:

- **Coverage** — percentage of source files analyzed
- **Completeness** — depth of business rule extraction
- **Compliance** — adherence to output schema standards
- **Currency** — freshness of analysis relative to current methodology
- **Clarity** — resolution rate of SME questions
- **Stability** — consistency of findings across analysis runs

## Confidence Levels

| Marker | Meaning | Evidence Required |
|--------|---------|-------------------|
| [Confirmed] | Verified through multiple evidence sources | 2+ independent sources |
| [Inferred] | Reasonable conclusion from available evidence | Documented assumptions |
| [Uncertain] | Hypothesis requiring validation | Flagged for SME review |

## What's Next

After reviewing the deliverables:
1. **Validate findings** — use `/feedback-add` to capture corrections, confirmations, and investigations (start with the 2 security-gating SME questions).
2. **Bulk review** — use `/feedback-ingest` to process feedback from GitHub PR comments.
3. **Re-finalize** — after incorporating feedback, re-run `/finalize-analysis` to update deliverables.
