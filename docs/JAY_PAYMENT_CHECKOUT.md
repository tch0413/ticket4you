# Jay — Payment checkout enhancement

Baseline: T4Y_Latest_Integrated_System.zip. Payment remains a coursework simulation, not an integration with PayNet, banks, card networks or wallets. Provider names are illustrative options, not verified live availability. The sample QR encodes only demo text and cannot initiate payment. No actual account, card, PIN or wallet information is collected.

## Integration
- Back up the database and private app key. Review only the modified Booking.php and new checkout assets/helper, tests, migration and this document. Existing baseline SQL is unchanged.
- Coordinate migration number 0001 with the team before applying. If another migration already uses this number, coordinate a new increasing number before any application. Never rename an applied migration or edit its checksum.
- On an isolated local test database run `php scripts/db_status.php`, then `php scripts/migrate.php`. On a shared database only the designated migration owner should run the reviewed migration, after backup and approval. Do not run install/seed or the previous category migration. Deploy compatible code and schema together; older code may not understand the new DuitNow method. Existing payment records retain their values; provider is nullable for legacy records and complimentary issuance.
- Run `php tests/jay_payment_unit.php`, `php tests/jay_booking_unit.php`, `php tests/mysql_smoke.php`.
- Browser: reserve tickets, choose each payment method, check provider validation, confirm payment, verify history and QR tickets. Use Testing options to force failure, retry within original deadline, check expiry and repeated submission, and confirm free checkout. Verify mobile layout, keyboard navigation and legacy transaction history. The countdown is advisory; the server enforces the actual deadline. No JS: all provider fields are visible; server checks the chosen method and provider. The failure toggle and live countdown require JS.
- Do not replace the entire project, share local config, or commit private keys/data. If the team is sharing patches manually, compare files against current source before copying. If using Git, apply --check before apply and commit only the listed files.

## Verification limitations
PHP unit tests cover the selection rules, not live payment transactions. Live MySQL, migration execution and browser acceptance require local testing. Refund and ticket issuance use the existing booking transaction logic.
