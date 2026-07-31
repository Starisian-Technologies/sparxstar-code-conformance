# Wiring a Repo into the Starisian Governance Platform

> **Exemption note:** this document is exempt from this repo's org-wide "no repo/product names" rule under OQ-007 (see `QUESTIONS.md` — the canonical statement of this carve-out) because it documents real cross-repo integration identifiers — `uses:` targets, secret/variable names, and tag pins — that are only correct and copy-pasteable with the exact values, not a generic coding/style standard.

This is a step-by-step guide for connecting **any** Starisian Technologies repo to the shared
governance platform: the ADR registry, contracts registry, product-spec registry, shared
code-conformance workflows, and the Claude PR reviewer. It's written from a live audit of
`sparxstar-helios-trust`'s integration — including the bugs found there — so the checklist below
exists specifically to stop you from repeating them.

This repo's own `REUSABLE-WORKFLOWS.md` covers adopting *this repo's* reusable workflows in much more
step-by-step detail than §4.3 below (repo-type selection, caller templates, worked examples), and
`docs/setup-and-install.md` covers the `version-drift-enforcement` gate specifically. Where a target
repo has its own detailed setup doc like those, treat this guide as the map and that doc as the
authoritative detail for that one integration.

## 1. The platform at a glance

Five repos provide governance-as-reusable-workflows. Your repo (the "consumer") calls into them
from its own `.github/workflows/*.yml`, mostly using GitHub Actions' `workflow_call` mechanism to
invoke pinned, versioned logic rather than copying it — the one exception is the propose-back flow in
§4.5, which is template-based by necessity (see the note below the table).

| Registry repo | Provides | Reusable workflow your repo calls |
|---|---|---|
| `sparxstar-architecture-governance-registry` | ADRs, invariants, enforceable architecture contracts | `.github/workflows/adr-enforce.yml` |
| `sparxstar-contracts-registry` | Canonical interface contracts (per-consumer, versioned, ratification status) | `.github/workflows/contract-conformance.yml` |
| `sparxstar-code-conformance` (this repo) | Shared lint/style/standards enforcement (pnpm, php, css, media, js, etc.) | `.github/workflows/{pnpm,php,css,media,...}-enforcement.yml` |
| `sparxstar-product-specification-registry` | Canonical per-product tech specs | `.github/workflows/fetch-specs.yml` (pull) + a propose-back template (push) |
| `sparxstar-claude-pr-review` | Claude-powered PR review against platform rules | `.github/workflows/claude-pr-review.yml` |

> **Canonical names:** the repository identifiers above are the authoritative names for this platform. `.github/copilot-instructions.md`'s "Reference repositories" section lists the ADR-registry and product-spec-registry names directly (not a deferral to this document) — both must agree; this table is the source to check first if they ever drift.

Four of these five are consumed the same way: called via `uses:` with a pinned ref, never copy-pasted
logic. The fifth — proposing spec/contract changes *back* to a registry, §4.5 — is the one exception:
that flow is a template you copy into your repo, not a `workflow_call`, because it's your repo pushing
*into* the registry rather than the registry running code inside your CI. None of the five work with
the default `${{ secrets.GITHUB_TOKEN }}` for cross-repo reads — that token is scoped to your own repo
only.

## 2. Prerequisite: GitHub App tokens, not PATs

