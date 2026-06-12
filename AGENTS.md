# Starisian Technologies — Standards Repository Agent Guide

This file guides autonomous agents maintaining standards in this repository.

**Trademark rule:** zero product names, repo names, concept names, or anything trademarkable lands in this repo. If a rule only makes sense with a product name attached, the rule belongs in that product's repo, not here. See [`docs/standards-catalog.md`](docs/standards-catalog.md).

## 1) Repository Role

This repository is policy infrastructure. Its output is enforceable standards and enforcement matrices that other repositories implement.

This repo is the **HOW** for the organization: code-agnostic standards, per-language implementation rules, and enforcement matrices. The **WHY / WHAT** (architecture decisions, invariants, open questions, cross-repo schemas, repo boundary review) lives in each product's own decision registry — never here.

Do not turn this repository into product-specific documentation. Do not name products, repos, services, or trademarks. Do not restate ADR text or invariant text; cite by number.

## 2) Canonical Document Model

- **Core law:** `docs/standards-handbook.md`
- **Implementation standards:** `docs/php-wordpress-standard.md`, `docs/javascript-react-standard.md`, `docs/node-standard.md`, `docs/css-standard.md`, `docs/media-upload-standard.md`
- **Enforcement mapping:** `docs/enforcement-matrix.md`, `CI-Enforcement-Matrix.md`
- **Legacy reference:** `ENGINEERING-STANDARDS.md` at the root.

When conflicts appear, move validated content into canonical docs and align references.

## 2a) Architecture Decisions Cross-Reference

Each product has its own decision registry holding the append-only record of architecture decisions:

- `decisions/ADR-NNN-*.md` — architecture decision records (append-only; Accepted ADRs are immutable; supersede, never edit).
- `invariants.md` — falsifiable rules (INV-NNN). Cite numbers; never restate text.
- `open-questions.md` — OQ-NNN. Block work that depends on an OQ in `OPEN` state.
- `specs/` — cross-repo table schemas.
- Role / boundary statements per product.

Citation conventions for standards text and commit messages in this repo:

```
# Per ADR-NNN: <decision shorthand>
# See INV-NNN: <invariant shorthand>
# Blocked on OQ-NNN — RESOLVED by ADR-MMM
# Schema per specs/<schema-file>
```

Conformance checks before editing standards text:

1. Does the proposed text contradict an INV? → Block; cite the number.
2. Does the proposed text assume a `OPEN` OQ is resolved? → Block; cite the OQ.
3. Does the proposed text duplicate an ADR or invariant statement? → Replace with a citation.
4. Does the proposed text drift from a `specs/` schema? → Flag as spec drift.
5. Does the proposed text name a product, repo, service, or trademark? → Replace with the generic concept; if no generic concept exists, the rule belongs in the product's repo, not here.

If the relevant decision registry is inaccessible, do not fabricate ADR/INV/OQ numbers from memory or from references inside other repos. Ask for access.

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
