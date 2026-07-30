# sparxstar-code-conformance — Roadmap & Maintenance Reference

Last updated: 2026-06-25
State at time of writing: `v1.0.0` / `v1` tags at `64b0ccf`, `2b534ac` merged to main.

This document is the memory of where this repo is, what it still owes, and
what ongoing maintenance it requires so nothing silently rots. It is written
for whoever touches this repo next — human or agent.

---

## Current State (what exists and is working)

### Reusable enforcement workflows — live and tested

| Workflow | Fixtures | Behavioral self-test | Bucket-3 status |
|---|---|---|---|
| `php-enforcement.yml` | pass, pass-vip-fallback, fail | yes | warn-only — pending ADR |
| `css-enforcement.yml` | pass, fail | yes | 5 rules warn-only — pending ADR |
| `media-enforcement.yml` | pass, fail | yes | 2 rules warn-only — pending ADR |
| `node-enforcement.yml` | pass, fail | yes (presence only) | warn-only — pending ADR |
| `react-enforcement.yml` | pass, fail | yes (presence only) | warn-only — pending ADR |
| `pnpm-enforcement.yml` | **no fixtures** | no | n/a |
| `version-drift-enforcement.yml` | n/a (no fixture model) | n/a | always hard — no mode |

### Caller templates — live

- `caller-templates/standards-php-wordpress.yml`
- `caller-templates/standards-node.yml`
- `caller-templates/standards-react.yml`

All templates ship with `enforcement_mode: gate` and pin `@v1`. The adoption
guide in `REUSABLE-WORKFLOWS.md` tells new consumers to switch each job to
`advisory` during onboarding and flip to `gate` once violations are zero.

### Key operational facts

- **Blocking mode value:** `gate` (not `required` — renamed in v1.0.0)
- **Warn-only mode value:** `advisory`
- **Org-locked pin:** `@v1.0.0` (immutable). `@v1` alias also exists.
- **Legacy `mode` input removal date:** 2027-01-01 — all callers on the old
  `mode` input must migrate to `enforcement_mode` before that date.
- **Floor:** `v1.0.0` (set in `config/version-policy.yml`)
- **Drift records:** written to `drift/<slug>.json` on main via contract-sync
  App on each `version-drift-enforcement` run.

---

## What This Repo Still Owes

### 1. pnpm-enforcement fixtures (highest priority)

**Gap:** `pnpm-enforcement.yml` has no fixture directory and no self-test
coverage. Every other workflow has at least pass/fail fixtures and is tested
in CI. `pnpm-enforcement` is running untested.

**What to build:**
- `fixtures/pnpm-enforcement/pass/` — a minimal repo with `package.json`,
  `pnpm-lock.yaml`, and no `package-lock.json` or `yarn.lock`. Should have
  `pnpm` as the only lockfile.
- `fixtures/pnpm-enforcement/fail/` — a repo with a `package-lock.json`
  (npm lockfile) present alongside `package.json`, triggering the lockfile
  violation.
- Add `pnpm fixture self-test` job to `fixture-self-test.yml` covering the
  PASS and FAIL cases, same pattern as the existing jobs.

**Why it matters:** pnpm-enforcement is the most universally applied gate
(every JS repo). An untested gate that silently passes when it should fail
is worse than no gate.

---

### 2. Bucket-3 rules — ADR ratification and fixture coverage

Every enforcement workflow has a `# Bucket-3: warn-only gaps` section at the
bottom. These rules emit `::warning::` annotations today but never block merge.
They flip to blocking only when their backing ADR is ratified.

**Rules currently warn-only:**

| Workflow | Rule ID | What it checks |
|---|---|---|
| `css-enforcement` | CSS-PERF-003 | Large box-shadow px values |
| `css-enforcement` | CSS-PERF-005 | text-shadow on body/paragraph text |
| `css-enforcement` | CSS-MAINT-001 | `!important` outside utility files |
| `css-enforcement` | CSS-PERF-006 | `@font-face` missing `font-display` |
| `css-enforcement` | CSS-A11Y-002 | `@keyframes` without `prefers-reduced-motion` guard |
| `media-enforcement` | MEDIA-CONSTRAINTS-002 | `channelCount > 1` in getUserMedia |
| `media-enforcement` | MEDIA-CONSTRAINTS-003 | Audio bitrate > 32 kbps |
| `php-enforcement` | PHP-SQL-003 | `$wpdb` query without `LIMIT` |

**What to do per rule, in order:**
1. Get the backing ADR written and ratified in the relevant product registry.
2. Add a fail fixture that triggers the rule (the pass fixtures for these
   rules already exist — the fail side is missing).
