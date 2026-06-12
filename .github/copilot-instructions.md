# Starisian Technologies — Copilot Instructions

This repository defines coding standards. Treat this repo as the source of policy, not product implementation.

## Mission

- Keep standards enforceable, testable, and technology-agnostic by default.
- Encode stack-specific rules only where implementation constraints demand it.
- Preserve consistency between `docs/`, root reference files, and agent guidance.

## Authoritative Sources

1. `docs/standards-handbook.md` (global law — the HOW)
2. `docs/*-standard.md` (implementation standards)
3. `docs/enforcement-matrix.md` and `CI-Enforcement-Matrix.md` (enforcement mapping)
4. Each product's own decision registry (the WHY / WHAT — ADRs, invariants, open questions, cross-repo specs)

Legacy material at the root (`ENGINEERING-STANDARDS.md`) is an input reference; it is not final authority unless merged into the files above.

## Decisions Cross-Reference Discipline

- Cite ADR-NNN, INV-NNN, OQ-NNN by number in standards text and commit messages. Never paraphrase or restate decision/invariant text.
- Do not write rules that contradict an invariant or assume an `OPEN` open question is resolved.
- If you cannot read the relevant product's decision registry, do not fabricate numbers from memory — request access.

## Trademark Discipline

- This is the **organization-wide** standards repo. No product names, repo names, service names, or trademarks appear here.
- If a rule only makes sense with a product name attached, the rule belongs in that product's repo, not here.
- Refer to capabilities by their generic role: "the authority layer", "the auth SDK", "the audio capture SDK", "the runtime layer".

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
