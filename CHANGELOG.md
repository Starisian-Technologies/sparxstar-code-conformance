# Changelog

All notable changes to this repository are documented in this file.

The format is based on Keep a Changelog and this repository follows Semantic Versioning for tagged releases.

## [Unreleased]

### Changed

- All workflows: move every action off the deprecated Node.js 20 runtime. Each pin is SHA-pinned with a major-version comment and runs on `node24` at that tag.
  - `actions/create-github-app-token` v1.12.0 → v3.3.0. Every mint step now passes `client-id:` instead of `app-id:`, with the same `*_CLIENT_ID` variables and secrets (owner ruling, resolves OD-2).
  - `actions/checkout` v4.2.2 → v5.1.0. `actions/setup-node` v4.1.0/v4.4.0 → v5.0.0. `actions/dependency-review-action` v4.9.0 → v5.0.0.
  - `pnpm/action-setup` v4.1.0 → v4.4.0. `shivammathur/setup-php` 2.31.1 → 2.40.0. `softprops/action-gh-release` v2.6.2 → v3.0.3.
  - `DavidAnson/markdownlint-cli2-action` → v23.2.0. The old pin named a commit that does not exist upstream. v23 bundles markdownlint-cli2 0.22.1, which adds rules MD059 and MD060, enabled by default.
- `css-enforcement.yml`, `node-enforcement.yml`, `react-enforcement.yml`: set `package-manager-cache: false` on setup-node. v5 caches automatically when `package.json` has a `packageManager` field, and these jobs enable pnpm through corepack only after setup-node runs.

### Added

- Repository governance baseline artifacts: CONTRIBUTING, SUPPORT, CODE_OF_CONDUCT, issue templates, and PR template.
- Repository-level CI check for dependency review.
