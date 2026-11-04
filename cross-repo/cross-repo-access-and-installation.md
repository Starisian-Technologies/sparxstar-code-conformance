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
- **Private repos cannot.** This org's GitHub plan does not let private repositories read org-level secrets or variables, and product repos stay private — they are not made public to work around this. Each private repo carries its own **repository-level** copies of the composer-resolver values it uses:

| Name | Type | Where it is set | Needed when |
| --- | --- | --- | --- |
| `COMPOSER_RESOLVER_CLIENT_ID` | Variable | Actions, repository level | always |
| `COMPOSER_RESOLVER_PRIVATE_KEY` | Secret | Actions, repository level | always |
| `ANTHROPIC_API_KEY` | Secret | Actions, repository level | the Claude PR reviewer runs in the repo |
| `COMPOSER_RESOLVER_PRIVATE_KEY` | Secret | **Dependabot**, repository level | Dependabot runs in the repo |

Dependabot-triggered runs do not receive Actions secrets; they read Dependabot secrets. Without the Dependabot copy, every credential-gated job skips on Dependabot PRs.

"Never recreate" applies to the org-level values only. A private repo's repository-level copies are required, not a violation.

### Adding a private repo

1. Set the values above at repository level: Settings → Secrets and variables → **Actions** (Secrets and Variables tabs, *Repository* section), and → **Dependabot** for the Dependabot copy.
2. Scope the composer-resolver App to every repo the new repo's workflows read (see Troubleshooting).
3. Add the repo to the list below.

### Private repos carrying repository-level credentials

Maintained by the owner. An agent cannot read Actions secrets or variables, so it must not add a repo here without the owner's word. To verify an entry: the repo's Settings → Secrets and variables → Actions (and → Dependabot), *Repository* section.

| Repo | Recorded |
| --- | --- |
| `sparxstar-3iatlas-consent-node` | 2026-11-04 (owner) |

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
- **You are the Claude PR reviewer caller** → Block C. The reviewer reads the two internal registries itself; your caller only passes it the credentials.

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

---

## Reusable-workflow sequencing rules (violating these looks like an auth failure)

1. **Registry declares first.** A caller passing a secret the pinned reusable workflow doesn't declare in `on.workflow_call.secrets` fails at startup. Order is always: callee declares → callee cuts a new patch tag → caller bumps its pin and passes.
2. **New edit, new tag.** After any edit to a reusable workflow, **cut a new immutable patch tag** on the commit containing the edit. Never move an existing semver tag (D1). Verify the new tag exists with `git ls-remote origin 'refs/tags/v1*'` — never from memory.
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
- Never restate the mint block in a repo's own instructions — link here.
