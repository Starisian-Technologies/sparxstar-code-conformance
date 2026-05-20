# Questions for Clarification — Coding Standards Review

This file was reviewed and previously raised questions that no longer match the current
`sparxstar-coding-standards-v1.md` have been removed.

At present, there are no open clarification questions tracked here.

If new questions arise, add only items that reference the current section titles,
terminology, and scope of `sparxstar-coding-standards-v1.md` so reviewers are not
misled by obsolete review notes.
The section title names three specific products. The content is already written in product-agnostic terms.

**Questions:**
- Should the section title become "Edge Layer — CDN, Reverse Proxy, HTTP Cache" (describing roles)?
- In Section 7.4, "Varnish and Cloudflare are disposable caches" — should this become "Edge caches and CDN layers are disposable caches"?

---

## 6. Trust Table (Lines 83–85) and Source of Truth Table (Section 8.2)

Both tables name `Redis` and `MariaDB` specifically.

**Questions:**
- Should these become role-labelled rows — e.g., `Distributed Cache (Redis or equivalent)` and `Primary Database (MariaDB or equivalent)` — or just `Cache Layer` and `Primary Database`?
- Section 12.1 write-order steps name `Cache invalidation (Redis)` — should this become `Cache invalidation (distributed cache layer)`?

---

## 7. Code Example in Section 8.3

The cache invalidation pseudocode uses `Redis::delete($cache_key)` and `EdgeCache::purge($route)`.

**Questions:**
- Should `Redis::delete(...)` become `Cache::delete(...)` or `ObjectCache::delete(...)` to be implementation-neutral?
- The pattern itself (DB write → cache delete → edge purge) is the important concept. Should the code use a generic `Cache` abstraction class name?

---

## 8. Section 2.7 — WordPress Plugin Rules

The section uses `wp_enqueue_script('sparxstar-recorder', ...)` as an example — which names a specific internal plugin.

**Questions:**
- Should the example use a generic plugin name (e.g., `my-component`) rather than an internal product reference?
- The rules in 2.7 are WordPress-specific by nature. Should this section stay, move to an appendix, or be abstracted to a generic "Asset Loading" rule applicable to any framework?

---

## 9. Languages and Platforms Not Yet Covered

The PR description mentions React, C#, TypeScript, and Node.js as languages the team intends to use.

**Questions:**
- Should this document be expanded now to include stub sections for React/JS, C#, and TypeScript — even if the rules are minimal or TBD?
- Or should the current document remain PHP/WordPress-focused and new language sections be added as separate documents or PRs?

---

## 10. Database Layer Generalization

The PR description notes the database layer will eventually include PostgreSQL and Neo4j alongside MariaDB.

**Questions:**
- Should Section 2.4 (Database Rules) be titled "Relational Database Rules" and written to cover MariaDB and PostgreSQL equally, since both are relational?
- Should a separate stub section be added for graph database rules (Neo4j) — even if it only states "rules to be defined"?
- In Section 8.2 Source of Truth table, should "MariaDB" become "Primary Relational Database" with a note that graph and document stores follow separate rules?

---

## 11. Final "Applies To" Line (Line 910)

The document ends with: `Applies to: WordPress (latest stable), PHP (latest stable active support), JavaScript, GraphQL, TUS, provider-agnostic edge and infrastructure`

**Questions:**
- Should this be updated to reflect the broader intended scope — e.g., "Applies to: all server-side languages, all front-end JavaScript frameworks, all relational and graph databases, all API transport layers, provider-agnostic edge and infrastructure"?
- Or should it list the current primary stack and note that the standards extend to future languages and platforms as adopted?

---

*All other sections (Sirus, concurrency, async, distributed system rules, CI enforcement, security, data lifecycle, observability) are already written in language-agnostic terms and appear ready as-is.*