Cross-repo checkout/push/PR operations need a token with access to the *other* repo. The platform
standard is short-lived **GitHub App installation tokens**, minted per-job with
[`actions/create-github-app-token`](https://github.com/actions/create-github-app-token), not a
long-lived personal access token. You need an identifier for the App and its private key (a repo/org
**Secret**) — ask a platform admin for these if your repo doesn't have them yet:

**Version-pinning caveat on `actions/create-github-app-token` itself:** this platform pins two
different major versions of that action for the same purpose, with different input semantics — the
required identifier depends on which one your workflow uses, so check before copying either pattern:

- This repo's `pnpm-enforcement.yml`, `php-enforcement.yml`, and `css-enforcement.yml` all pin
  `actions/create-github-app-token` by commit SHA with a `# v1` comment (the workflow files
  themselves show only that SHA, not a resolved version — looking up that commit shows it lands on
  the `1.12.0` release) and pass the identifier via that version's `app-id` input, documented there
  simply as "GitHub App ID" — that release has no separate `client-id` input at all.
- `sparxstar-claude-pr-review`'s own token-minting step pins `actions/create-github-app-token@v3` and
  passes the identifier via `client-id` — the input name that action's current major version
  documents and recommends.

Both integrations read the identifier from the **same** org variable, `COMPOSER_RESOLVER_CLIENT_ID`.
Whether one raw value stored there is valid for both an `app-id` input (v1) and a `client-id` input
(v3) is a question for a platform admin to verify against what's actually stored — don't assume it
either works or is broken without checking. Flag the version split itself to a platform admin as
worth standardizing on one `actions/create-github-app-token` major version platform-wide, so the
required identifier type isn't ambiguous per-workflow; that's out of scope for a per-repo setup guide
to fix:

| Secret pair | Used for |
|---|---|
| `COMPOSER_RESOLVER_CLIENT_ID` (var) / `COMPOSER_RESOLVER_PRIVATE_KEY` (secret) | Read-only cross-repo checkout: pulling ADR contracts, interface contracts, specs, and private Composer packages |
| `CONTRACT_SYNC_CLIENT_ID` (var) / `CONTRACT_SYNC_PRIVATE_KEY` (secret) | Write-back flows: pushing branches and opening PRs into the contracts/spec registries |
| `ANTHROPIC_API_KEY` (secret) | Required by the Claude PR reviewer |

**An App existing in the org is not the same as it being scoped to your repo.** Both Apps must have
your repo explicitly added under their installation's repository access — verify under org Settings
→ GitHub Apps → (App name) → Repository access. A missing scope produces an opaque "repository not
found" or 401 error with no indication the App's scope was the cause.

**Before you wire a write-back flow (pushing a branch + opening a PR into a registry), confirm the
App installation actually has `Pull requests: write` on that target repo.** This was the single most
common failure found in the live audit: the App could push a branch (`Contents: write`) but not call
`gh pr create` (`Resource not accessible by integration (repository.pullRequests)`), silently leaving
orphaned branches in the registry with no PR ever opened. Read-only pulls don't need this scope;
propose-back flows do.

Pass secrets to reusable workflows **by name**, not `secrets: inherit`, whenever the callee needs
cross-repo access — `inherit` only forwards what your repo can see, and being explicit makes the
dependency visible in a diff. `secrets: inherit` is fine for reusable workflows that only need your
own repo's `GITHUB_TOKEN` (e.g. this repo's own lint-only enforcement workflows).

