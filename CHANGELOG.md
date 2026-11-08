# Changelog

All notable changes to this repository are documented in this file.

The format is based on Keep a Changelog and this repository follows Semantic Versioning for tagged releases.

## [Unreleased]

### Fixed

- `version-drift-enforcement.yml`: the drift-record push now retries. On a rejected push it refetches `sparxstar-architecture-governance-registry` `main`, regenerates the caller's record on top of it, and pushes again: up to 5 attempts with a short backoff, then the step fails with an explicit error. Each caller writes its own file, so retries never conflict on content, and callers need no `concurrency:` group for the write. Replaces the single `git pull --rebase` + push, which failed an otherwise passing check when two runs overlapped.

### Added

- First-screen page weight and platform fonts (owner ruling 2026-11-04, OQ-008, pending ADR), recorded platform-wide and SPECIFIED until a CI gate checks them:
  - `docs/standards-handbook.md` §0.4: 100 KB compressed cap for a UI's whole first screen, web fonts and inline SVG included; no images on the first screen.
  - `docs/css-standard.md` §6.2: Inter as one subsetted WOFF2 file covering Latin, Latin Extended and African-orthography characters (verify ŋ, ɲ, ɛ, ɔ and tone-marked vowels; report the size). Google Sans and Fira only where Inter can't cover a character, reported in the PR. No Noto. `font-display: swap` only; `optional` is no longer allowed.
  - `CI-Enforcement-Matrix.md`: rows SYS-008 and CSS-004.
- `cross-repo/set-repo-credentials.sh` (#40): owner script that sets a private repo's repository-level credentials (both Client ID variables; both private keys as Actions and Dependabot secrets; optional `ANTHROPIC_API_KEY` from a hidden prompt), then verifies by listing names only. Has `--dry-run`; skips public repos; stops if `gh` isn't logged in. Usage is documented in `cross-repo/cross-repo-access-and-installation.md` under "Adding a private repo".

### Changed

- All workflows: move every action off the deprecated Node.js 20 runtime. Each pin is SHA-pinned with a major-version comment and runs on `node24` at that tag.
  - `actions/create-github-app-token` v1.12.0 → v3.3.0. Every mint step now passes `client-id:` instead of `app-id:`, with the same `*_CLIENT_ID` variables and secrets (owner ruling, resolves OD-2).
  - `actions/checkout` v4.2.2 → v5.1.0. `actions/setup-node` v4.1.0/v4.4.0 → v5.0.0. `actions/dependency-review-action` v4.9.0 → v5.0.0.
  - `pnpm/action-setup` v4.1.0 → v4.4.0. `shivammathur/setup-php` 2.31.1 → 2.40.0. `softprops/action-gh-release` v2.6.2 → v3.0.3.
  - `DavidAnson/markdownlint-cli2-action` → v23.2.0. The old pin named a commit that does not exist upstream. v23 bundles markdownlint-cli2 0.22.1, which adds rules MD059 and MD060, enabled by default.
- `.markdownlint.json`: turn off MD059 (descriptive-link-text) and MD060 (table-column-style) for this release. Turning them on is a separate future decision; with them on, this repo's docs have 150 MD060 violations. `markdownlint.yml` lints each caller with the caller's own config, so this file does not change what callers see.
- `css-enforcement.yml`, `node-enforcement.yml`, `react-enforcement.yml`: set `package-manager-cache: false` on setup-node. v5 caches automatically when `package.json` has a `packageManager` field, and these jobs enable pnpm through corepack only after setup-node runs.

### Added

- Repository governance baseline artifacts: CONTRIBUTING, SUPPORT, CODE_OF_CONDUCT, issue templates, and PR template.
- Repository-level CI check for dependency review.
