SPARXSTAR Engineering Standards v2
==================================

**Enterprise Implementation Specification**

* * * * *

1\. Engineering Principles (Non-Negotiable)
-------------------------------------------

These do not change with tools, frameworks, or vendors.

-   Code is written as if it will be **licensed, audited, sold, and maintained by strangers**

-   **Correctness over speed** --- broken fast systems cost more than slow correct ones

-   **Offline is a state, not an error**

-   **Global scope is hostile** --- everything must be explicitly namespaced or prefixed

-   **Platform independence where possible, platform optimization where necessary**

-   **Design for weakest environments first (2G, low RAM, old Android)**

* * * * *

2\. Standards Classification
----------------------------

All rules fall into one of four categories:

### Mandatory (M)

Must be followed. Violations block merge.

### Preferred (P)

Strong default. Deviations require justification.

### Situational (S)

Used when context applies.

### Prohibited (X)

Never allowed.

* * * * *

3\. Language & Runtime Standards
--------------------------------

### PHP

-   (M) PHP 8.2+

-   (M) `declare(strict_types=1);`

-   (M) PSR-1, PSR-4, PSR-12

-   (X) PSR-2 (deprecated)

-   (P) readonly properties/classes where appropriate

-   (S) enums for domain modeling

-   (S) fibers only with documented use case

### WordPress

-   (M) WordPress 6.8+

-   (M) WordPress VIP standards override PSR when conflicting

-   (M) Multisite-first architecture --- never retrofitted

-   (M) All code network-aware from inception

### Architecture

-   (M) Business logic isolated from WordPress-specific code

-   (M) Platform adapters/interfaces for portability

-   (P) Laravel / C# / external services supported via abstraction layers

* * * * *

4\. Namespacing & Prefixing
---------------------------

### Namespaces

-   (M) `Starisian\Sparxstar\{Product}`

-   (X) Abbreviations or deviations

### WordPress Global Scope

-   (M) All globals prefixed:

    -   functions

    -   hooks

    -   CPTs

    -   taxonomies

    -   meta keys

    -   options

    -   DB tables

Example:

spx_\
aiwa_\
sirus_

-   (X) Unprefixed global identifiers

* * * * *

5\. Static Analysis & Linting
-----------------------------

### Static Analysis

-   (M) PHPStan Level 5 minimum

-   (P) Level 8+ for core systems

-   (M) No suppression without:

    -   inline reason

    -   linked issue or remediation plan

### Linters (Report Mode Only)

-   (M) PHPCS (VIP + PSR-12)

-   (M) ESLint

-   (M) Stylelint

-   (M) HTMLHint

-   (M) markdownlint

-   (M) JSON linting

-   (X) Auto-fix in CI

-   (M) Lint failures block merge

* * * * *

6\. Stack Awareness (Production Model)
--------------------------------------

### Preferred Production Stack

Cloudflare (CDN, WAF, Workers)\
→ Nginx\
→ Varnish\
→ Apache\
→ PHP-FPM\
→ MariaDB\
→ Redis

### Rule

-   (M) Code must **not break caching, proxying, or edge behavior**

-   (P) Code should be aware of upstream/downstream layers

-   (S) Must degrade gracefully when layers are absent (local/dev)

* * * * *

7\. First-Party Platform Services
---------------------------------

These are **platform primitives**, not optional plugins.

### Sirus (Context Engine)

-   (M) Used for device, network, and environment context

-   (X) Custom device fingerprinting

-   (M) Frontend error reporting through Sirus

### Helios (Authentication)

-   (M) Trust Helios-issued identity

-   (X) Custom frontend auth systems

-   (X) Direct use of `wp_set_auth_cookie()` for frontend users

### Starmus (Audio)

-   (M) All recording via Starmus

-   (X) Raw MediaRecorder implementations

* * * * *

8\. Africa-First Performance Model
----------------------------------

### Network

-   (M) Design for 2G/3G baseline

-   (M) Resumable uploads (TUS) for unreliable networks

-   (M) Generous timeouts (≥30s)

### Device

-   (M) Optimize for low RAM (1--2GB)

-   (M) Avoid heavy client computation

-   (M) Mobile-first UI

### Offline

-   (M) Offline state required

-   (M) Service Worker for data-input features

