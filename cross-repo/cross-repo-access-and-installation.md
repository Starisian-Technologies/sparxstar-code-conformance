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

## Org secrets and variables (already exist — reference, never recreate)

| Name | Type | App |
| --- | --- | --- |
| `COMPOSER_RESOLVER_CLIENT_ID` | Variable | composer-resolver |
| `COMPOSER_RESOLVER_PRIVATE_KEY` | Secret | composer-resolver |
| `CONTRACT_SYNC_CLIENT_ID` | Variable | contract-sync |
| `CONTRACT_SYNC_PRIVATE_KEY` | Secret | contract-sync |
| `ANTHROPIC_API_KEY` | Secret | Claude PR review |

**Phantom names — these do NOT exist. If you see one in any workflow or instruction file, it was fabricated. Delete it:** `ADR_REGISTRY_READ_TOKEN`, `REGISTRY_READ_TOKEN`, `STANDARDS_READ_TOKEN`

---

## The canonical mint block

Pattern verified 2026-09-10 against live workflows in the registry, contracts-registry, and claude-pr-review — all use `@v3` / `client-id:`. The enforcement workflows in code-conformance/coding-standards currently use a SHA-pinned v1 with `app-id:`, deliberately (supply-chain pinning), pending an owner ruling on platform-wide standardization (see Open Decisions in the correction list). Until that ruling, treat `@v3` + `client-id:` as the default for anything NEW, and never "fix" an existing green workflow's mint step to match this document — the green run wins. The Drive docs' unpinned `@v1` / `app-id:` block matches neither live pattern and is retired.

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
- **You are the Claude PR reviewer caller** → Block C. It reads no other repo.

### Block A — Read another private repo / install private packages

Mint block above, then either:

**Checkout:**

```yaml
- name: Check out that repo
  uses: actions/checkout@v4
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

Pass the secrets the tag you pin declares, and delete any phantom token lines.
Read the callee before copying either block below — rule 3 applies here too.

At `@v1.1.1` the reviewer mints its own read token for the private ADR and
product-spec registries, so it declares **two** required secrets and a caller
passing only the API key fails at startup:

```yaml
jobs:
  review:
    permissions:
      contents: read
      pull-requests: write
    uses: Starisian-Technologies/sparxstar-claude-pr-review/.github/workflows/claude-pr-review.yml@v1.1.1
    secrets:
      ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
```

Its client id still comes from the organization variable
`COMPOSER_RESOLVER_CLIENT_ID`; only the private key is passed.

---

## Dependabot-triggered runs read a different secret store

A workflow run triggered by Dependabot does **not** receive the repository's
ordinary Actions secrets. Organization and repository *variables* do reach it;
*secrets* must additionally exist in the separate Dependabot store
(**Settings → Secrets and variables → Dependabot**) under the same name.

This is invisible until you read a log, because the mint step fails on the
secret while the variable beside it resolves normally:

```
client-id: Iv23…                                      <- the VARIABLE arrived
Error: The 'private-key' input must be set to a non-empty string.
```

That is a real sirus run from 1 Aug 2026. An ordinary (non-Dependabot) run on
the same repository minted its token without complaint.

So for any repo whose CI mints a token and also receives Dependabot PRs,
`COMPOSER_RESOLVER_PRIVATE_KEY` must exist **twice**: once as an org Actions
secret, and once as an org Dependabot secret with the identical name. Without
the second one, every Dependabot PR in that repo is permanently red at the mint
step and no dependency update can ever be merged on a green run.

Do not diagnose this as a missing or broken key. The key is fine; the run is
reading a different store.

## Reusable-workflow sequencing rules (violating these looks like an auth failure)

1. **Registry declares first.** A caller passing a secret the pinned reusable workflow doesn't declare in `on.workflow_call.secrets` fails at startup. Order is always: callee declares + re-tags → then caller passes.
2. **Tags follow edits.** After ANY edit to a reusable workflow, both the immutable semver tag and the moving major alias (`v1`) must be moved onto the commit containing the edit. Verify with `git ls-remote origin 'refs/tags/v1*'` — never from memory.
3. **Names must match by reading, not recall.** When a secret-passing failure occurs, open the caller and the callee's `on.workflow_call.secrets` block in the same turn and compare the exact names. A mismatch there IS the bug.

## Where this document lives and how it reaches repos

- Canonical home: `sparxstar-code-conformance`, at `cross-repo/cross-repo-access-and-installation.md`. (It was previously `sparxstar-architecture-governance-registry`; that is no longer where it is edited.)
- Distribution: the governance-sync workflow compiles snapshot files and pushes them into each consumer's `.github/instructions/governance/`. Because the source moved, that workflow's "Compile snapshot" step and its push trigger paths must point at this repository and this path — verify both by reading the live workflow before relying on distribution.
- **Coverage is an explicit allowlist**: `config/governance-consumers.txt` in the registry. A repo not in that file receives nothing. This is deliberate (an unscoped token would cover the whole installation). "My repo has no governance folder" is fixed by adding the repo to that file — the change itself triggers a sync.

## Troubleshooting: opaque `repository not found` at checkout

An App being installed org-wide is NOT the same as being scoped to the repo being read. If a mint succeeds but checkout of an internal registry fails with `repository not found`, check: org Settings → GitHub Apps → composer-resolver → Repository access — the App must be scoped to include every repo the workflow reads (in particular both internal registries for the PR-review gate). This is the documented most-common misconfiguration.

## Never

- Never `secrets: inherit`. Pass only named secrets.
- Never a PAT for cross-repo access.
- Never an API key in Variables — keys are Secrets.
- Never define a token a workflow doesn't use.
- Never restate the mint block in a repo's own instructions — link here.