3. Add a self-test assertion in `fixture-self-test.yml` (PASS stays pass,
   new FAIL triggers the rule).
4. Remove the `# WARN-ONLY` comment and change the step's `::warning::` to
   `::error::` + `exit 1` (or wire it through the `enforce_block` flag).
5. Update `docs/enforcement-matrix.md` status from `WARN` to `ENFORCED`.

Do not skip the fixture step. The rule already existed when it was warn-only;
you still need to prove the new blocking logic triggers correctly before
flipping.

---

### 3. Presence-validation steps — fixture coverage

`node-enforcement.yml` and `react-enforcement.yml` have presence-validation
steps (checking that required packages are declared in `package.json`). These
run warn-only today and carry:

```
# WARN-ONLY — flips to required after self-test fixtures authored (STD-TOOLCHAIN-001 §4)
```

The existing pass fixtures satisfy the presence checks. The **fail side** is
incomplete — the fail fixtures for node and react currently only test lockfile
violations (wrong lockfile type), not presence violations (missing required
packages).

**What to add:**
- `fixtures/node-enforcement/fail/` — add a `package.json` that is missing
  `typescript-eslint` or has `eslint-plugin-node` (the prohibited package) to
  trigger NODE presence warnings.
- `fixtures/react-enforcement/fail/` — add a `package.json` that is missing
  `vitest` or `typescript-eslint` to trigger REACT presence warnings.
- Self-test assertions for each new fail case in `fixture-self-test.yml`.
- Once covered: remove `# WARN-ONLY`, flip `::warning::` → `::error::` +
  `exit 1` on the specific presence steps, flip to blocking.

---

### 4. GovernedActionGateRule — ADR and rollout

`standards/phpstan-rules/GovernedActionGateRule.php` exists and is shipped
with the Composer package. It is referenced in `STD-TOOLCHAIN-001.md §5` as
warn-only pending ADR ratification.

**What is owed:**
- An ADR in the relevant product registry documenting the decision to require
  `assert_governed_action()` at mutation entry points.
- Once ADR is ratified: flip the PHPStan rule from warn-only to error-level
  in `phpstan.neon` and update `STD-TOOLCHAIN-001.md §5` status.
- A fail fixture in `fixtures/php-enforcement/fail/` with an unguarded
  mutation entry point to prove the rule catches it.

---

### 5. STD-TOOLCHAIN-001 ADR citations — sections still pending

`docs/STD-TOOLCHAIN-001.md` lists these sections as proposal-only pending ADR:

| Section | Topic | Needed |
|---|---|---|
| §3 | Three-Axis Versioning | ADR citation |
| §4 | Self-Test Fixtures | ADR citation |
| §5 | Governed-Action Gate | ADR citation |
| §9 | Profiles | ADR citation |
| §10 | Bucket Classification | ADR citation |

Only §7 (pnpm, ADR-017) and §8 (Exception Process, ADR-042) are normative
today. The standard cannot be treated as binding for the uncited sections until
their ADRs exist. The doc's status header makes this explicit — do not remove
the warning banner until all sections have citations.

---

### 6. Python profile — no enforcement workflow yet

`standards/profiles/python/v1/manifest.json` exists. There is no
`python-enforcement.yml` reusable workflow and no caller template for Python
services. `docs/python-standard.md` is authored.

**What to build when a Python product repo needs enforcement:**
- `.github/workflows/python-enforcement.yml` — reusable workflow following
  the same pattern (inputs: `enforcement_mode`, `profile_version`;
  `on.workflow_call`; secrets block; exception parser; bucket-1/2/3 structure).
- Fixtures: `fixtures/python-enforcement/pass/` and `fail/`.
- Self-test job in `fixture-self-test.yml`.
- `caller-templates/standards-python.yml`.
- Entry in `REUSABLE-WORKFLOWS.md` table.

Do not build this speculatively. Build it when the first Python repo needs it.

---

### 7. `version-drift-enforcement` — caller template integration

No caller template currently includes a `version-check:` job calling
`version-drift-enforcement.yml`. The gate exists and is documented in
`docs/setup-and-install.md`, but consumers adopting from a template will
not get it automatically.

**Decision needed:** should the three caller templates include a
`version-check:` job by default, or is it opt-in? If opt-in, the adoption
guide should explicitly tell consumers when to add it. If default, add it
to all three templates with `write_drift_record: false` for advisory runs,
switching to `true` once conformance is confirmed.

Until this decision is made, the gate is wired only by consumers who read the
setup doc and add it manually. That is acceptable for v1 but becomes a gap
as adoption widens.

---

## Ongoing Maintenance — What Must Not Slip

### Legacy `mode` input removal (hard date: 2027-01-01)