-   (M) IndexedDB for persistence

-   (X) localStorage for critical data

### Sync

-   (M) Resumable

-   (M) Idempotent (no duplication/corruption)

* * * * *

9\. PWA Requirements
--------------------

-   (M) App shell architecture

-   (M) Service worker

-   (P) Installability

-   (S) Push notifications (phase-based)

* * * * *

10\. Media & Uploads
--------------------

### Uploads

-   (M) TUS required for:

    -   large files

    -   unreliable network flows

    -   user-generated content

### Processing

-   (M) FFmpeg (sandboxed)

-   (M) ImageMagick (sandboxed)

-   (X) User-controlled execution parameters

### Storage

-   (M) Abstracted storage layer

-   (P) Cloudflare R2 primary

-   (P) S3 fallback

-   (X) Hardcoded storage URLs

* * * * *

11\. Multisite Requirements
---------------------------

-   (M) Network-aware from line one

-   (M) Proper use of:

    -   `$wpdb->prefix`

    -   `get_option()` vs `get_site_option()`

### Lifecycle

-   (M) Activation handles all sites

-   (M) New site creation initializes automatically

* * * * *

12\. Database Standards
-----------------------

-   (M) Prepared statements only

-   (M) Sanitized queries

### Storage Strategy

-   CPT → content

-   Custom tables → high-volume/structured data

-   Redis → cache/transient

### Rule

-   (P) Use `dbDelta()` for standard schema management

-   (S) Versioned migrations allowed when justified

* * * * *

13\. Data Handling & Security
-----------------------------

### Core Principle

**Sanitize → Validate → Escape (in that order)**

-   (M) Sanitize input

-   (M) Validate domain logic

-   (M) Escape output

### Functions

-   `sanitize_text_field()` → machine data

-   `wp_kses_post()` → human content

-   `esc_*()` → output context

* * * * *

14\. Geo & Privacy
------------------

-   (M) GeoIP2 for geolocation

-   (M) IP anonymization (last octet zeroed)

-   (X) Trusting user-supplied location blindly

* * * * *

15\. Testing Strategy
---------------------

-   (M) PHPUnit (backend)

-   (M) Jest (frontend)

-   (M) Playwright (E2E)

-   (M) axe-core (accessibility)

-   (S) Puppeteer for automation tasks

* * * * *

16\. Commercialization Standards
--------------------------------

-   (M) No hardcoded credentials

-   (M) No undocumented APIs

-   (M) Capability-based access control

-   (M) License headers in all files

-   (M) Dependency license audit

-   (X) Commented-out code in production

-   (X) Untracked TODOs

* * * * *

17\. Documentation
------------------

-   (M) DocBlocks for all public interfaces

-   (P) Generated reference documentation

-   (M) Manual documentation for:

    -   architecture

    -   integrations

    -   operational procedures

* * * * *

18\. Release Engineering
------------------------

### Single Source of Truth

-   (M) One canonical version source

### Automated Release Pipeline

On tag (`v*`):

1.  Validate version consistency

2.  Run lint suite

3.  Run full test suite

4.  Build/minify assets

5.  Generate translations

6.  Package distribution

7.  Generate checksums

8.  Publish release

-   (M) All steps required

-   (X) Manual releases

* * * * *

19\. Enforcement Model
----------------------

-   (M) CI blocks merge on:

    -   lint failure

    -   test failure

    -   static analysis failure

-   (M) Exceptions require:

    -   documented reason

    -   linked issue

    -   defined resolution path

* * * * *

20\. The Short Version (Operational)
------------------------------------

-   Modern PHP only

-   WordPress VIP when applicable

-   PSR everywhere else

-   Prefix everything global

-   Multisite first

-   Offline-first always

-   2G-first design

-   Use platform services (Sirus, Helios, Starmus)

-   No silent failures

-   No hidden dependencies

-   Every line commercial-ready

* * * * *

Final Positioning
-----------------

This is no longer a "coding standard."\
This is a **platform contract**.

It defines:

-   how systems behave

-   how data flows

-   how users are supported

-   how products scale commercially

If enforced, this creates:

-   predictable engineering output

-   faster onboarding of new developers

-   reduced long-term maintenance cost

-   real enterprise credibility
