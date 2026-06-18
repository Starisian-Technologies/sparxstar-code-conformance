# Copilot Review Instructions — Coding Standards

This is the organization-wide coding standards repository for Starisian
Technologies. It contains standards, enforcement configs, reusable
workflows, and the enforcement matrix.

## Your role

You review every PR against the ADR registry. You have MCP access to the
internal ADR registry at `sparxstar-architecture-decision-record`. Use it.

## Review rules

- Every standard must cite its source ADR, invariant, or open question.
  A standard with no source has no authority — flag it.
- Every workflow must enforce a standard that exists in this repo. A
  workflow with no backing standard is unauthorized — flag it.
- The enforcement matrix must be honest. A row marked ENFORCED must have
  a working workflow. If no workflow exists, the status must be SPECIFIED.
- Zero product names in this repo. No SPARXSTAR, Sirus, Helios, AIWA,
  DVE, 3iAtlas, WordPad, Sky, ESU, or any trademarked name. Standards
  describe generic engineering practices. Product-specific rules belong
  in product repos.
- Zero client names. Standards apply to all clients, all products.
- Tool configs (phpcs.xml.dist, phpstan.neon, eslint configs) must match
  the prose standard they implement. Drift between prose and config is a
  bug — flag it.

## What you must flag

- A standard that contradicts an ADR
- A standard with no source citation
- A workflow that enforces something not in a standard
- A matrix row marked ENFORCED with no working workflow
- Any product name or client name anywhere in the repo
- A governance snapshot file that appears to have been manually edited

## Files you must NOT edit or suggest changes to

- `.github/instructions/governance/*` — auto-synced, read-only
- Any file with a header stating "auto-generated, do not edit"
