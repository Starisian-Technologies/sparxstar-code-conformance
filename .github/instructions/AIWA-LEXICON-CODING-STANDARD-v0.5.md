# AIWA Lexicon Coding Standard v0.5

**Branch/PR:** claude/aiwa-lexicon-coding-standard (PR \#3) **Status:** Active draft — extends v0.4 **Authors:** Max Barrett / Starisian Technologies · language authority: Muhammed Dibbasey **Date:** June 2026 **Supersedes:** v0.4 only where stated (§1 authority flip). Everything else in v0.4 stays in force.

This version specifies three things as one standard, in execution order: **Part 1** — flip the domain authority to SemDom-primary. **Part 2** — define the AIWA Level and Usage Type controlled vocabularies (new). **Part 3** — the concept-slot table that drives the app (consumes Parts 1 and 2).

A standing law from the writing-tools direction governs Part 3 and anything user-facing built on it: **the system asks, it never corrects.** AI is the lowest authority (platform invariant). Every suggestion, level, and question degrades silently where data is absent.

---

# PART 1 — Domain Authority Flip (SemDom-primary)

The recorded domain becomes the SIL Semantic Domain node. WordNet drops from authority to confirmer. Concepticon's SEMANTICFIELD becomes an independent third vote.

## 1.1 Recorded fields

| Field | Role | Source |
| :---- | :---- | :---- |
| `Domain_ID` | **Authority.** The recorded domain. | SIL SemDom node (e.g. `1.6.1.2`) |
| `Domain_Label` | Authority label | SemDom |
| `Domain_Confidence` | Agreement signal | computed — see §1.2 |
| `WordNet_Synset` | Confirmer only | WordNet |
| `WordNet_Lexname` | Confirmer only | WordNet |
| `Concepticon_SemanticField` | Independent third vote | Concepticon |

WordNet fields are retained for disambiguation and confidence, never as the recorded domain.

## 1.2 Confidence by agreement (relabel, not rebuild)

The existing 4,509 HIGH rows already carry the SemDom node where it agreed — this is a relabel \+ confidence pass.

- `HIGH` — SemDom node present AND (WordNet lexname OR Concepticon SEMANTICFIELD) agrees.  
- `MED` — SemDom node present, no corroboration, no conflict.  
- `QUESTION` — sources conflict, OR only WordNet fired (no SemDom). Never auto-resolve a QUESTION; route it to the secondary-domain question lane (Part 3).

WordNet-only assignments from earlier passes are **demoted to QUESTION**, not kept as domain — this reverses the v0.4 default and is the core of the flip.

---

# PART 2 — AIWA Level & Usage Type (controlled vocabularies)

## 2.1 AIWA\_Level — what it measures (important reframe)

For a fluent L1 Mandinka speaker, level is **not** vocabulary knowledge — they already know the word orally. AIWA\_Level measures **orthographic/literacy difficulty and developmental appropriateness for reading and writing the word**. It is therefore NOT CEFR. CEFR\_Approx may be stored as a loose reference signal, but **AIWA\_Level wins** on every conflict.

Assignment signals (objective, AI-proposable): word frequency in daily Gambian Mandinka, length / syllable count, presence of novel graphemes (ŋ, ñ) and length-doubling, concreteness/imageability, single vs compound/derived form, and register (Usage\_Type). Each level carries a default scaffold weight — the fade schedule from the writing-tools direction.

| Level | Reading/writing stage | Typical words | Default scaffold |
| :---- | :---- | :---- | :---- |
| **A0** | Emergent decoding | Highest-frequency, short, concrete, imageable (water, bird, hand) | Max: visual word \+ audio \+ first-letter cue |
| **A1** | Early | Common concrete daily words; may carry ŋ/ñ; single domain | Heavy: visual \+ audio |
| **A2** | Developing | Longer/compound everyday words; vowel-length contrasts; mild abstraction | Moderate: audio on tap |
| **B1** | Independent | Storytelling connectors, abstract nouns, derived forms, reduplication | Light: on hesitation only |
| **B2** | Proficient | Lower-frequency, register-marked, figurative/proverbial | Minimal |
| **C1** | Advanced / scholarly | Rare, archaic, religious/scholarly (e.g. Ajami-register terms), specialized | Reference only — no scaffold |

**Authority rule:** AI may *propose* A0–A2 from the objective signals. B1+ placement and all final level assignments are curriculum/human authority. Senior Secondary "Step Up" vocabulary is human-only and not auto-leveled.

## 2.2 Usage\_Type — controlled vocabulary (multi-valued)

A word may carry several (e.g. Living \+ Borrowed \+ Religious). These feed WordPad register coaching and protect the truth of living Mandinka.

| Value | Meaning | Who may assign |
| :---- | :---- | :---- |
| `Traditional` | Established heritage word | Human (Muhammed/elder) |
| `Living` | In active daily use | Corpus-inferable \+ human confirm |
| `Borrowed` | Loanword (set `Borrowed_From`) | Etymology/corpus-inferable; flag |
| `Religious` | Islamic/devotional register | Human \+ lexical signal |
| `School/Formal` | Formal/educational register | Human |
| `Child-Friendly` | Content suitable & useful for young readers (distinct from difficulty) | Human/curriculum |
| `Regional` | Dialectal variant | Human — Muhammed (dialect authority) |
| `Sensitive` | Culturally restricted / requires care | **Human ONLY — never AI.** Ties to DVE restriction handling |
| `Unverified` | Not yet speaker-confirmed | System default |

**Rule:** `Sensitive` and `Regional` are never AI-assigned. `Unverified` is the default until speaker affirmation (highest-weight signal per platform invariant).

---

# PART 3 — The Concept-Slot Table (the app driver)

One row per Concepticon concept present in the lexicon. This is the artifact the front end reads to drive prompts, suggestions, colexification questions, and the reaction loop.

## 3.1 Columns

| Column | Source | Notes |
| :---- | :---- | :---- |
| `Concept_ID` | Concepticon | alignment spine |
| `English_Prompt` | Concepticon gloss | placeholder/prompt only — never authority |
| `Ontological_Category` | Concepticon | Person/Thing, Action, Property… |
| `Domain_ID` / `Domain_Label` | Part 1 | SemDom authority |
| `Mandinka_Form` (1..n) | MASTER | the surface form(s); may be empty (scaffold slot) |
| `POS` | MASTER |  |
| `AIWA_Level` | Part 2 |  |
| `Usage_Type[]` | Part 2 |  |
| `Suggestion` | MASTER | the offered word for this concept |
| `Colex_Question` | CLICS | generated — see §3.2 |
| `Reaction_Options` | fixed set | see §3.3 |
| `Audio_Status` | MASTER | none/pending/verified |
| `Confidence` | Parts 1–2 |  |
| `Provenance` | MASTER | source incl. manuscript citations where applicable |

Empty `Mandinka_Form` is legal and meaningful: it is a **scaffold slot** — an English-prompted concept awaiting a surface form. This is the bootstrap mechanism that lets any language (or a gap in Mandinka) be filled through use.

## 3.2 Colex\_Question generation (from CLICS)

For each `Concept_ID`, look up CLICS partners. For partners with `FamilyWeight ≥ 5` (strong cross-linguistic colexification), surface the top 1–3 as a question, never an assertion:

"Your word for **{English\_Prompt}** — does it also mean **{partner\_gloss}**?"

Example (HEAR): "Your word for *hear* — does it also mean *understand* or *obey*?"

If no partner clears the threshold, `Colex_Question` is empty (silent degradation). The question feeds two lanes: translation-warning, and the secondary-domain QUESTION lane from Part 1\.

## 3.3 Reaction\_Options (fixed)

`Yes — same word` · `No — different word` · `Sometimes / depends`

A speaker's reaction is a high-weight signal (platform invariant: speaker affirmation outranks AI). Reactions are append-only corrections, never overwrites.

---

# Execution Order for PR \#3

1. **Part 1** — relabel domain authority to SemDom-primary; demote WordNet-only to QUESTION; compute `Domain_Confidence`. (Mostly relabel on existing HIGH/MED rows.)  
2. **Part 2** — add `AIWA_Level`, `Usage_Type[]`, `Borrowed_From`, `CEFR_Approx` columns; run AI-proposable A0–A2 \+ Borrowed inference; leave B1+/Sensitive/Regional for human pass.  
3. **Part 3** — assemble the concept-slot table joining MASTER × Concepticon × CLICS × Parts 1–2; generate `Colex_Question`; emit scaffold slots for concepts with no Mandinka form.

# Open — human authority (route to Muhammed)

- Final B1+ level placements; all `Sensitive`/`Regional` tags; `Traditional` vs `Living` calls.  
- Colex\_Question confirmations are speaker work, not editor work.  
- These join the existing orthography questions document rather than being resolved by any dataset.

