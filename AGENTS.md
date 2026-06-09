# SPARXSTAR Standards Repository — Agent Guide

This file guides autonomous agents maintaining standards in this repository.

## 1) Repository Role

This repository is policy infrastructure. Its output is enforceable standards and enforcement matrices that other repositories implement.

Do not turn this repository into product-specific documentation.

## 2) Canonical Document Model

- **Core law:** `docs/standards-handbook.md`
- **Implementation standards:** `docs/php-wordpress-standard.md`, `docs/javascript-react-standard.md`, `docs/node-standard.md`, `docs/css-standard.md`, `docs/media-upload-standard.md`
- **Enforcement mapping:** `docs/enforcement-matrix.md`, `SPARXSTAR-CI-Enforcement-Matrix.md`
- **Legacy/reference corpus:** `.github/instructions/*`, root legacy standards

When conflicts appear, move validated content into canonical docs and align references.

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