Every enforcement workflow emits a deprecation warning when a caller passes
the old `mode` input instead of `enforcement_mode`. The comment in each
workflow says:

```
# REMOVAL: 2027-01-01 — all callers must migrate to enforcement_mode before this date
```

**Before 2026-10-01:** audit all consuming repos (check `drift/` records and
GitHub search) for any caller still using `mode:` instead of
`enforcement_mode:`. Contact owners.

**Before 2027-01-01:** remove the legacy bridge from all six enforcement
workflows. The bridge is the `elif [ -z "$em" ]` block that falls back to
`$lm`. After removal, an empty `enforcement_mode` with no `mode` falls
through to the unknown-value error immediately.

Do not extend the 2027-01-01 date without a deliberate decision. It was set
with full awareness of the migration window.

---

### Tagging discipline — every merge that changes workflow behaviour

The `v1` alias must move to every merge commit that changes reusable workflow
logic, inputs, or secrets. The process:

1. Merge PR to main.
2. From an authenticated local clone: `git tag v1.x.y <sha>` (new immutable
   tag) and `git tag -f v1 <sha>` (move alias).
3. Push both: `git push origin v1.x.y` and `git push -f origin v1`.
4. Update `config/version-policy.yml` `floor:` only if the change is a
   breaking contract change that older pinned callers cannot safely run.

Raising the floor is a breaking change for any consumer still on an older
pin. Do not raise it without notifying all consuming repos first and giving
them a migration window.

**Never push tags through the GitHub Actions proxy** — it blocks `refs/tags/`
pushes with 403. Tags must be pushed from an authenticated local clone with
push access to the repo.

---

### Drift record concurrency

`version-drift-enforcement.yml` uses `git pull --rebase && git push` to commit
drift records to `drift/` on main. Multiple consumers running simultaneously
will queue-rebase correctly. If a drift commit fails (push rejection after
rebase), the run will error — this is intentional; the caller should re-run.

If the `drift/` directory grows large (many consuming repos), consider a
periodic cleanup PR that archives or removes drift records for repos that
have been decommissioned. There is no automated cleanup today.

---

### Fixture accuracy — kept in sync with workflow logic

When a workflow rule changes its detection pattern (a grep regex, a Python
check, a new prohibited identifier), the corresponding fixture must change at
the same time in the same PR. The self-test CI will catch regressions, but
only if the fixture was accurate before the change.

The rule: **no PR changes a detection pattern without updating the fixture that
proves it**. If a pass fixture starts tripping a new detection, it is a
regression — fix the detection or the fixture, do not suppress the test.

---

### Exception expiry — no silent accumulation

The exception parser in every enforcement workflow hard-fails on a past
`expires:` date. This means consuming repos that added exceptions during
onboarding will start failing CI when those dates pass, even if violations are
not re-introduced.

This is by design. The platform's job is not to silently ignore old exceptions.
Consuming repos must either fix the underlying violation before the expiry or
file a new exception with a new `approval:` ADR and updated `expires:` date.

Platform responsibility: if a consuming repo's exception expires and their CI
breaks, the first diagnostic is always "check `.standards/standards-exceptions.yml`
for expired entries." This is documented in `REUSABLE-WORKFLOWS.md §3` and
worth repeating verbally to teams onboarding for the first time.

---

### `config/version-policy.yml` — floor is a breaking change

The floor is currently `v1.0.0`. Raising it means any consumer pinned to an
older immutable tag (if one existed below the floor) would start failing
version-drift-enforcement immediately, with no grace period.

Today all consumers should be on `v1.0.0` or `v1` since those are the only
tags. But as new releases are cut and some consumers lag, raising the floor
without notice will break those callers. The rule:

- Do not raise the floor without a platform announcement with a minimum
  four-week notice window.
- Raise it in a dedicated PR with a clear subject line so it is visible in
  history.
- After raising, cut a new semver tag and move `v1` to that commit.

---

## Not In This Repo (do not add)

These things explicitly do not belong here, to keep the boundary clean:

- **ADR text** — architecture decisions live in product registries, not here.
  This repo cites ADR numbers; it does not store decision rationale.
- **Product-specific rules** — rules that apply to one product repo only go
  in that repo. Only org-wide rules that apply to a whole profile live here.
- **PATs or GITHUB_TOKEN hacks** — cross-repo reads use the composer-resolver
  App (Block A). Cross-repo writes use contract-sync (Block C). No PATs, no
  `secrets: inherit`, no `GITHUB_TOKEN` for cross-repo work.
- **`@main` pins in caller templates** — never. Templates ship pinned to an
  immutable tag. Document the pin strategy in the template header comment.
