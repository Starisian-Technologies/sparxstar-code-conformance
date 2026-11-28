# Adopting Reusable Workflows from Coding Standards

Every product repo calls reusable workflows from the coding-standards repo
instead of writing its own CI checks. This keeps enforcement consistent
and means a standard update fixes every repo at once.

## Source repo

`Starisian-Technologies/sparxstar-code-conformance`
(public — no auth needed to call reusable workflows)

## How to add a reusable workflow

Create one file in your repo: `.github/workflows/standards.yml`

This single file can call one or more reusable workflows. You don't need
a separate workflow file per check — one file, multiple jobs.

> **Pin an exact release tag in every example below.** `vX.Y.Z` in the
> examples stands for the latest release tag of this repository; look it up
> (see "Pin an exact release tag") and never copy a number from a document.
> Never `@main`, never the frozen `@v1` alias.

```yaml
# .github/workflows/standards.yml
name: Platform Standards

on:
  pull_request:
  push:
    branches: [main]

jobs:
  pnpm:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate

  # Additional domain workflows (PHP, JS, CSS, formatting, etc.) will be added
  # to this repository over time. Add their jobs here once they exist under
  # .github/workflows/ in this repo.
```

## Which jobs to include

Not every repo needs every job. Include only what applies:

| Job | Workflow | Include when |
|---|---|---|
| `pnpm` | `pnpm-enforcement.yml` | Repo has a `package.json` |
| `php` | `php-enforcement.yml` | Repo has PHP code |
| `react` | `react-enforcement.yml` | Repo is a standalone React app |
| `node` | `node-enforcement.yml` | Repo is a standalone Node/TS service |
| `css` | `css-enforcement.yml` | Repo has CSS or stylesheets |
| `media` | `media-enforcement.yml` | Repo records, uploads, or processes audio/video |
| `changes` | `changed-paths.yml` | **Always** — first job; gates every other job (CI cost rules) |
| `credentials` | `credential-policy.yml` | **Always** — every repo (ADR-056; also checks the CI cost rules) |
| `analyze` | `caller-templates/codeql.yml` | Repo has CodeQL-supported code (CI cost rules: PRs + weekly, not on push) |
| `js` / `css` / `formatting` / `markdownlint` | `js-standards.yml`, `css-standards.yml`, `formatting.yml`, `markdownlint.yml` | ESLint + `tsc`, Stylelint, Prettier, markdownlint — template `caller-templates/standards-lint.yml`, self-test `lint-workflows-self-test.yml` |

PHP-only repos (no JS) skip the `react`, `node`, and `css` jobs.

Retired (owner ruling, 2026-11-25; no repository called them):
`php-standards.yml` (use `php-enforcement.yml`), `wp-plugin-check.yml` and
`full-quality-gate.yml`. The rules they covered are listed in
`CI-Enforcement-Matrix.md` with their current status.
JS/React repos (no PHP) skip the `php` job.

---

## Adopting these workflows in your repo

This section takes a repo from nothing to an enforcing pull request. If you
follow it top to bottom and your repo type is listed, you do not need to read
any other file in this repository.

### Before you start

Confirm two things:

- You know your **repo type**. Pick exactly one:
  - `wp-plugin` — a WordPress plugin or mu-plugin (PHP).
  - `standalone-react` — a React app or component library (TypeScript/JS).
  - `standalone-node` — a Node service or library, no React (TypeScript/JS).
- You have looked up the **latest release tag** of `sparxstar-code-conformance`
  (see "Pin an exact release tag" below). You pin to it in step 2. Pinning to
  `@main` is not allowed.

### Step 1 — Copy the caller template for your repo type

Each repo type has a ready-made caller in `caller-templates/`. Copy the one
that matches your repo into your repo's `.github/workflows/` directory as
**`standards.yml`**:

| Your repo type | Copy this file | Into your repo at |
|---|---|---|
| `wp-plugin` | `caller-templates/standards-php-wordpress.yml` | `.github/workflows/standards.yml` |
| `standalone-react` | `caller-templates/standards-react.yml` | `.github/workflows/standards.yml` |
| `standalone-node` | `caller-templates/standards-node.yml` | `.github/workflows/standards.yml` |

The template is the only file you add. You do not copy the enforcement
workflows themselves — they live here and your caller references them.

### Step 2 — Pin to an immutable patch tag and set the enforcement mode

