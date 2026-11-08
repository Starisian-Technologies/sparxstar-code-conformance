# SPARXSTAR — Cross-Repo Access & Private Package Installation

**This is the only document for this topic.** It supersedes and replaces:

- CROSS-REPO-ACCESS.md (Drive)
- The "Org-Level Infrastructure" and "Composer auth" sections of governance-repo-setup-instructions.md (Drive, all three copies)
- The "Composer auth for PHP repos" section of adopting-reusable-workflows.md (Drive)

Repo-level agent instructions must LINK here, never restate the blocks below. A restated copy is where stale patterns survive.

---

## The six governance and standards repos

All six names verified against the live org 2026-09-10.

| Repo | Access observed | Purpose |
| --- | --- | --- |
| sparxstar-architecture-governance-registry | auth required (internal) | ADRs, invariants, open questions, contracts, conformance enforcement, governance-sync |
| sparxstar-product-specification-registry | auth required (internal) | Product tech specs — one per product, fetched on demand |
| sparxstar-code-conformance | public | Reusable enforcement workflows (tag-pinned: v1 / semver) |
| sparxstar-contracts-registry | public | Shared PHP interfaces — auto-synced from source repos |
| sparxstar-claude-pr-review | public | AI PR reviewer — reviews against ADRs and specs |
| starisian-technologies-coding-standards | public | Coding standards catalog, enforcement matrix, installable configs |

Retired names — never use, correct on sight: `sparxstar-architecture-decision-record` (→ architecture-governance-registry), `sparxstar-platform-standards` (→ product-specification-registry).

---

## The two GitHub Apps

| App | Direction | Use it to… |
| --- | --- | --- |
| sparxstar-composer-resolver | READ | check out / read another private repo; install private Composer packages |
| sparxstar-contract-sync | WRITE | push commits/files into another repo |

There is no third app. Never create a PAT to solve cross-repo access.

### Drift records (owner ruling, 2026-11-04)

`version-drift-enforcement` writes one JSON record per consuming repo to **`sparxstar-architecture-governance-registry`** — proposed path `drift/<owner>-<repo>.json` on `main`. Drift records are **internal**: they name private repos, so they are never committed to `sparxstar-code-conformance`, which is public. The workflow mints its contract-sync token scoped to the registry only.

