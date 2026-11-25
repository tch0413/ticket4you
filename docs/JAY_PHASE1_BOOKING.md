# Jay — Phase 1: Booking Management

Baseline: the uploaded `T4Y_Jay_Current.zip` archive. No schema migration, no new dependencies.
Apply only on your own branch after verifying `git status` and the latest team merge.

## Changes
- Customer and administrator booking history: search by reference or event title, filter by status, show session labels. Customer SQL always retains the owner filter.
- Checkout: show category quantities, unit price, fee and order totals; mark elapsed holds expired even before the scheduled jobs script updates the database.
- Update unpaid bookings: active undiscounted pending orders before any payment attempt, existing categories only. Quantity zero removes a category; at least one ticket must remain. Original unit prices, fee snapshots, policy IDs and hold deadline are retained. Category/session capacity and per-order maximum are checked while holding the session and order locks.
- Cancel unpaid bookings: keep the order and item records; mark cancelled or expired and release inventory. Payment history is retained.
- Checkout fingerprint detects stale forms after quantity changes. Paid orders and duplicate payment attempts retain existing idempotency behavior.
- Sales-closed and sold-out indicators in selection UI; payment methods reflect payable vs zero-total orders.
- Existing rewards, ticket issuance, transfer, refund settlement and teammates' other modules remain in place.

## Verification
Run in PowerShell from the repository root:
```
C:\xampp\php\php.exe -l modules/Booking.php
C:\xampp\php\php.exe tests/jay_booking_unit.php
C:\xampp\php\php.exe tests/customer_portal_smoke.php
C:\xampp\php\php.exe tests/mysql_smoke.php
```
The last command checks schema/default refund tiers, not end-to-end booking behavior. This environment has no PDO MySQL driver, so use XAMPP to test the following on a local test database.

## Manual acceptance cases (test account)
1. Reserve two available paid tickets, then edit quantity to one. Verify price and fees update and original expiry remains the same.
2. Increase an undiscounted reservation within inventory; then attempt beyond quota or the per-order limit. Invalid update must not change the order.
3. Open checkout in two tabs; edit in one tab, then attempt payment in the older tab. It must require checkout reload.
4. Simulate failed payment; retry before original expiry. Editing must not be offered after a payment attempt. Pay successfully and confirm tickets issued once.
5. Reserve and cancel before payment. Confirm cancelled record remains searchable and availability returns.
6. Allow another hold to expire. Check expired status and that payment is unavailable; run scripts/jobs.php to persist expired statuses.
7. Try a free category and verify only free checkout is offered. Try a paid order and verify free checkout is not offered.
8. Search by reference and event name; filter by paid, cancelled and expired. Verify another customer's orders are not listed or accessible.
9. Verify promo, bundle and points bookings still reserve/pay as before; their quantities cannot be edited in place. Cancel and rebook instead.
10. Two customers attempt to reserve the final ticket; verify only one succeeds. This requires actual MySQL testing.
11. Verify existing customer dashboard navigation and other teammates' pages.
12. Verify administrator sees history but cannot pay or cancel another customer's order from checkout.

## Known boundaries
No changes to Category Management or Refund Policy Interface in this phase. Discounted/attempted orders and adding new categories require cancel/rebook; paid bookings use Support's refund process. Hold expiry is enforced server-side, but a live ticking countdown is not added here. Existing QR/PDF, notification, language and payment infrastructure is retained; external payment gateway and live browser testing are not included.