Open the file you just copied. Two things must be set correctly:

1. **Every `RELEASE_TAG` placeholder is replaced with the latest release
   tag**, on each `uses:` line and in `contract_ref`. Look the tag up (see "Pin
   an exact release tag"); do not change it to `@main` or `@v1`.
2. **`enforcement_mode` is set to `advisory` for new consumers.** New repos
   start advisory (warn-only) so onboarding is never blocked by a gate the
   repo does not yet pass. Switch to `gate` only when all violations are
   resolved — see "Advisory first, gate when clean" below.

Do not leave `enforcement_mode` unset. The empty value falls back to a
deprecated legacy `mode` input that is scheduled for removal on 2027-01-01.

**Immutable pins never move on their own — plan for that.** Every current caller
template includes a `version-check` job (`version-drift-enforcement.yml`) that warns
when your pin falls behind the recommended patch tag and hard-fails if it drops below
the floor in `config/version-policy.yml`, regardless of `enforcement_mode`. Every
repository also copies `caller-templates/dependabot.yml` to `.github/dependabot.yml`
(required; see "CI cost rules" below). Its `github-actions` entry
understands any `uses: owner/repo@vX.Y.Z` line and opens a bump PR when an
upstream workflow you pin cuts a new release.

### Step 3 — Add the exceptions file (only if you need an exception)

If a specific rule cannot pass yet and you need a time-boxed waiver, create
`.standards/standards-exceptions.yml` in your repo root. Every exception entry
requires all six fields — a missing field or a past `expires` date **fails the
build closed**, on purpose:

```yaml
exceptions:
  - id: EXC-001                      # unique id for this exception
    rule: CSS-TYPE-001               # the rule id being waived
    reason: "Legacy theme uses px font sizes; migrating in Q3."
    owner: "@your-github-handle"     # who owns getting this fixed
    expires: "2026-09-30"            # ISO date; after this, the build fails
    approval: "ADR-019"              # the decision authorizing the waiver
```

If you have no exceptions, do not create this file. Its absence is fine and
means "no waivers."

### Step 4 — Commit, open a PR, and read the check

Commit the caller file (and the exceptions file if you added one), push, and
open a pull request. The enforcement workflow runs as a check on the PR.

- In `advisory` mode: the check passes, and any violations appear as warning
  annotations in the PR.
- In `gate` mode: the check fails the PR if any violation is found, with
  the rule id and the offending file/line in the log.

You are now enforcing. That is the whole adoption.

---

## Worked example: enforcing a wp-plugin repo end to end

This is the complete set of changes for a WordPress plugin repo, going from
no enforcement to a blocking gate.

**1. Copy the template.** Copy `caller-templates/standards-php-wordpress.yml`
to `.github/workflows/standards.yml` in your repo. After copying,
the file looks like this:

```yaml
name: Standards (PHP / WordPress)
on:
  pull_request:
  push:
    branches: [main]
jobs:
  php:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/php-enforcement.yml@vX.Y.Z
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  css:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/css-enforcement.yml@vX.Y.Z
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@vX.Y.Z
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    # media-enforcement.yml declares no secrets, so none are passed.
```

**2. Update pins and set advisory mode.** Update every `uses:` line to end in
the latest release tag, looked up as in "Pin an exact release tag". Set
every `enforcement_mode` to `advisory` for a new consumer — do not flip to
`gate` until violations are zero (see "Advisory first, gate when clean"
below). Set `repo_type` to `wp-plugin` everywhere.

**3. Clean up violations in advisory mode.** With `enforcement_mode: advisory`,
merge the caller file, let the gate run, and fix all reported violations.
Once violations are zero, switch each gate to `enforcement_mode: gate`
in a follow-up PR — that is the deliberate, recorded flip to blocking.

**4. Commit and open the PR.** Add `.github/workflows/standards.yml`,
push the branch, open the PR. The three jobs (php, css, media) run. In
`advisory` mode, a `font-size: 14px` in a stylesheet produces a warning
annotation — the job still passes so the PR is not blocked. Resolve all
warnings, then switch to `enforcement_mode: gate` in a follow-up PR.

That is a wp-plugin repo fully adopted: one file added, pinned to an exact release tag,
advisory mode for onboarding, blocking gate once clean.

---

### Pin an exact release tag

**Our own reusable workflows** (this repository and the central review
repository, `sparxstar-pr-review`) are pinned to a published release version,
`@vX.Y.Z`. Immutable releases are enabled across the organisation, so the tag
of an immutable release cannot be moved or deleted and resolves to the same
commit forever. Your enforcement changes only when you update the pin.

**Only an immutable release qualifies:** one published after immutable
releases were enabled. Its GitHub Release is marked immutable. A bare tag
with no release, or a release published before then, does not qualify. Look
up the latest qualifying release each time; a version written into a document
goes stale:

```bash
gh api repos/Starisian-Technologies/sparxstar-code-conformance/releases \
  --jq '[.[] | select(.immutable and (.draft | not) and (.prerelease | not))][0].tag_name'
```

For the central review repository, use the same command with its name. Empty
output means no release qualifies yet: keep your current pin until one does.

- **`@main`** is never permitted. It is the integration branch, and breaking
  changes land there first.
- **`@v1` is frozen.** The floating major alias was moved by the retired
  manual release workflow. Under ADR-053 nothing moves it any more, so it stays
  at its last position and never receives a fix. A caller still on `@v1` should
  move to an exact release tag. `version-drift-enforcement` rejects a bare `v1`
  as `contract_ref`.
- **No commit SHAs for our own workflows.** The release version is the pin.

**Third-party actions** (everything not published by this organisation) stay
pinned by full commit SHA, with the version as a comment:

```yaml
- uses: owner/action@<40-character commit SHA> # vX.Y.Z
```

Only Dependabot's `github-actions` updates change these pins and their
comments (`caller-templates/dependabot.yml`). Agents never look up, resolve or
edit a third-party SHA by hand. A pin whose comment is only a major version
(`# vN`) is updated to the exact version by Dependabot's next bump.

`release-self-test.yml` checks every `uses:` line in this repository's
workflows and caller templates: our own workflows at `@vX.Y.Z` (or the
template placeholder) or by local path, third-party actions by full SHA with a
version comment.

---

### Advisory first, gate when clean

New consumers set `enforcement_mode: advisory` on every gate. Advisory runs
report violations as warnings but never block merge, so wiring a new gate
cannot stall the team.

Switch a gate to `enforcement_mode: gate` (blocking) only when **all
three** of the following are true:

1. **Zero violations on the most recent advisory run.** Do not flip with
   known unresolved violations — that red-walls every PR immediately.
2. **Conformance is confirmed.** For code-conformance gates: enforcement
   jobs are green. For spec or ADR gates: conformance tests exist and pass.
3. **The switch is a deliberate, recorded decision.** The repo is declaring
   "we now conform and intend to stay conforming."

Once a gate is set to `gate`, a violation blocks merge. That is the
point — it is the commitment that the repo stays clean. Do not switch to
`gate` as a goal in itself; switch when the repo is provably clean and
you want to keep it that way.

**The platform expectation: start advisory, earn gate.** A gate flipped to
blocking on a repo that is not yet clean is a misconfiguration, not
enforcement.

---

## Example: PHP WordPress plugin

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate
  php:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/php-enforcement.yml@vX.Y.Z
    with:
      repo_type: wp-plugin
      profile_version: v1
      enforcement_mode: gate
      phpstan-level: '5'
```

## Example: standalone React app

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate
  react:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/react-enforcement.yml@vX.Y.Z
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: gate
  css:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/css-enforcement.yml@vX.Y.Z
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: gate
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate
      profile_version: v1
```

## Example: standalone Node backend

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate
  node:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/node-enforcement.yml@vX.Y.Z
    with:
      repo_type: standalone-node
      profile_version: v1
      enforcement_mode: gate
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@vX.Y.Z
    with:
      enforcement_mode: gate
      profile_version: v1
```

## Releases (ADR-053)

Every repository releases through one reusable workflow,
`.github/workflows/semver-release.yml`, decided by ADR-053. It is separate
from `standards.yml`: it runs on pushes to the default branch, not on pull
requests.

What it does:

- Validates `release-please-config.json` against ADR-053 (tags `vX.Y.Z`, one
  root package, `bump-minor-pre-major: true`, a 0.x `initial-version`).
- Runs release-please, which keeps `CHANGELOG.md` and opens or updates the
  release pull request from Conventional Commits. Merging that pull request
  creates the `vX.Y.Z` tag and the GitHub Release.
- If `image_name` is set, builds the image from the tagged commit and pushes
  it to `ghcr.io/<owner>/<image_name>` tagged with the version (`X.Y.Z`) and
  the full commit SHA, with the matching OCI `version` and `revision` labels.
  It never pushes `latest` and refuses to rebuild a version that already
  exists. Hosts deploy the version tag and record the commit; rollback is the
  previous version tag.

Adopting it:

1. Copy `caller-templates/release.yml` to `.github/workflows/release.yml`,
   and `caller-templates/release-please-config.json` and
   `caller-templates/.release-please-manifest.json` to the repository root.
2. Replace `RELEASE_TAG` in the `uses:` line with this repository's latest
   release tag that contains `semver-release.yml`. The template shows the
   lookup command; do not copy a version from any document.
3. In `release-please-config.json`, set `release-type` to the one that matches
   the repository's package manifest (for example `node` for
   `package.json`), so release-please keeps that manifest's `version`. Keep
   `simple` if there is none.
4. If the repository already has `vX.Y.Z` tags, seed the manifest once with
   its latest tag (`git describe --tags --abbrev=0`, without the `v`).
5. Set `image_name` if the repository ships a container image; otherwise
   leave it empty. Keep `packages: write` either way: the image job declares
   it, and a caller that grants less fails at startup. The caller's
   `GITHUB_TOKEN` otherwise needs only `contents: read`.
6. **Before the first run, check the repository's rulesets** (Settings →
   Rules → Rulesets, plus any organisation ruleset that targets the
   repository). If a ruleset that applies to all branches blocks force pushes
   or requires code-scanning results, it must exclude `release-please--*`.
   release-please force-updates that branch to a commit with no scan result
   yet, so without the exclusion the first run fails with `Error updating ref
   heads/release-please--branches--main`. If the exclusion is missing, tell
   the owner in one line to exclude `release-please--*`, and wait. Do not
   work around it: changing a ruleset is a repository admin action (owner
   ruling, 2026-11-25). The release pull request's own checks still gate the
   merge to the default branch (ADR-037).
7. Remove any repository-local release or tagging workflow, and replace
   hand-written version numbers in docs with instructions to look the version
   up (latest GitHub Release, latest tag, or the top of `CHANGELOG.md`).

Versioning:

- Commits follow Conventional Commits; release-please derives the next
  version and the `CHANGELOG.md` entry from them.
- A new repository starts at 0.x and stays there until it is publicly live.
- A repository that already has `vX.Y.Z` tags continues its line (step 4).

Release pull requests opened with `GITHUB_TOKEN` do not run CI, so the merge
gate (ADR-037) cannot pass on them. The contract-sync GitHub App opens them
instead (ADR-054, resolving OQ-025). The template passes
`vars.CONTRACT_SYNC_CLIENT_ID` as `app_client_id` and
`secrets.CONTRACT_SYNC_PRIVATE_KEY` as `app_private_key`; the workflow mints a
token for the calling repository only, with Contents and Pull requests write.
Where those values come from (organisation level for a public repository,
repository-level copies for a private one) is in
[`cross-repo/cross-repo-access-and-installation.md`](cross-repo/cross-repo-access-and-installation.md).
The App must be installed on the repository. Both values are required: the
workflow fails rather than fall back to `GITHUB_TOKEN` (ADR-054 decision 3).
Release images go to `ghcr.io` (ADR-055).

This repository releases the same way: `.github/workflows/release.yml` calls
`semver-release.yml` by local path. The manual tagging workflow that moved the
floating `v1` alias is retired, and `v1` is frozen.

`release-self-test.yml` proves the config check passes the template and
fails each fixture in `fixtures/release/`, that this repository's own config
conforms, that every action in `semver-release.yml` is pinned by full commit
SHA, and that no caller template hand-writes a release of this repository.

### When to release

Ordinary pull requests merge as usual. A release is separate, and it is a
production update.

1. **The agent decides when merged work warrants a release:** a user-facing
   change, a fix that should reach production, or a database migration.
2. **The agent tells the owner, and posts the same message on the release
   pull request:**

   ```text
   Ready to release vX.Y.Z
   - <what changes for users, one line each>
   Database changes: yes | no
   This goes to production when you reply release.
   ```

   For a library or tooling repository, the last line is instead: "This
   publishes a new version; nothing in production changes." Take `vX.Y.Z`
   from the release pull request's title; never write it from memory.
3. **Only the owner's reply "release" merges the release pull request.** No
   agent merges one on its own judgement.
4. **What a release does depends on the repository:**
   - A repository that deploys on every merge to `main` switches to deploying
     on release (the `vX.Y.Z` tag or GitHub Release), not on merge.
   - A repository that deploys through the droplet process runs it right after
     the release.
   - Libraries and tooling only publish the version.

WordPad is outside this process.

## CI cost rules

Owner instruction, 2026-11-26: the Actions budget is limited. These rules apply
to this repository and to every repository that adopts the caller templates;
the templates already follow them, so a repository that copies a template gets
them by default. The `credentials` job (`credential-policy.yml`) checks the
rules marked **checked** on every pull request, in the same job as the
credential check, so the check costs no extra runner. A repository working
through its findings can pass `cost_policy: advisory` for a while.

1. **CI runs only on pull requests and pushes to the default branch.** One
   literal branch name under `push.branches` (normally `main`); no push trigger
   on other branches or on tags. Keep `workflow_dispatch` where it exists; publish from the release workflow (or the
   `release` event) instead of a tag push. *Checked (COST-001).*
2. **Concurrency on every workflow.** Group by workflow and PR number or ref;
   cancel in-progress runs for pull requests only:

   ```yaml
   concurrency:
     group: ${{ github.workflow }}-${{ github.event.pull_request.number || github.ref }}
     cancel-in-progress: ${{ github.event_name == 'pull_request' }}
   ```

   A new push to a pull request cancels the older run of that pull request;
   a run on the default branch is never cancelled. The check cannot evaluate
   expressions, so it accepts a boolean (quoted or not) or one of these two
   forms as written: `${{ github.event_name == 'pull_request' }}`, or
   `${{ github.ref != format('refs/heads/{0}', github.event.repository.default_branch) }}`.
   The release workflow and anything that deploys never cancel
   (`cancel-in-progress: false`), and nothing cancels a run on `main`.
   Reusable workflows take the caller's concurrency and declare none of their
   own (a called workflow sharing the caller's group would cancel itself).
   *Checked (COST-002).*
3. **Path filters at the job level, never on the `pull_request` trigger.**
   A docs-only change (Markdown, `docs/**`, `.github/instructions/**`,
   licences) runs no code, security or browser job. Every template starts
   with one `changes` job calling `changed-paths.yml` (no checkout, one small
   job) and gates each job on `needs.changes.outputs.code == 'true'`. A
   skipped job still reports and counts as passing, so required checks never
   leave a pull request waiting on a check that never runs. *Checked
   (COST-003).*
4. **CodeQL and other security scans: pull requests to `main` plus a weekly
   `schedule`, never on every push.** Use `caller-templates/codeql.yml`.
   GitHub's CodeQL *default setup* runs on every push to the default branch,
   so a repository using it switches to advanced setup (Settings → Code
   security) before adopting the template. *Checked for workflow-based scans
   (COST-004); default setup is a repository setting, not a file, and is not
   checked.*
5. **Cache dependencies.** Every job that runs `pnpm install`, `npm ci`,
   `npm install`, `yarn` or `composer install` restores a cache keyed on the
   lockfile (`actions/cache`, or the setup action's `cache:` input). The
   reusable workflows here cache the pnpm store and Composer downloads.
   *Checked (COST-005).*
6. **Browser (Playwright) and database tests run only when the code they
   cover changes.** Give `changed-paths.yml` a named filter for that code and
   gate the job on `fromJSON(needs.changes.outputs.matches).<name>`. *Checked
   (COST-006): a job that runs Playwright or declares `services:` must be
   gated by an `if:` on a `needs` output.*
7. **Do not push to a pull request just to re-run a check that is blocked by
   something outside the code** (budget, permissions, a missing review).
   Report what blocks it and wait. A push re-runs every check. *Not
   checkable; agent rule (this repository's `AGENTS.md`).*
8. **Dependabot: weekly, minor and patch grouped per ecosystem.** Copy
   `caller-templates/dependabot.yml` to `.github/dependabot.yml` and keep the
   ecosystems the repository uses (`github-actions`, `npm` for npm and pnpm,
   `composer`). Each ecosystem gets one pull request for its minor and patch
   updates, each major update as its own pull request, one pull request for
   security updates, `open-pull-requests-limit: 5`, and a 3-day cooldown on
   version updates (security updates are never delayed). Dependabot pull
   requests run CI like any other pull request; merge them when green.
   *Checked (DEP-001): the file exists, is weekly, groups minor and patch
   updates and all security updates, and sets the limit to 5 or less.*
   (A later owner instruction on 2026-11-26 replaced the earlier "all updates
   in one group".)

   This supersedes, for this repository and its templates, ADR-058's
   "merge before CI". The governance registry's ADR-058 and its review rules
   still state the older rule and need amending there.

`changed-paths.yml` reads the changed files from the GitHub compare API with
the caller's token. Anything it cannot classify safely (a new branch, a manual
run, 300 or more changed files, an API error) counts as changed, so it never
skips work it cannot prove unnecessary. This repository's own `ci.yml` is the
worked example: one `changes` job, then each self-test suite as a reusable
workflow gated on its own named filter.

## Credential policy (ADR-056)

Every repository runs `.github/workflows/credential-policy.yml`, as the
`credentials` job in each `standards-*.yml` template or through
`caller-templates/credential-policy.yml`. It reads the repository's workflow
files and fails on any credential other than `GITHUB_TOKEN`, the
composer-resolver and contract-sync App credentials, and the deploy secrets the
repository declares in `.github/credential-policy.yml`. Each failure names the
App to use instead. The rules and the declaration format are in
[`cross-repo/cross-repo-access-and-installation.md`](cross-repo/cross-repo-access-and-installation.md)
("Checked in CI"). It takes no secrets and has no advisory mode.

`credential-policy-self-test.yml` runs the checker against every fixture in
`fixtures/credential-policy/`, checks that a committed token is rejected
without being echoed, checks that every caller template passes, and runs the
real check on this repository.

The same job also runs the platform security and data protection checks
(DATA-001 data inventory, TLS-001 verified database TLS, NEON-001 no Neon
hosts). They are warn-only until the date in `SECURITY_ENFORCE_FROM`, then
failing; the `security_policy` input overrides that (`advisory` or `gate`).
A service repository declares `service: true` in `.github/credential-policy.yml`.
What each check flags, and why, is in the
[README](README.md#platform-security-and-data-protection-checks).

## Mode input

The `mode` input on workflows that support it maps to the enforcement
matrix:

| Mode | Behavior |
|---|---|
| `draft` | Violations produce warnings, never block merge |
| `development` | Violations block merge |
| `production` | Violations block merge (strictest checks) |

Default is `development`. Use `draft` for repos in early development
where you want visibility without blocking.

## Composer auth for PHP repos

If the PHP workflow needs private packages, pass the secrets the reusable
workflow declares **by name** in the caller's `secrets:` block. Never use
`secrets: inherit`.

Do not assume the credentials reach your repo. Public repos read the
org-level values; private repos cannot, and must carry repository-level
copies. The names, where each is set, and the auth block itself live in
[`cross-repo/cross-repo-access-and-installation.md`](cross-repo/cross-repo-access-and-installation.md)
(Block A covers Composer auth). Read them there; do not copy them here.

## Pin strategy

| Pin | When to use |
|-----|-------------|
| `@vX.Y.Z` (latest immutable release) | **Always**, for this repository's and the central review repository's workflows. Update deliberately, or merge the Dependabot bump PR. |
| `@<full SHA> # vX.Y.Z` | **Always**, for third-party actions. Changed only by Dependabot. |
| `@v1` | Never for new pins. Frozen at its last position; it receives no further releases. |
| `@main` | Never. Breaking changes land here first; not for consumers. |

## Rules

- **One `standards.yml` file per repo.** Don't create separate workflow
  files per check.
- **Always pin our own workflows to an immutable release (`@vX.Y.Z`), looked
  up, never copied from a document;** third-party actions by full SHA,
  maintained by Dependabot only. Never pin to `@main` (breaking changes land there first)
  or to the frozen `@v1`. See STD-TOOLCHAIN-001 §3 for the three-axis versioning
  model.
- **Don't duplicate checks.** If the reusable workflow checks phpcs,
  don't also run phpcs separately in another workflow. One source of
  enforcement.
- **Don't override reusable workflow behavior.** If a check needs to
  work differently for your repo, file a request in the coding-standards
  repo — don't fork the workflow locally.
