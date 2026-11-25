# Changelog

## [1.2.0](https://github.com/Starisian-Technologies/sparxstar-code-conformance/compare/v1.1.0...v1.2.0) (2026-11-25)


### Features

* **release:** release this repo with semver-release.yml; retire manual tagging ([#61](https://github.com/Starisian-Technologies/sparxstar-code-conformance/issues/61)) ([6201677](https://github.com/Starisian-Technologies/sparxstar-code-conformance/commit/62016775a90123c7b05ee5556525433a29d23dab))
* **release:** shared SemVer release workflow and config template (ADR-053) ([#58](https://github.com/Starisian-Technologies/sparxstar-code-conformance/issues/58)) ([81699ff](https://github.com/Starisian-Technologies/sparxstar-code-conformance/commit/81699ff517d1b2ccb7bb314cf64915d1cbdf123c))
* **security:** shared credential-policy check for every repo (ADR-056) ([#62](https://github.com/Starisian-Technologies/sparxstar-code-conformance/issues/62)) ([b7c24f4](https://github.com/Starisian-Technologies/sparxstar-code-conformance/commit/b7c24f4b16955272b8ce38f08a62d77a7fae2aca))


### Bug Fixes

* **workflows:** run NODE-LOG-001 and JS-CONSOLE-001 in gate mode ([#63](https://github.com/Starisian-Technologies/sparxstar-code-conformance/issues/63)) ([d6beea3](https://github.com/Starisian-Technologies/sparxstar-code-conformance/commit/d6beea3dd08fb78442cc6f9d3a6b14740df65098)), closes [#59](https://github.com/Starisian-Technologies/sparxstar-code-conformance/issues/59)

## Before release-please (hand-kept)

Every entry above this section is written by release-please from Conventional Commits (ADR-053); do not edit release
entries by hand. The entries below were written by hand before this repository adopted `semver-release.yml`. They cover
changes up to and including the adoption, and are not tied to a single release.

### Added

- `semver-release.yml`: the one reusable release workflow every repository calls (ADR-053). It validates the caller's
  release-please config, runs release-please (`googleapis/release-please-action`, pinned by commit SHA) to keep
  `CHANGELOG.md` and open the release pull request, and on merge creates the `vX.Y.Z` tag and GitHub Release. With
  `image_name` set it pushes the image to GHCR tagged with the version and the full commit SHA, never `latest`, and
  refuses to rebuild an existing version. Optional App credential for release pull requests (OQ-025, open).
- `caller-templates/release.yml`, `caller-templates/release-please-config.json`,
  `caller-templates/.release-please-manifest.json`: the caller and config templates. The template's `uses:` pin is a
  lookup placeholder, not a hand-written version.
- `release-self-test.yml` and `fixtures/release/`: prove the config check passes the template and fails each bad
  fixture, and that every action in `semver-release.yml` is SHA-pinned.

- `pnpm-enforcement.yml`: optional `node-version` input. When set, the job runs `actions/setup-node` (the same pinned v5
  SHA as the other enforcement workflows, `package-manager-cache: false`) before the `NODE-PKG-004` install dry run.
  Empty, the default, keeps the runner image's Node exactly as before, so existing callers are unchanged. Repositories
  with `engine-strict=true` and an `engines.node` above the runner default (3iAtlas on Node 26) can now pass their
  floor.

- `cross-repo/cross-repo-access-and-installation.md` Block E, private npm packages (owner ruling 2026-11-18). A package is published privately to GitHub Packages' npm registry from its own release workflow (organisation scope, `publishConfig.registry`, `packages: write`). Each consumer is granted read access once in the package's **Manage Actions access**, and its CI installs with its own `GITHUB_TOKEN` and `packages: read`; no App or personal token is used. Installing on a developer machine is not covered: it would need a personal token, which this document forbids. The decision tree and the "Never" list gain matching entries.

### Changed

- `node-enforcement.yml`, `react-enforcement.yml`, `pnpm-enforcement.yml`: the `node-version` input now defaults to
  `'26'`, the platform runtime (Active LTS). It was `'22'` for Node and React, and empty (the runner image's Node) for
  pnpm. Callers that pass `node-version` are unchanged. A caller that relied on the old default and must stay on it
  passes `node-version: '22'` (or `''` for pnpm). All three install corepack themselves when the runtime lacks it.

- This repository's own CI uses fewer Actions minutes. `fixture-self-test.yml` runs on pushes to `main` only (it also ran on
  every branch and tag push, so each PR commit ran it twice), and `composer install` in its config-fixtures job reuses a
  cached Composer download directory. `fixture-self-test.yml` and `dependency-review.yml` cancel a run superseded by a
  newer push to the same PR or branch, and dependency review no longer runs when a PR changes only documentation.
  Reusable workflows are unchanged.

- `caller-templates/standards-*.yml`: every `sparxstar-code-conformance` pin and `contract_ref` move `v1.0.4` → `v1.0.6`, so new callers get the drift-record push retry. No callee's declared secrets or inputs changed between the two tags.

### Fixed

- Node 25 and later no longer bundle corepack, so every enforcement and standards workflow that ran `corepack enable`
  failed on `node-version: '26'`. `css-enforcement.yml`, `css-standards.yml`, `formatting.yml`, `js-standards.yml`,
  `node-enforcement.yml`, `pnpm-enforcement.yml` and `react-enforcement.yml` now install `corepack@0.36.0` first when
  the runtime has no `corepack`. On Node 24 and earlier the install is skipped and behaviour is unchanged.
- `css-standards.yml`, `formatting.yml`, `js-standards.yml`: `actions/setup-node` no longer sets `cache: 'pnpm'`.
  pnpm only exists after the corepack step that follows it, so the cache lookup could not find `pnpm`. They now use
  `package-manager-cache: false`, like the enforcement workflows.
- `pnpm-enforcement.yml` `NODE-PKG-001`: a line whose only npm install is `npm install -g corepack[@x.y.z]` (also `npm
  i`, `--global`) is no longer reported. On Node 25 and later that is how pnpm itself is bootstrapped. Any other
  `npm`/`yarn` install, including one chained on the same line, an unpinned package such as `corepack-evil`, or a bare
  `npm i`, is still reported.
- `version-drift-enforcement.yml` (released in `v1.0.6`; `v1.0.5` predates it): the drift-record push now retries. On a rejected push it refetches `sparxstar-architecture-governance-registry` `main`, regenerates the caller's record on top of it, and pushes again: up to 5 attempts with a short backoff, then the step fails with an explicit error. Each caller writes its own file, so retries never conflict on content, and callers need no `concurrency:` group for the write. Replaces the single `git pull --rebase` + push, which failed an otherwise passing check when two runs overlapped.

### Added

- `docs/php-wordpress-standard.md` §10.3: how `composer.lock` is regenerated (owner ruling
  2026-11-13, OQ-009, pending ADR), SPECIFIED until a CI gate checks it. Composer folds all of
  `extra` into the lock content hash, so a metadata-only edit invalidates the lock. Regeneration
  must use the Composer release matching the lock's recorded `plugin-api-version`, read from the
  lock rather than assumed, and must produce a `content-hash`-only diff. Matrix row `PHP-009`.
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
