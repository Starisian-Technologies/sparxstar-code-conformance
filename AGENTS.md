# Agent Instructions — Product Specifications

## Repository structure


sparxstar-platform-standards/
  patents                - platform and product patents
  platform/              ← platform-level governance docs
  specs/
    general/             ← cross-cutting specs (not product-specific)
    IAMC/
    DVE/
    IAtlas/
    Starmus/


## Rules for where things go:

- **`platform/`** — governs the whole platform regardless of product.
  The governance architecture, the spec template, the naming convention.
  Not ADRs (those are in the registry). Not coding standards (those are
  in the coding-standards repo).
- **`specs/general/`** — cross-cutting specs that apply to multiple
  groups but aren't governance. Design tokens, font stack, consent
  model, wire-key convention.
- **`specs/{Group}/`** — one file per product following the template in
  `platform/product-spec-document-structure.md`. Plus one group
  architecture doc per group.
- **Group folders match the contracts repo:** IAMC, DVE, IAtlas, Starmus.
  Same names, same grouping. An agent that knows its group navigates
  both repos the same way.

## Document rules

- **One file per product. No exceptions.** No supplementary docs, no
  companion files, no "part 2." Everything about one product lives in
  one spec file.
- **Every spec follows the template** in
  `platform/product-spec-document-structure.md`. Twelve mandatory
  sections, same order, every time.
- **Version in the document, not the filename.** The file is always
  `helios-spec.md`. Git history tracks versions.
- **Cross-product docs** (suite architecture, identity model) also get
  one file each, named `{system-name}-architecture.md`.

## What agents may do

- Draft new specs for owner review, following the template
- Update existing specs to reflect new ADR decisions
- Propose spec amendments when code in product repos reveals a gap
- Move cross-product specs here from product repos
- Add seam definitions between products
- Add files to `specs/general/` for cross-cutting specs

## What agents must NOT do

- Make decisions. Specs describe what was decided — they do not decide.
  If a spec requires a new decision, flag it as needing an ADR. Do not
  write the decision into the spec.
- Invent features. If no decision or owner direction exists for a
  feature, do not spec it. "No spec exists for X" is a hard stop — X
  becomes an open question, never a drafted rule.
- Create multiple files for one product. One file per product. If you
  feel the need for a second file, the first file is incomplete —
  add to it.
- Put product-specific content in `specs/general/`. General specs apply
  across groups.
- Put platform governance in `specs/`. Governance lives in `platform/`.
- Edit governance snapshot files under `.github/instructions/governance/`.
  These are auto-synced and read-only.
- Publish client-specific content. Specs describe platform capabilities.
  Client deployments are configured, not specced.

## The rule for Claude sessions

When you are drafting a spec and reach a question that has no answer in
the governance reference or in the owner's direction:

STOP. State the question. Mark it as needing an owner decision. Do not
draft a rule to fill the gap. Do not ask the owner to ratify something
you invented. Wait.

This is the single most important instruction for this repo. Every time
an agent drafts past an unasked question, the downstream sessions build
against fiction. The cost of stopping is one message. The cost of
continuing is a retraction cascade across every repo that read the spec.

## Governance reference

Read `.github/instructions/governance/` for current ADR decisions,
invariants, and open questions. These are the rules your specs must
satisfy. Do not assume rules that are not in the governance reference.
