# Copilot Review Instructions — Product Specifications

This repository holds the technical specifications for SPARXSTAR platform
products. Specs define what each product does, how products connect, and
what the seams look like.

## Your role

You review every PR against the ADR registry. You have MCP access to the
internal ADR registry at `sparxstar-architecture-decision-record`. Use it.

## Review rules

- **One file per product.** If a PR adds a second spec file for a
  product that already has one, flag it. The existing file should be
  updated instead.
- **Every spec follows the template** in
  `platform/product-spec-document-structure.md`. All twelve sections
  must be present. If a section is missing, flag it.
- **Directory placement matters:**
  - `platform/` — platform governance docs only
  - `specs/general/` — cross-cutting specs, not product-specific
  - `specs/{Group}/` — product specs and group architecture docs
  - Do not put product specs in `platform/` or `specs/general/`
  - Do not put cross-cutting specs in a group folder
- Every spec must be consistent with the ADR registry. If a spec
  contradicts a decision, flag it.
- Specs must not assume answers to open questions. If a spec builds on
  an OQ, flag it and cite the OQ number.
- Cross-product specs (suite architecture, identity model, reward model)
  belong in the appropriate group folder or in `specs/general/` if they
  span groups.
- Client-specific content does not belong here. Use generic language.
  "The first client deployment" is acceptable. "AIWA" as a proper noun
  in a spec title is not.

## What you must flag

- A spec that contradicts an ADR or invariant
- A spec that assumes an answer to an open question
- A spec that should be in a product repo (single-product, no seams)
- A product name used where a generic term would work
- A governance snapshot file that appears to have been manually edited

## Files you must NOT edit or suggest changes to

- `.github/instructions/governance/*` — auto-synced, read-only
- Any file with a header stating "auto-generated, do not edit"
