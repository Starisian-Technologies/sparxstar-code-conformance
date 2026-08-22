# Changelog

All notable changes to this repository are documented in this file.

The format is based on Keep a Changelog and this repository follows Semantic Versioning for tagged releases.

## [Unreleased]

### Fixed

- `php-enforcement`, `pnpm-enforcement`, and `css-enforcement` no longer require
  the dependency resolver credential in order to run. The credential is now
  declared `required: false`, and each workflow inspects the repository's own
  manifest to decide whether a private source is actually involved: the app
  token is minted only when one is, and the job fails with an explicit error if
  a private source is declared but no credential is available. Previously the
  secret was `required: true` and the token was minted whenever a manifest
  existed, so any run that could not read Actions secrets — notably every run
  triggered by an automated dependency bot, which reads a separate secrets
  store — failed at the token step before any check executed. Mirrors the
  `check-secrets` pattern already used by `full-quality-gate`.

### Added

- Repository governance baseline artifacts: CONTRIBUTING, SUPPORT, CODE_OF_CONDUCT, issue templates, and PR template.
- Repository-level CI check for dependency review.
