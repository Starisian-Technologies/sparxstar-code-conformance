# SPARXSTAR Standards Repository — Agent Guide

This file guides autonomous agents maintaining standards in this repository.

## 1) Repository Role

This repository is policy infrastructure. Its output is enforceable standards and enforcement matrices that other repositories implement.

This repo is the **HOW**: code-agnostic standards, per-language implementation rules, and enforcement matrices. The **WHY / WHAT** (architecture decisions, invariants, open questions, cross-repo schemas, repo boundary review) lives in the companion private repo `sparxstar-platform-decisions` (a.k.a. `sparxstar-architecture-decision-record`).

Do not turn this repository into product-specific documentation. Do not restate ADR text or invariant text; cite by number.

## 2) Canonical Document Model

- **Core law:** `docs/standards-handbook.md`
- **Implementation standards:** `docs/php-wordpress-standard.md`, `docs/javascript-react-standard.md`, `docs/node-standard.md`, `docs/css-standard.md`, `docs/media-upload-standard.md`
- **Enforcement mapping:** `docs/enforcement-matrix.md`, `SPARXSTAR-CI-Enforcement-Matrix.md`
- **Legacy/reference corpus:** `.github/instructions/*`, root legacy standards

When conflicts appear, move validated content into canonical docs and align references.

## 2a) Platform Decisions Cross-Reference (companion repo)

The companion repo `sparxstar-platform-decisions` holds the append-only registry of platform law:

- `decisions/ADR-NNN-*.md` — architecture decision records (append-only; Accepted ADRs are immutable; supersede, never edit).
- `invariants.md` — falsifiable platform-wide rules (INV-NNN). Cite numbers; never restate text.
- `open-questions.md` — OQ-NNN. Block work that depends on an OQ in `OPEN` state.
- `specs/` — cross-repo table schemas.
- `PRODUCT-ROLE-BOUNDARY.md` — per-product role and boundary statements.

Citation conventions for standards text and commit messages in this repo:

```
# Per ADR-008: two doors, one chain
# See INV-009: deny nothing; quarantine instead
# Blocked on OQ-001 (contributor-identity keystone) — RESOLVED by ADR-012
# Schema per specs/morpheme-tier-tables.md
```

Conformance checks before editing standards text:

1. Does the proposed text contradict an INV? → Block; cite the number.
2. Does the proposed text assume a `OPEN` OQ is resolved? → Block; cite the OQ.
3. Does the proposed text duplicate an ADR or invariant statement? → Replace with a citation.
4. Does the proposed text drift from a `specs/` schema? → Flag as spec drift.
5. Does the change touch Patent Family A/B flagged areas? → Stop. Flag for owner review.

If the companion repo is inaccessible, do not fabricate ADR/INV/OQ numbers from memory or from references inside other repos. Ask for access.

## 3) What Good Changes Look Like

- Keep standards measurable and enforceable.
- Keep global principles language-agnostic.
- Add stack-specific constraints only where justified by runtime, security, or tooling.
- Preserve stable rule IDs and matrix references where possible.

## 4) Required Coverage Domains

Maintain explicit and coherent standards coverage for:

- PHP / WordPress
- JavaScript / React / Node
- CSS
- SQL / PostgreSQL
- Neo4j
- XML / JSON
- Laravel
- Vite

Coverage can be direct (dedicated section/file) or inherited (global + implementation matrix), but must be explicit and reviewable.

## 5) Content Quality Rules

- Avoid product-level coupling or single-repo assumptions.
- Use normative language (`MUST`, `MUST NOT`, `REQUIRED`, `FORBIDDEN`) for enforceable rules.
- Separate architectural requirement from illustrative examples.
- Keep security and privacy constraints explicit.

## 6) Enforcement-First Authoring

For each new or changed rule, maintain:

1. Rule statement
2. Enforcement status (`ENFORCED`, `WARN`, `SPECIFIED`, `REFERENCE`, `RESERVED`)
3. Tooling path (lint/static/test/build/review gate)
4. CI stage placement

If enforcement is not yet automated, mark it `SPECIFIED` and do not weaken requirement language.

## 7) Security Baselines for Standards Text

- Require sanitize -> validate -> escape ordering where applicable.
- Require parameterized database queries.
- Require capability checks and fail-closed behavior for governed actions.
- Prohibit secret leakage in examples and templates.

## 8) Agent Workflow

1. Review canonical docs and relevant `.github/instructions/` references.
2. Propose smallest coherent documentation delta.
3. Update canonical files first, then index/reference docs.
4. Verify cross-file consistency (scope, terminology, status labels, rule IDs).
5. Preserve code-agnostic framing unless stack specificity is required.

## 9) Do Not Do

- Do not introduce contradictory wording between handbook and matrices.
- Do not remove constraints from canonical docs without updating enforcement status and rationale.
- Do not add organization-private secrets, credentials, or confidential values.
- Do not downgrade non-negotiable safety or governance principles.
