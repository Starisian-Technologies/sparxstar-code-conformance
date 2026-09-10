# SETUP — consuming the Code Conformance workflows

**What this repo is.** The platform's reusable enforcement workflows — pnpm,
PHP, CSS, React, Node, media, formatting, version drift and the full quality
gate. Your repo *calls* them; it never copies them. A standards fix lands once
here and reaches every repo that moves its pin.

**Access.** Public — no auth needed to call the workflows. The workflows
themselves mint tokens to reach private dependencies, so consumers still pass
`COMPOSER_RESOLVER_PRIVATE_KEY`.

> **This file is a router, not a manual.** The detail already exists in this
> repo and is maintained beside the code. Do not read a summary of it here —
> that is how copies drift.

---

## Where to go

| You want to | Read |
|---|---|
| **Adopt these workflows in a repo** (repo types, caller templates, pin policy, advisory→gate, worked examples) | [`REUSABLE-WORKFLOWS.md`](./REUSABLE-WORKFLOWS.md) — **start here** |
| Copy a ready-made caller | [`caller-templates/`](./caller-templates/) — the maintained originals |
| Wire a repo into the *whole* platform (all five governance repos, propose-back flow) | [`docs/platform-setup/STARISIAN-GOVERNANCE-PLATFORM-SETUP.md`](./docs/platform-setup/STARISIAN-GOVERNANCE-PLATFORM-SETUP.md) |
| Understand the version-drift gate specifically | [`docs/setup-and-install.md`](./docs/setup-and-install.md) |
| Know which rule IDs a gate enforces | [`CI-Enforcement-Matrix.md`](./CI-Enforcement-Matrix.md) |
| Read the standards themselves | [`docs/standards-catalog.md`](./docs/standards-catalog.md) |
| Wire a **brand-new** repo end to end | `starisian-technologies-proprietary-license` → `GOVERNANCE-SETUP.md` |
| Authenticate to another repo | product-spec registry → `specs/_platform/SPARXSTAR-CROSS-REPO-ACCESS-STANDARD.md` |

---

## The three things people get wrong

Everything else is in the documents above. These three cause most of the
failures, so they are stated once, here, at the point of use.

**1. Copy the caller template; do not hand-write the caller.**
`caller-templates/` has one file per repo type — `standards-php-wordpress.yml`,
`standards-react.yml`, `standards-node.yml`, plus `dependabot.yml`. They sit
beside the workflows they call, so they are correct by construction. A
hand-written caller is how a repo ends up passing a secret the pinned tag does
not declare.

**2. Pin an immutable patch tag, and move `contract_ref` with it.**
D1 is immutable patch tags only — never `@main`, never the moving `@v1` alias.
The `version-check` job's `contract_ref` **must equal** the `@vX.Y.Z` your
other jobs pin; bare `v1` is rejected at validation. Bump both in one commit.
`caller-templates/dependabot.yml` opens the PRs that move them.

**3. Advisory first; gate when clean.**
Every new gate starts `enforcement_mode: advisory` — violations warn, nothing
blocks. Flip to `gate` once the job reports zero violations. Wiring a gate must
never stall a team, and leaving it advisory forever must never become normal.

---

## Two known sharp edges

- **`react-enforcement.yml` and `node-enforcement.yml` at `@v1.0.2` declare no
  `workflow_call` secrets** and set up no git auth, although they run
  `pnpm install`. Do **not** add a `secrets:` block to those jobs at that tag —
  workflow validation rejects a secret the callee does not declare, and the
  call fails at startup. A repo with private GitHub-hosted JS dependencies will
  fail in those jobs even when the `pnpm` job passes. Known platform gap; check
  this repo's tags for a release that ships the fix rather than working around
  it.
- **The `mode:` input is deprecated** in favour of `enforcement_mode`, with
  removal dated 2027-01-01. New callers set `enforcement_mode` only.

---

## Editing a workflow in this repo

**Tags follow edits, in the same sitting.** After any change to a reusable
workflow, move both the immutable semver tag and any moving major alias onto
the commit containing the edit, then verify:

```
git ls-remote origin 'refs/tags/v1*'
```

Verify from that command, never from memory. A consumer pinned to a tag that
still points at the old commit sees no change and reports the workflow as
broken.

**Declare before callers pass.** A secret must appear in the callee's
`on.workflow_call.secrets` *and* be inside the tag a caller pins, before that
caller can pass it. Order is always: declare → re-tag → callers pass.

**Never re-cut a green mint step to match a document.** The enforcement
workflows here deliberately SHA-pin `create-github-app-token` with an `app-id:`
input, while the registries use `@v3` with `client-id:`. Both are live and both
work; the SHA pin is a supply-chain choice. Standardising them is an open
platform decision, not a licence to edit passing CI.
