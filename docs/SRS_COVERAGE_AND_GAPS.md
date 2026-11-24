# Tickets4You SRS v3.0 — implementation coverage and remaining work

This file distinguishes **source-code coverage**, **tested pure logic**, **untested database integration** and **missing/incomplete** requirements. The implementation is a modular coursework *prototype* and the source alone is not evidence of a successful end-to-end UAT.

## Eight assessed modules

| Owner / module | Delivered in separate PHP file | Known limitations |
|---|---|---|
| TAN UAM | Customer registration, email verification, login, timeout, one active session, profiles/password/email verification, controlled staff provisioning, categories, organiser assignments/revocation, anonymisation | Profile photo upload not implemented; Staff account category management UI partial; brute-force/login IP rate limiting not implemented; production-grade deletion/retention workflow not signed off |
| TAN Venue | Space/layout and maintenance block CRUD, safe physical capacity checks, parent/child venue conflict checks | Complex hall partition/exclusivity graph, recurring maintenance blocks and public venue calendar visualization are not complete |
| JAY Booking | Per-session holds and quota checks, simulated FPX/card/e-wallet, per-unit signed tickets, free/comp issuance, receipt/history, payment idempotency, official transfer, refund settlement | No specific numbered-seat selection engine; advanced late real-provider callback resolution not present; PDF is a text ticket summary rather than an embedded scannable QR image; no real payment provider |
| JAY Category | Ticket pricing/category CRUD, separate category quota, sale-start lock, archive | Granular pricing-specific quotas, real eligibility document/age verification and complex discounts by category are simplified |
| IVAN Support | Support/dispute case CRUD, replies/priorities, individual ticket refund requests/decision, independent simulated financial payouts and failed retry | No attachment or full case assignment/response SLA editor; post-partial-refund cancellation compensation is not automated; no real payout gateway |
| IVAN Event | Proposal CRUD, review/approve, Admin-controlled publication and lifecycle changes, organiser assignment, approved event translations | Staffing requests, revisions with complete prior-version history and delegated event operation permission matrix incomplete |
| YONG Session | Session CRUD, safe venue allocation, setup/teardown, capacity floor, reschedule history, ticket protection | Automated staff-conflict escalation on reschedule and detailed calendar UI need completion |
| YONG Scheduling | Shift CRUD, staff no-overlap checks including overnight, 30-minute gate grace and notices | Configurable grace control, whole-event assignment across all dates and published schedule approval workflow incomplete |

## Four proposed unique functions

- **Rewards:** Configurable demo rate, voucher/promo rules and redemptions, points ledger, same-session bundle component allocation are present. Remaining: per-user promo limit, category-scoped promo, point expiration and more complete redemption reversal/negative-point reconciliation.
- **Refund policy interface:** Separate immutable published versions, boundary validation, category/event/default precedence, snapshot at purchase and tier computation are present. Remaining: public policy translations, preview interface for multiple hypothetical dates/prices, and favourable exception versioning.
- **Personalised recommendations:** Dashboard opt-in, separate promo-email consent, preference/favourite/paid-booking signals and generic fallback are present. Remaining: full feedback explanation UI, interaction-history retention/consent provenance model and reporting.
- **MFA:** TOTP enrolment with recent password, AES-256-GCM secret protection, challenge, time tolerance, replay protection, recovery codes, optional requirement when enrolled are present. Remaining: Admin-approved lost-factor recovery, role-specific enforcement policy UI, persistent IP-based throttling.

## Shared services

- **QR:** First check-in unique constraint, current token/signature, refund/revocation checks, assigned shift gate, manual justification. No offline scanner mode, no camera SDK, no re-entry tracking (intentional).
- **Notifications:** In-app views, saved delivery state, development outbox, 1/5/15/60-minute retry routine and scheduled reminders. SMTP integration, editable templates and internationalised notification text are not implemented.
- **Dashboards:** Role-based data, event/user/booking counts, financial overview, customer bookings/rewards/recommendations, 30-second page refresh that does not extend inactivity. Advanced charts, filterable date ranges, full revenue decomposition and venue occupancy visualisation remain incomplete.
- **PDF:** Basic valid PDF text report and signed ticket-reference summary; not full visual branded e-ticket PDF containing QR graphics.
- **Localisation:** Navigation labels in English/Bahasa Melayu/Simplified Chinese/Traditional Chinese and organiser-provided approved event translations with fallback. **Most other forms and notifications are still English**; full four-language interface acceptance remains incomplete.

## Validation status at delivery

- `php -l` passed for all delivered PHP files with local PHP 8.4.
- `tests/unit.php` passed for monetary conversion, discount cents allocation, refund tier validation, TOTP reference vector and base32 round-trip.
- A sample `pdfOutput()` was generated and recognised as a valid single-page PDF by `pdfinfo`; rendered image was checked.
- Full database-based tests and live MySQL integration **could not be run in the build environment** because no MySQL server or PHP `pdo_mysql` driver was installed there. The XAMPP operator must run `tests/mysql_smoke.php` plus manual tests.
- Do not use this source for real payments, sensitive personal records, or an internet-facing service without further development, security review and approval.