```yaml
secrets:
  COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

A secret must be declared in the reusable workflow's own `on.workflow_call.secrets` block before a
consumer can pass it by name — passing one the callee doesn't declare fails workflow validation
outright ("secret ... is not defined in the referenced workflow" or similar), it does not silently
no-op. That failure mode is actually useful: it means a caller passing the *wrong* secret name fails
loudly and immediately, distinct from a stale pin silently missing a *newly added* required secret
(which instead fails later, inside the callee, with a missing-value error). If a registry adds a new
secret requirement after you've pinned an older tag, your pin still has the old declaration and won't
forward the new secret until you bump the pin.

## 3. Version pinning — pick one policy and apply it everywhere

Every registry publishes an immutable patch tag (`v1.0.0`, `v1.0.1`, ...). Most also publish a
moving major alias (`v1`) that repoints on every release — whether one exists is repo-specific, not
guaranteed (see the `sparxstar-claude-pr-review` note below: as of this writing it has no `v1` alias
at all). **Pin to the immutable patch tag regardless** — this is what every registry's own README
recommends, and the live audit found repos mixing patch tags and aliases inconsistently where both
existed (some gates pinned `@v1.0.0`, others `@v1`), which means CI behavior can silently change out
from under you the next time a registry cuts a release. Bumping the pin should be a deliberate,
reviewed commit, not something that happens automatically.

**Also verify the tag you pin to actually exists and isn't stale.** One of the five integrations in
the audited repo had been pinned to an old tag for months after the upstream repo had already
shipped a fix in the next patch release — the bug the old tag had was long resolved, just not where
this consumer was pointing. Check the target repo's own README/CHANGELOG for its "current
recommended" tag before you pin, and re-check it periodically.

**A note on `sparxstar-claude-pr-review` pinning:** verified live against that repo's tags —
currently `v1.0.0` and `v1.1.0` only, **no `v1` moving alias exists there**. Pin to the immutable
patch tag; there is no alias to prefer it over. (This repo's own
`caller-templates/standards-php-wordpress.yml` was found pinning that same reusable workflow to a
moving `@v1` alias during an earlier review round — bumped to `@v1.1.0`, the current immutable tag,
in that same commit; if you copied that template before this fix, verify your own pin points to a
tag that actually exists on that repo.)

This repo's own `caller-templates/*.yml` were also found pinning their own `pnpm`/`php`/`css`/`media`/
`react`/`node` jobs to the moving `@v1` alias — inconsistent with the immutable-pin recommendation
above and with this repo's own `REUSABLE-WORKFLOWS.md`. Bumped to `@v1.0.2` — this repo's current
latest patch tag as of this writing — in the same review round as the `claude-pr-review` fix, so
copying any current caller template now matches this guidance. Re-check that tag is still current
before you copy it; per the "verify the tag isn't stale" advice above, this doc can itself go stale.

## 4. Setup checklist, integration by integration

### 4.1 ADR conformance gate

```yaml
# .github/workflows/adr-conformance-gate.yml
name: ADR Conformance Gate
on:
  pull_request:
  push:
    branches: [main]
jobs:
  adr-conformance:
    uses: Starisian-Technologies/sparxstar-architecture-governance-registry/.github/workflows/adr-enforce.yml@v1.0.1
    with:
      contract_version: v1
      contract_ref: v1.0.1
      enforcement_mode: advisory   # flip to `gate` once your repo's ADR citations are clean
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

Always set `contract_ref` explicitly — the reusable workflow falls back to parsing
`GITHUB_WORKFLOW_REF` when it's omitted, which is a caller-run-scoped value, not a reliable source of
truth for which registry version to check out. An explicit input avoids that ambiguity entirely.

If you also want to react to upstream ADR changes, add a passive listener:

```yaml
on:
  repository_dispatch:
    types: [adr-registry-changed]
```
— but note this only fires if the registry's own dispatch workflow lists your repo in its consumer
config (`config/governance-consumers.txt` or equivalent). Ask the registry maintainer to add you, and
ask them to trigger one real event so you can confirm the wiring — a `workflow_dispatch` test only
proves your listener runs, not that the sender-side config includes you.

### 4.2 Contract conformance gate

```yaml
# .github/workflows/contract-conformance-gate.yml
name: Contract Conformance Gate
on:
  pull_request:
jobs:
  contract-conformance:
    uses: Starisian-Technologies/sparxstar-contracts-registry/.github/workflows/contract-conformance.yml@v1.0.2
    with:
      contract-ref: v1.0.2
      enforcement_mode: advisory
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

**Before this will ever pass**, your repo must be registered as a `consumer` on at least one
canonical/ratified contract in the registry's `MANIFEST.json` — using your **current, exact**
`owner/repo` string. If your repo was ever renamed, ask the registry maintainer to update the
`consumers` array; a stale name causes the job to fail with `no contracts matched` even though
auth, checkout, and the workflow ref are all fine. This is a registry-side, not caller-side, fix.

### 4.3 Shared code-conformance standards (this repo)

```yaml
# .github/workflows/standards.yml
name: Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@v1.0.2
    with: { enforcement_mode: advisory }
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  php:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/php-enforcement.yml@v1.0.2
    with:
      repo_type: wp-plugin   # wp-plugin | wp-module — php-enforcement.yml's only two valid values
      enforcement_mode: advisory
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  css:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/css-enforcement.yml@v1.0.2
    with:
      repo_type: wp-plugin
      enforcement_mode: advisory
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@v1.0.2
    with:
      repo_type: wp-plugin
      enforcement_mode: advisory
    secrets: inherit   # media-enforcement.yml declares no secrets — nothing to pass by name here
```

`pnpm-enforcement.yml`, `php-enforcement.yml`, and `css-enforcement.yml` declare
`COMPOSER_RESOLVER_PRIVATE_KEY` as a required secret (used to mint a read token for
private-dependency checkout) — pass it explicitly per §2, don't rely on `inherit`.
`react-enforcement.yml` and `node-enforcement.yml` run `pnpm install` as well but currently
declare no secrets and perform no git-auth setup. Repos with private GitHub-hosted JS
dependencies will hit a plain git-auth failure in those two jobs; that gap is tracked as a
known platform issue and requires a change to the reusable workflows themselves (adding the
secret declaration and token-minting step) followed by a new published release tag. Do not
pass `COMPOSER_RESOLVER_PRIVATE_KEY` to the react/node jobs while they are pinned to a tag
that does not declare the secret — workflow validation will reject it outright. Check this
repo's current tags for a release that ships that fix before copying the react/node caller
templates. `media-enforcement.yml` is the one job here that genuinely declares no secrets
at all, so `secrets: inherit` there isn't a violation of the by-name rule — there's nothing cross-repo
to make visible.

`repo_type` on the php/css/media/react/node jobs is technically optional (`required: false` with a
default), but always set it explicitly — the default may not match your repo type, and an unnoticed
mismatch produces confusing, hard-to-trace violations rather than a clear error. See
`REUSABLE-WORKFLOWS.md` in this repo for the full set of valid `repo_type` values and caller templates
per repo type.

Only include the jobs relevant to your stack. Once this is wired, **do not** also hand-roll inline
lint/format steps in a separate workflow for the same tooling — that creates two divergent
enforcement paths for the same rules, one versioned and one not. Retire the inline version.

### 4.4 Product spec pull

```yaml
# part of governance.yml
jobs:
  pull-spec:
    uses: Starisian-Technologies/sparxstar-product-specification-registry/.github/workflows/fetch-specs.yml@v1.0.1
    with:
      specs: '<your-repo-short-name>'
      agent-ref: main
      contract-ref: v1.0.1
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

Specs land at runtime in `.sparxstar/specs/` and are never committed into your repo.

### 4.5 Proposing spec/contract changes back

This is a plain git push + `gh pr create` against the registry, not a `workflow_call` — copy the
template rather than hand-writing it. **The two registries use different filenames and paths, not
one generic template** — verified directly against both repos:

- Proposing a **spec** change: `sparxstar-product-specification-registry`'s
  `templates/calling-repo/.github/workflows/propose-spec.yml`.
- Proposing a **contract** change: `sparxstar-contracts-registry`'s
  `templates/calling-repo/propose-contract.yml` — note this one is *not* under `.github/workflows/`
  in the registry's own templates tree; place it under `.github/workflows/` in your own repo when
  you copy it.

Confirm the `CONTRACT_SYNC_*` App has **Pull requests: write** on the target registry before relying
on either — see §2. Test it once manually via `workflow_dispatch` and check that a PR actually
opens, not just that the branch pushes.

### 4.6 Claude PR review

```yaml
# .github/workflows/claude-pr-review.yml
name: Claude PR Review
on:
  pull_request:
    types: [opened, synchronize, reopened]
jobs:
  review:
    uses: Starisian-Technologies/sparxstar-claude-pr-review/.github/workflows/claude-pr-review.yml@v1.1.0  # pin to current tag from repo README
    with:
      contract_ref: v1.0.1   # ADR/product-spec registry tag — a SEPARATE axis, see note below
    secrets:
      ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

Pin the `uses:` line to whatever tag that repo's own README currently calls "the platform default" at
the time you set this up — don't assume an earlier tag is safe just because it's the one you see in
an example (a live audit found a consumer stuck on an older patch tag for months after a later release
shipped a fix for a bug that broke every single run: the privileged job resolved its own
reference-docs checkout from the caller's PR ref instead of its own workflow ref). **Don't confuse that pin with `contract_ref`** — `contract_ref`
selects which tag of the *ADR and product-spec registries* to review against, a completely
independent version axis from this reusable workflow's own release tag; bumping one does not require
bumping the other. Reviews are advisory: a FAIL verdict comments on the PR but does not block merge
by design.

## 5. Verifying you set it up correctly

After adding any of the above, don't just check that the job goes green once — the failures found in
production all *looked* like clean plumbing (App token minted, checkout succeeded) right up until a
later step:

1. Open a real PR and confirm the job actually runs (check the Actions tab, not just that the YAML
   parses).
2. If the job fails, read the failed step's logs, not just the red X — every failure found in
   practice was a specific, readable error (`no contracts matched`, `couldn't find remote ref`,
   `Resource not accessible by integration`), not a generic timeout.
3. For any propose-back / write flow, confirm a PR actually appeared in the target repo — a
   successful-looking `git push` step is not proof the PR was opened.
4. For any `repository_dispatch` listener you add, ask the registry maintainer to trigger a real
   event once (not just `workflow_dispatch`) to prove the sender-side wiring lists your repo.
5. Re-check your version pins quarterly against each registry's README "current recommended tag" —
   pins go stale silently, and a repo can be broken for months before anyone notices if nothing else
   changes to draw attention to that workflow file.

## 6. Getting access

If you don't have the secrets in §2, or your repo isn't yet listed as a consumer in a registry's
`MANIFEST.json` / dispatch config, that's an org-admin action, not something you can fix from your
own repo. Ask a platform maintainer to:
- Install/confirm the relevant GitHub App(s) on your repo with the permissions in §2.
- Add your repo's secrets (`COMPOSER_RESOLVER_*`, `CONTRACT_SYNC_*`, `ANTHROPIC_API_KEY` as needed).
- Register your repo as a consumer in the relevant registry's manifest, using your exact current
  `owner/repo` name (re-confirm this after any repo rename).
