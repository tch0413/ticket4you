# Manual verification and evidence checklist (Tickets4You SRS v3.0)

Before taking screenshots, run `scripts/install.php`, `scripts/demo_seed.php`, and `tests/mysql_smoke.php` using XAMPP's PHP. Use fictional accounts and tickets only. Document input, expected result, actual result and screenshots per assessed CRUD process.

| Module | Create | Retrieve | Update | Delete / archive and negative test |
|---|---|---|---|---|
| UAM | Register unique email and Admin-create staff | Admin list, account profile | Change phone/password, reverify email, assign/revoke organiser | Deactivate; anonymise self, never delete paid transaction history. Test unverified customer cannot book. |
| VEN | Add demo hall, layout and maintenance block | View floor/size/facilities/blocks | Adjust layout/venue capacity | Archive referenced hall or layout; reject overlapping block/unsafe reduced capacity. |
| BKG | Reserve ticket and simulate payment | See order, attempts, individual QR references | Retry failed attempt within original hold; transfer acceptance rotates QR | Cancel unpaid hold; after a paid refund only that ticket becomes unusable. Test combined max of two categories. |
| CAT | Add session category | List quotas & sale price | Change price before sale | Archive purchased category; price edit after sales start fails. |
| SUP | Open case or request partial refund | Customer case history and Admin queue | Reply/priority/status, approve/refuse financial refund | Archive case with retained financial history; reject checked-in ordinary refund or second unresolved refund. |
| EVT | Submit proposal, Admin approve to Draft event | Public published event, private queue | Edit permitted application/event details, publish/cancel | Withdraw pending application, archive completed/cancelled event; publishing without category blocked. |
| SES | Add sequential session | Event/session calendar | Reschedule and preserve valid tickets | Archive referenced session; block overlap or capacity below holds. |
| SCH | Assign staff shift | Staff schedule | Move shift, validate overlap | Cancel shift; assert gate access immediately revoked outside remaining valid shifts. |

## Critical integrated flows

1. **Registration→email verification→booking:** sign up, run jobs, open private verification email, sign in, reserve Standard + Student across a single session, simulate successful payment, verify one booking reference and one QR per ticket. Attempt to exceed per-order max across categories: deny.
2. **Hold concurrency:** in two separate browser sessions reserve the last seat at the same time; at most one should succeed. Perform this on real MySQL with two processes (not a single-browser click). Do not assume passing syntax tests establishes transaction safety.
3. **Payment idempotency:** resubmit the same `attempt_key`; no additional successful payment or ticket. Retry a failed attempt with a new key before the original hold end; expiry must not be extended.
4. **Refund boundaries:** evaluate exactly 31d/16d/8d before session start; expect 100%/50%/25%. At 7d23h expect 0%. Confirm the purchased version remains fixed after a newer policy is published.
5. **Partial refund:** buy 3 paid tickets; submit one ticket for a refund; approve success; that QR is rejected while the other 2 remain valid. Repeat settlement must not double-refund.
6. **Payment refund failure:** approve an eligible case with failure outcome; decision should remain Approved with financial Needs Action and ticket still under review. Retry with success; ticket is then refunded.
7. **Cancellation:** Admin cancels a published event. Affected paid tickets get full actual paid ticket amount + their customer-paid fees in support cases, including checked-in tickets. Approval remains required for simulated payout.
8. **Formal transfer:** enable transfer before event session; invite recipient, run outbox, accept link, verify the old signed token fails and the recipient's restricted guest link shows a replacement QR.
9. **QR and shift scope:** assign Entrance Staff; test first scan within shift/grace, then duplicate scan (reject). Test incorrect staff category or cancelled shift (deny). Do not create re-entry events.
10. **MFA:** user enables factor with current password, enters app code, saves recovery codes, signs out and signs in again. Password alone must not unlock protected routes. Test code replay and recovery code reuse.
11. **Recommendation privacy:** no personalised history without opt-in. Opt in, select category interest, purchase event, check rankings; withdraw and verify generic fallback. Promotional email opt-in remains independent.
12. **Rewards and promo:** create limited promo, exhaust redemptions; test that two simultaneous uses of final redemption cannot both complete. Create a 2+2 bundle with per-order max 3 and confirm rejection; do not stack promo and points.
13. **Outbox failure:** set `T4Y_EMAIL_FORCE_FAIL=1`; run job repeatedly at respective scheduled times; business rows remain committed; retry schedule advances 1/5/15/60 minutes.
14. **Authorisation:** verify customer cannot access Admin pages; organiser cannot alter unassigned event; Finance Staff cannot access unrelated refunds; Entrance Staff cannot check in without live shift.

## Evidence required for coursework

Capture screenshots of each assessed Create, Retrieve, Update and Delete operation, validation failures, MySQL rows before and after, any relevant state transition, and app error text. Keep a test case sheet with ID, preconditions, test data, actions, expected result, actual result, and pass/fail. For full rubric coverage, prepare each module's activity diagrams, ERD/3NF/data dictionary, UI mockups, source walkthrough and UAT.