> **From `v1.0.4`.** This destination ships in `version-drift-enforcement` `v1.0.4` (Starisian-Technologies/sparxstar-code-conformance#41). Tags up to and including `v1.0.3` still mint for, and push to, `sparxstar-code-conformance`. Their drift-record write fails, and it would target the wrong repo even if it didn't. **Do not turn the drift record on while pinned to `v1.0.3` or earlier.**

**Turning the drift record on in a caller.** From `v1.0.4`, the maintained caller templates (`caller-templates/standards-*.yml`) pin `v1.0.4`, set `write_drift_record: true`, and pass `CONTRACT_SYNC_PRIVATE_KEY` by name (owner ruling, 2026-11-04). A caller copied from an earlier template still sets `write_drift_record: false` and passes only `COMPOSER_RESOLVER_PRIVATE_KEY`, so setting the credentials alone records nothing there. To turn it on in such a caller:

1. Set the contract-sync values in the repo (table below, including the Dependabot copy where Dependabot runs).
2. Pin `version-drift-enforcement` and `contract_ref` to a release that writes to the registry (the release above or later).
3. Remove the `write_drift_record: false` override, and pass `CONTRACT_SYNC_PRIVATE_KEY: ${{ secrets.CONTRACT_SYNC_PRIVATE_KEY }}` by name in the job's `secrets:` block.

### What contract-sync needs repository access to

Every repo a workflow writes to through contract-sync must be in the App's repository access (org Settings → GitHub Apps → contract-sync → Repository access). As read from the live workflows on 2026-11-04:

| Writer (repo → workflow) | Writes to |
| --- | --- |
| `sparxstar-code-conformance` → `version-drift-enforcement.yml` | `sparxstar-architecture-governance-registry` (drift records), from `v1.0.4`. Earlier tags target `sparxstar-code-conformance`. |
| `sparxstar-architecture-governance-registry` → `governance-sync.yml` | every repo in its `config/governance-consumers.txt` |
| `sparxstar-product-specification-registry` → `propose-spec.yml` (called from product repos), `fetch-specs.yml` (drift report) | `sparxstar-product-specification-registry` |
| `sparxstar-contracts-registry` → `propose-contract.yml`, `fetch-contracts.yml` (drift report) | `sparxstar-contracts-registry` |
| `sparxstar-contracts-registry` → `dispatch-contract-change.yml` | the consumer repos it resolves for the changed contract |
| product repos → `sync-contracts.yml` | `sparxstar-contracts-registry` |
| product repos → their own `governance.yml` (inline spec proposal, e.g. `sparxstar-sky-ui`, `sparxstar-3iatlas-wordpad`, `sparxstar-contracts-registry`) | `sparxstar-product-specification-registry` |

Before changing the App's access, re-read each workflow's `repositories:` line rather than trusting this table; a workflow added later is not listed until someone adds it here.

## Secrets and variables: names, and where each repo reads them

| Name | Type | App |
| --- | --- | --- |
| `COMPOSER_RESOLVER_CLIENT_ID` | Variable | composer-resolver |
| `COMPOSER_RESOLVER_PRIVATE_KEY` | Secret | composer-resolver |
| `CONTRACT_SYNC_CLIENT_ID` | Variable | contract-sync |
| `CONTRACT_SYNC_PRIVATE_KEY` | Secret | contract-sync |
| `ANTHROPIC_API_KEY` | Secret | Claude PR review |

These names exist at org level. **Whether a repo can read the org-level copies depends on its visibility** (owner ruling, 2026-11-04):

- **Public repos** read the org-level values. Reference them; never recreate them.
- **Private repos cannot.** This org's GitHub plan does not let private repositories read org-level secrets or variables, and product repos stay private — they are not made public to work around this. Each private repo carries its own **repository-level** copies of the composer-resolver and contract-sync values:

| Name | Type | Where it is set | Needed when |
| --- | --- | --- | --- |
| `COMPOSER_RESOLVER_CLIENT_ID` | Variable | Actions, repository level | always |
| `COMPOSER_RESOLVER_PRIVATE_KEY` | Secret | Actions, repository level | always |
| `ANTHROPIC_API_KEY` | Secret | Actions, repository level | the Claude PR reviewer runs in the repo |
| `COMPOSER_RESOLVER_PRIVATE_KEY` *(second copy, Dependabot store)* | Secret | **Dependabot**, repository level | Dependabot runs in the repo |
| `CONTRACT_SYNC_CLIENT_ID` | Variable | Actions, repository level | always |
| `CONTRACT_SYNC_PRIVATE_KEY` | Secret | Actions, repository level | always |
| `CONTRACT_SYNC_PRIVATE_KEY` *(second copy, Dependabot store)* | Secret | **Dependabot**, repository level | Dependabot runs in the repo **and** the repo writes a drift record |

**Contract-sync (owner ruling, 2026-11-04):** private repos carry `CONTRACT_SYNC_CLIENT_ID` and `CONTRACT_SYNC_PRIVATE_KEY` alongside the composer-resolver values. `version-drift-enforcement`'s `write_drift_record` defaults to true, and the caller templates from `v1.0.4` leave it on. Callers copied from earlier templates override it to false; see the caller steps under "Drift records" above.

**Dependabot (owner rulings, 2026-11-04).** Dependabot-triggered runs do not receive Actions secrets; they read Dependabot secrets. So:

- Where Dependabot runs, the repo carries a Dependabot copy of `COMPOSER_RESOLVER_PRIVATE_KEY`.
- Where Dependabot runs **and** the repo writes a drift record, it also carries a Dependabot copy of `CONTRACT_SYNC_PRIVATE_KEY`.

Without these copies, every credential-gated job skips or fails on Dependabot PRs.

"Never recreate" applies to the org-level values only. A private repo's repository-level copies are required, not a violation.

### Adding a private repo

1. Set every row above at repository level:
   - **Actions → Variables tab:** `COMPOSER_RESOLVER_CLIENT_ID`, `CONTRACT_SYNC_CLIENT_ID`.
   - **Actions → Secrets tab:** `COMPOSER_RESOLVER_PRIVATE_KEY`, `CONTRACT_SYNC_PRIVATE_KEY`, and `ANTHROPIC_API_KEY` where the reviewer runs.
   - **Dependabot → Secrets:** `COMPOSER_RESOLVER_PRIVATE_KEY` again, where Dependabot runs; and `CONTRACT_SYNC_PRIVATE_KEY` again, where Dependabot runs and the repo writes a drift record. The Actions and Dependabot stores are separate, so setting a key once leaves Dependabot PRs without it.
2. Scope the composer-resolver App to every repo the new repo's workflows read (see Troubleshooting), and the contract-sync App to every repo they write to. For the drift record, that is `sparxstar-architecture-governance-registry`, once the repo is pinned to a release that writes there (see "Drift records" above).
3. Add the repo to the list below.

#### Script for step 1: `cross-repo/set-repo-credentials.sh`

The owner runs this from their own machine, logged in to the GitHub CLI (`gh auth login`). It sets step 1's rows on one or more private repos at repository level, then lists each store's names and reports any expected name that is missing. It does not do steps 2 or 3; it prints a reminder for them. The names it sets come from the table above. If the script and this doc disagree, this doc wins: fix the script. The script refuses to run if a name it uses is missing from this file.

```bash
cross-repo/set-repo-credentials.sh [--dry-run] [--anthropic-key] \
  [--no-dependabot] [--no-contract-sync-dependabot] \
  --composer-resolver-client-id <CLIENT_ID> \
  --composer-resolver-key <path/to/composer-resolver.pem> \
  --contract-sync-client-id <CLIENT_ID> \
  --contract-sync-key <path/to/contract-sync.pem> \
  <repo> [<repo> ...]
```

- **Repos:** a bare name defaults to owner `Starisian-Technologies`; `OWNER/NAME` also works. Public repos are skipped with a warning, because they read the org-level values.
- **Client IDs:** the Client ID strings (`Iv23…`), never the numeric App ID. A numeric value is rejected.
- **Private keys:** read from the `.pem` files you hold and passed to `gh` on standard input. They never go on a command line, to the terminal, a log or a file, and are never read back.
- **Default:** both Client ID variables, both private keys as Actions secrets, and both private keys as Dependabot secrets.
- **`--anthropic-key`:** also sets `ANTHROPIC_API_KEY` as an Actions secret, for repos where the Claude PR reviewer runs. The script asks once, with input hidden, and uses the value for every repo named.
- **`--no-dependabot`:** skips both Dependabot copies, for repos where Dependabot doesn't run. **`--no-contract-sync-dependabot`:** skips only the contract-sync Dependabot copy, for repos that write no drift record. The script notes when a repo has `.github/dependabot.yml`, or a workflow that sets `write_drift_record: false`, so you can tell which applies.
- **`--dry-run`:** checks the inputs, the `gh` login and each repo's visibility, shows what would be set, and lists which expected names are already there. It changes nothing and does not prompt for the Anthropic key.
- **Re-checking a repo wired by hand:** run with `--dry-run`. Every `MISSING` line is a row still to set.
- **Exit status:** 0 when every expected name is present (or on a dry run); non-zero if `gh` isn't logged in, an input is invalid, a repo is skipped, a set fails, or a name is still missing afterwards.

### Private repos carrying repository-level credentials

Maintained by the owner. An agent cannot read Actions secrets or variables, so it must not add a repo here without the owner's word. An entry means the repo holds **every** applicable row of the table above. To verify an entry: the repo's Settings → Secrets and variables → Actions (and → Dependabot), *Repository* section.

| Repo | Recorded |
| --- | --- |
| `sparxstar-3iatlas-consent-node` | 2026-11-04 (owner) — recorded before the contract-sync ruling; owner to confirm `CONTRACT_SYNC_*` are set |

**Phantom names — these do NOT exist. If you see one in any workflow or instruction file, it was fabricated. Delete it:** `ADR_REGISTRY_READ_TOKEN`, `REGISTRY_READ_TOKEN`, `STANDARDS_READ_TOKEN`

---

## The canonical mint block

Pattern verified 2026-09-10 against live workflows in the registry, contracts-registry, and claude-pr-review — all use `@v3` / `client-id:`. The enforcement workflows in code-conformance now use this pattern too: a SHA-pinned v3 release with `client-id:` (owner ruling 2026-11-04, resolving OD-2). SHA-pinning is still preferred over the bare `@v3` shown below; the pinned SHA lives in the workflow files. The Drive docs' unpinned `@v1` / `app-id:` block matches neither live pattern and is retired.

```yaml
- name: Mint read token
  uses: actions/create-github-app-token@v3
  id: read-token
  with:
    client-id: ${{ vars.COMPOSER_RESOLVER_CLIENT_ID }}
    private-key: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
    owner: Starisian-Technologies
    repositories: <REPO-TO-READ>
```

The input pairs with the **Client ID variable** (the Iv23… string), never the numeric App ID, and never an input named `app-id` unless the green workflow says so. When the action's major version changes, this block is updated HERE and nowhere else.

---

## Decision tree

Does your workflow touch a repo OTHER than the one it runs in?

- **No** → the default `GITHUB_TOKEN` is enough. Stop.
- **Yes, reading only** → Block A.
- **Yes, writing/pushing** → Block B.
- **You are the Claude PR reviewer caller** → Block C. The reviewer reads the two internal registries itself; your caller only passes it the credentials.
- **A Claude Code agent session needs to read a repo it is not cloned into** → Block D.

**Scope every token to the minimum the run needs** — only the repositories it touches (`repositories:`) and only the access it uses. A read-only workflow mints a read token (composer-resolver); it never mints or receives a write token (contract-sync). *(Moved here from the product-spec registry's cross-repo standard, AC-002.)*

### Block A — Read another private repo / install private packages

Mint block above, then either:

**Checkout:**

```yaml
- name: Check out that repo
  uses: actions/checkout@v5
  with:
    repository: Starisian-Technologies/<REPO-TO-READ>
    token: ${{ steps.read-token.outputs.token }}
    path: <REPO-TO-READ>
```

**Composer auth (private packages — Ouroboros, pre-sync Helios contracts):**

```yaml
- name: Configure Composer auth
  run: composer config --global github-oauth.github.com ${{ steps.read-token.outputs.token }}
```

Run this before `composer install`.

**Ouroboros specifically:** `sparxstar-ouroboros-integrity` is a published, complete package. Never treat it as unpublished or pending. Any `packages/` stub directory in a consumer repo is obsolete scaffolding — delete it and install the real package through this auth path.

### Block B — Write to another repo

Same shape as Block A but mint with `CONTRACT_SYNC_CLIENT_ID` / `CONTRACT_SYNC_PRIVATE_KEY`, then:

```yaml
- name: Commit and push
  working-directory: target
  run: |
    git config user.name "sparxstar-contract-sync[bot]"
    git config user.email "contract-sync@starisian.com"
    git add -A
    git diff --cached --quiet || git commit -m "chore: sync from ${{ github.repository }}@${{ github.sha }}"
    git push
```

### Block C — Claude PR reviewer caller

The reviewer **does** read other repos. Its `build-context` job mints a composer-resolver read token for the private ADR and product-spec registries, using `vars.COMPOSER_RESOLVER_CLIENT_ID` and the `COMPOSER_RESOLVER_PRIVATE_KEY` you pass it. So the caller passes **two** secrets by name, and the caller repo must be able to read `COMPOSER_RESOLVER_CLIENT_ID` as a variable: org-level for a public repo, repository-level for a private one (see above). The App must also be scoped to both registries (Troubleshooting). Delete any phantom token lines.

```yaml
jobs:
  review:
    permissions:
      contents: read
      pull-requests: write
    uses: Starisian-Technologies/sparxstar-claude-pr-review/.github/workflows/claude-pr-review.yml@<RELEASE-TAG>
    secrets:
      ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

`<RELEASE-TAG>` is an immutable release tag of `sparxstar-claude-pr-review` — never `@main`, and no `@v1` alias is published. Find the current one by reading, not recall:

```bash
git ls-remote --tags https://github.com/Starisian-Technologies/sparxstar-claude-pr-review
```

Before pinning, open `.github/workflows/claude-pr-review.yml` **at that tag** and confirm its `on.workflow_call.secrets` declares exactly the two names above (sequencing rule 3). This block was checked against `v1.1.1` on 2026-11-04, where both are `required: true`. The reviewer refuses to run on a public repo; see its own `docs/consumer-setup.md`.

### Block D — Read token for a Claude Code agent session

Use this when an agent session in a workflow must read a repository it is not cloned into. Mint with the canonical mint block above (composer-resolver, read), with `repositories:` set to the repo the agent reads. Then pass `${{ steps.read-token.outputs.token }}` to the agent session through an environment variable (for example `SPECS_TOKEN`). The agent reads; it does not write, so it never gets a contract-sync token. *(Moved here from the product-spec registry's cross-repo standard, REQ-004.)*

This is not Block C. Block C is a caller of the published reviewer workflow, which mints its own tokens; Block D is for a workflow that runs an agent session itself.

---

## Reusable-workflow sequencing rules (violating these looks like an auth failure)

1. **Registry declares first.** A caller passing a secret the pinned reusable workflow doesn't declare in `on.workflow_call.secrets` fails at startup. Order is always: callee declares → callee cuts a new patch tag → caller bumps its pin and passes.
2. **New edit, new tag.** After any edit to a reusable workflow, **cut a new immutable patch tag** on the commit containing the edit. Never move an existing semver tag (D1). Patch tags never move; the release workflow advances the `vN` alias. Verify the new tag exists with `git ls-remote origin 'refs/tags/v1*'` — never from memory.
3. **Names must match by reading, not recall.** When a secret-passing failure occurs, open the caller and the callee's `on.workflow_call.secrets` block in the same turn and compare the exact names. A mismatch there IS the bug.

## Where this document lives

- **Canonical home: this file, `sparxstar-code-conformance` → `cross-repo/cross-repo-access-and-installation.md`** (owner ruling, 2026-11-04). The repo is public so agents can read it; link here rather than copying it.
- **Not canonical:** `sparxstar-product-specification-registry` → `specs/_platform/SPARXSTAR-CROSS-REPO-ACCESS-STANDARD.md` is being reduced to a pointer to this file. Where it disagrees with this file, this file wins. No copy lives in `sparxstar-architecture-governance-registry`.

## Governance snapshot coverage

- **Coverage is an explicit allowlist**: `config/governance-consumers.txt` in `sparxstar-architecture-governance-registry`. A repo not in that file receives nothing. This is deliberate (an unscoped token would cover the whole installation). "My repo has no governance folder" is fixed by adding the repo to that file — the change itself triggers a sync.

## Troubleshooting: opaque `repository not found` at checkout

An App being installed org-wide is NOT the same as being scoped to the repo being read. If a mint succeeds but checkout of an internal registry fails with `repository not found`, check: org Settings → GitHub Apps → composer-resolver → Repository access — the App must be scoped to include every repo the workflow reads (in particular both internal registries for the PR-review gate). This is the documented most-common misconfiguration.

## Never

- Never `secrets: inherit`. Pass only named secrets.
- Never a PAT for cross-repo access.
- Never an API key in Variables — keys are Secrets.
- Never define a token a workflow doesn't use.
- Never pass a write token to a read-only workflow. Scope every token to the minimum needed.
- Never restate the mint block in a repo's own instructions — link here.
