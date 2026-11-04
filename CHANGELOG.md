# Changelog

All notable changes to this repository are documented in this file.

The format is based on Keep a Changelog and this repository follows Semantic Versioning for tagged releases.

## [Unreleased]

### Changed

- `version-drift-enforcement.yml`: move off the deprecated Node.js 20 action runtime. `actions/create-github-app-token` goes from `v1.12.0` to `v3.0.0` (`f8d387b`) and `actions/checkout` goes from `v4.2.2` to `v5.0.0` (`08c6903`). Both run on `node24`. Inputs are unchanged. Other workflows still use the Node 20 pins; they are left for a separate change.

### Added

- Repository governance baseline artifacts: CONTRIBUTING, SUPPORT, CODE_OF_CONDUCT, issue templates, and PR template.
- Repository-level CI check for dependency review.
