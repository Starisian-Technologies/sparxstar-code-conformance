# SPARXSTAR Copilot Instructions

This repository defines coding standards. Treat this repo as the source of policy, not product implementation.

## Mission

- Keep standards enforceable, testable, and technology-agnostic by default.
- Encode stack-specific rules only where implementation constraints demand it.
- Preserve consistency between `docs/`, root reference files, and agent guidance.

## Authoritative Sources

1. `docs/standards-handbook.md` (global law)
2. `docs/*-standard.md` (implementation standards)
3. `docs/enforcement-matrix.md` and `SPARXSTAR-CI-Enforcement-Matrix.md` (enforcement mapping)

Legacy and draft materials under `.github/instructions/` are input references; they are not final authority unless merged into the files above.

## Non-Negotiable Engineering Rules

- If a rule cannot be enforced or verified, it is incomplete.
- Sanitize -> Validate -> Escape (in that order).
- No silent failure.
- Bounded execution and explicit limits are mandatory.
- Idempotency is required for retriable writes.
- Platform abstractions are required; do not hardwire provider-specific behavior.
- Prefer fail-closed behavior for authority, trust, and safety decisions.

## Multisite and Platform Constraints

- Assume WordPress multisite from the start.
- Use `$wpdb->prefix`; never hardcode `wp_`.
- Distinguish site options from network options.
- Require capability checks for governed actions.

## Stack-Specific Coverage Baseline

All maintenance must keep standards current for:

- PHP
- WordPress
- JavaScript
- React
- CSS
- SQL
- PostgreSQL
- Neo4j
- XML
- JSON
- Laravel
- Vite

When a stack lacks a dedicated standard file, update the handbook and enforcement matrices to capture explicit rules and enforcement status.

## Security and Quality Guardrails

- Never commit credentials, tokens, or secrets.
- Require prepared/parameterized database access.
- Require documented public APIs.
- Keep CI stages explicit and ordered (lint, static analysis, tests, build, security, review gates).

## Documentation Maintenance Rules

- Update cross-references whenever files move or split.
- Keep wording policy-level, not project-feature specific.
- Keep examples illustrative; avoid embedding repository-specific implementation assumptions.
- Record changes in canonical docs first; then align reference summaries.
