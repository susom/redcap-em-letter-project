# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A REDCap External Module (EM) for Stanford's "Letter Project" ("Advance Directive: What Matters Most"). It manages
a multi-event survey workflow where a patient completes an initial letter/survey, invites up to 3 proxies
(decision makers) to review/respond via emailed links, and a witness form finalizes the record. The module
also renders the final letter as a PDF and exposes a public (NOAUTH) portal for proxies to log in with an
emailed code and reconcile responses across all parties before signing.

Namespace: `Stanford\LetterProject`. Repo: `susom/redcap-em-letter-project`. There is no build system, package
manager for JS, linter, or test suite in this repo — it is plain PHP loaded directly by the REDCap framework, plus
vendored JS libraries checked into `js/`.

## Running / testing

There is no local run/build/test command. This module only executes inside a full REDCap instance (it depends on
REDCap framework globals/classes like `REDCap`, `db_query`, `prep`, `generateRandomHash`, and `$module`/`$Proj`
injected by REDCap's EM framework). To exercise changes, deploy into a REDCap install (this repo lives under
`redcap-docker-compose-master/www/modules-local/`) and drive the actual survey/reconciliation flow in a browser —
there's no way to unit test the hooks or the NOAUTH pages in isolation.

`composer.lock`/`composer.json` only pulls in `tecnickcom/tcpdf` for PDF generation (vendored under `vendor/`).

## Architecture

### Event flow (driven by REDCap project configuration, not hardcoded)

All event/instrument/field names are project-settings (see `config.json`) resolved at runtime via
`$module->getProjectSetting(...)` — e.g. `first-event`, `final-event`, `starting-survey`, `letter-survey`,
`proxy-survey`, `witness-survey`, `email-survey`, `proxy-{1,2,3}-event`, `proxy-{1,2,3}-field`, `hash`, `hash-url`,
`code-field`, `questions` (repeatable). Never assume a fixed event/field name — always read it from project settings,
mirroring the existing code.

The rough lifecycle:
1. **Starting survey** (first event) completed → `LetterProject::setHash()` generates a random hash/URL (used as the
   proxy login link) and stores it back on the record.
2. **Email survey** / **letter survey** / **witness survey**, when completed in the first event, get copied into the
   **final event** via `LetterProject::copyToFinal()` — but only if the final event's copy of that instrument is not
   already complete (avoids clobbering reconciled data).
3. Witness survey completion in the final event additionally copies signature file fields
   (`copyOverSigFields()`) directly via SQL into `redcap_data` (or `REDCap::getDataTable()` on newer REDCap), since
   file/signature fields don't come along with a normal `saveData` copy.
4. Proxies are invited by email (`saveNewEmail()`) and, once they finish their surveys, all hooks funnel back through
   `redcap_save_record()` → `copyToFinal()`.
5. `redcap_survey_complete()` — when the witness survey completes in the final event — auto-redirects the browser
   into the NOAUTH `ProxyLetterReconciliation.php` portal (posts the saved hash/email code to log the user
   straight into the reconciliation view instead of the default REDCap "survey complete" page).

### Key files

- **`LetterProject.php`** — the EM class; all REDCap hooks (`redcap_save_record`, `redcap_survey_complete`,
  `redcap_survey_page_top`) plus the business logic described above (`copyToFinal`, `copyOverSigFields`,
  `saveNewEmail`, `setProxyEmails`, `setHash`, `saveTimestamp`, `lookupByCode`, `getResponseData`,
  `getWitnessPageStatus`, `setupLetter`).
- **`ProxyLetterReconciliation.php`** (NOAUTH page) — the public-facing entry point for proxies. Handles:
  - AJAX POST `action` dispatch (`saveResponse`, `emailPDF`, `downloadPDF`, `printPDF`, `saveNewEmail`,
    `checkWitnessForm`) — each branch sets `$result` and the tail of the file JSON-encodes and exits.
  - A login flow (`$_POST['login']`) that looks up a participant by emailed code via
    `LetterProject::lookupByCode()` (raw SQL against `redcap_data`, done because REDCap's `getData` filters are
    case-insensitive and this needs an exact code match), verifies a companion hash, then includes
    `pages/reconciliation.php` (the tabbed comparison UI, one tab per configured `questions` entry, built from
    `organizeResponses()`/`renderTabs()`/`renderTabDivs()`).
  - Falls through to `pages/login.php` if no login/action was posted.
  - `organizeResponses()` has hardcoded per-question layout logic (e.g. `q2` = 4 milestone sub-fields, `q5` =
    per-proxy decision-maker contact fields, `q7`/`q8` = value+"other" pairs, `q9`/`q13` = value+"other" pairs) that
    maps generic `q<N>` config keys to actual field name patterns, with a `p_` prefix used on proxy-event forms vs.
    the original/final arm. Any change to the survey question set likely needs a matching branch here.
- **`LetterPDF.php`** — extends TCPDF (`Stanford\LetterProject\LetterPDF extends TCPDF`) to render the final signed
  letter as a PDF, with a custom `Header()`/`Footer()` (Stanford Health Care letterhead) and page-building methods
  (`makeHTMLPage1`, etc.) that pull reconciled record data into an HTML template TCPDF then renders.
- **`GetLetter.php`** (NOAUTH page) — thin wrapper: calls `$module->setupLetter($record_id)` to build the PDF, then
  outputs it inline (`I`), for download (`D`), or inline with an auto-print JS call (`P`), based on `?action=`.
- **`RestCallRequest.php`** — a generic cURL-based REST client helper (get/set url/verb/body/auth/accept-type); not
  REDCap-specific.
- **`pages/login.php`** / **`pages/reconciliation.php`** — HTML/PHP views included by `ProxyLetterReconciliation.php`.
- **`config/tcpdf_config_alt.php`**, **`tcpdf_include.php`** — TCPDF configuration required before instantiating
  `LetterPDF`.
- **`js/letter_project.js`**, **`js/config.js`** — front-end for the reconciliation portal (AJAX calls into
  `ProxyLetterReconciliation.php`'s `action` dispatch). `js/ace/*` and `js/jquery-3.2.1.min.js` are vendored
  third-party libraries, not project code.

## Conventions specific to this module

- Field/event/instrument names are configuration, not constants — always resolve via
  `$module->getProjectSetting('<key>')` rather than hardcoding REDCap field/event names.
- `copyToFinal()`-style logic intentionally checks `<instrument>_complete` on the final event before overwriting, to
  avoid stomping already-reconciled data — preserve that guard when touching related code.
- Direct SQL against `redcap_data` (via `prep()`/`db_query()`/`REDCap::getDataTable()`) is used deliberately in a
  couple of spots to route around REDCap API limitations (case-insensitive filters in `lookupByCode`, missing
  file-field support in `saveData` for `copyOverSigFields`) — not a pattern to reach for by default, but expected
  here.
- `ProxyLetterReconciliation.php` and `GetLetter.php` are NOAUTH pages (see `no-auth-pages` in `config.json`) reached
  by unauthenticated participants via emailed links — treat all `$_GET`/`$_POST` input there as untrusted.