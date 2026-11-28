# Jay — Event Category Management

Baseline: `T4Y_Event_Category_Current.zip`, which already includes the payment upgrade and `0001_booking_payment_providers.sql`. This change does not edit payment, ticket-category, refund, venue, user-account or session implementation. The Event and Customer Portal integrations are small changes to shared files; have their owners review them. The analytics dashboard now includes gross paid-order revenue and paid-ticket issuance grouped by event category (top eight, existing date/event filters). These are not net revenue or attendance metrics; historical chart labels follow the event's current category.

## Confirmed scope and decisions

- Dedicated management page: `index.php?page=event_category`. Admin and active staff with the existing `operate` permission (normally Event Operations Staff) may create, retrieve, update, deactivate and reactivate; other roles may not manage the catalog. No permanent delete route.
- One category per event/request. The existing `category_tag` columns remain the compatibility key; a stable numeric catalog ID is used in the dropdowns and management interface. This avoids changing the already applied baseline and keeps legacy booking/analytics/recommendation queries compatible. This is application-level referential integrity, not a new foreign-key constraint on the existing event tables.
- Unique name (database case/accent-insensitive collation), required description, icon from a fixed list, optional public category image. Images are stored in the shared database so teammates see the same image. JPEG/PNG/WebP only, maximum 512 KiB and 2048 x 2048; no SVG/HTML/external URLs. Uploads are checked for type and dimensions. Do not use personal images or sensitive material.
- Search by ID, name or description, active/inactive filters, associated event list/count, summary counts, and activity history. Names are limited to 80 UTF-8 bytes and descriptions to 500 bytes, matching existing input conventions. Names and statuses are validated server-side; edits are protected by the existing CSRF and account checks. A unique DB key also guards concurrent duplicate creation.
- Inactive categories remain attached to existing events. They cannot be newly assigned; a pending application using an inactive category must be revised before approval. Updating a category name updates matching legacy tags in events, requests and recommendation preferences within one transaction. If a legacy duplicate preference blocks that update, the transaction rolls back rather than discarding data. Inspect those legacy records with the DB owner before retrying.
- Seed nine suggested category names and import nonblank legacy event, application and preference tags. Existing event and preference rows are not rewritten by the seed. Case/whitespace variants may still exist in legacy data; the customer discovery page uses the canonical catalog name where available. Empty legacy tags remain unclassified until edited. The existing recommendation algorithm is unchanged; rename propagation preserves its existing tag references. Old applications/CLI seed scripts that write arbitrary tags do not enforce the catalog; coordinate deployment across all team members and avoid running demo scripts on shared data.
- The new catalog and image routes are available once the seed migration has been recorded as applied. Until then, the management page displays a notice and existing event forms retain legacy text input so code rollout does not disable the old workflow. The application does not execute migrations automatically.

## Patch application (your `jay-event-category` branch)

From PowerShell in the project root, verify `git status` and the intended branch. Fetch current team changes; review any changes that overlap with this patch, and merge the approved main branch if appropriate. Only apply with a clean working tree:

```powershell
git apply --check "$env:USERPROFILE\Downloads\T4Y_Jay_Event_Category.patch"
git apply "$env:USERPROFILE\Downloads\T4Y_Jay_Event_Category.patch"
git status
C:\xampp\php\php.exe tests/jay_event_category_unit.php
C:\xampp\php\php.exe tests/customer_navigation_smoke.php
C:\xampp\php\php.exe tests/customer_portal_smoke.php
C:\xampp\php\php.exe tests/admin_analytics_smoke.php
C:\xampp\php\php.exe tests/jay_booking_unit.php
C:\xampp\php\php.exe tests/jay_payment_unit.php
```

If compatibility fails, stop; do not force or overwrite team files. Do not include private keys/configuration or data exports in Git.

## Database owner only — coordinate before execution

Two new migration files follow the baseline's numbering: `0002_event_category_catalog.sql` and `0003_event_category_seed.sql`. Both contain one statement, following the project's migration runner. The original `schema.sql` and `0001_booking_payment_providers.sql` remain byte-for-byte unchanged. If a teammate has already reserved these numbers, coordinate new unique increasing numbers **before** applying; update test file paths and documentation accordingly. Do not rename or edit applied migrations. The readiness check uses the seed file suffix, not the number.

1. Confirm the actual server/database, migration permissions, journal and backup with the owner. `scripts/db_status.php` is read-only. Test against a disposable, isolated copy using the same DB engine/version as the shared TiDB deployment. Verify existing records and counts before and after. Never import, install, demo-seed or run destructive tests against the team's shared database.
2. The runner applies **all** pending migrations in order, including payment `0001` if it has not already been applied. Coordinate that migration too. Resolve checksum, number collisions and engine compatibility before running any migration. Test SQL, image storage and rename/assignment concurrency in the isolated copy first.
3. Follow team policy for review/merge, deploy the approved code, take a private backup, and schedule a coordinated maintenance window to prevent older clients writing unmanaged category tags during migration/rollout. Only the designated migration owner should use the migration account and execute `C:\xampp\php\php.exe scripts/migrate.php` on the shared DB. Repeat the command to confirm all previously applied migrations are skipped. On failure stop and inspect the journal and partial changes; do not blindly use `--retry-failed`. Runtime accounts should not need DDL permission.
4. All members pull the approved code and run `scripts/db_status.php`. Switch migration credentials back to runtime credentials. Do not share config/database.local.php, app.key, or real records.

## Manual acceptance tests (isolated DB first)

1. Sign in as Admin; open Management > Event categories. Check default and imported categories, active/inactive summary, search, filter, linked events and audit history.
2. Create a fictional category (name/description/icon/image); verify duplicate names including case differences are rejected. Upload JPG/PNG/WebP and reject oversize, non-image or SVG. Verify another workstation sees the uploaded image on an approved test environment. Edit the fields, replace/remove the image and verify activity history.
3. As authorised operations Staff, create/update a category. As entrance/booking/finance Staff without `operate`, Organiser, Customer and guest, verify the page and POST actions deny management. Confirm existing role navigation stays accessible.
4. Create a fictional event application with exactly one active category, revise it and approve using the existing Admin workflow. Verify the event editor and customer category filter show the chosen category and its icon/image. Edit a category name and verify existing events, applications and preferences reflect it without changing booking/ticket counts. Verify inactive categories are not offered for new requests, forged inactive/unknown IDs are rejected, an existing inactive assignment can be retained, and a pending inactive request cannot be approved until revised. Reactivate to make it selectable again.
5. Check the new event-category revenue and paid-ticket charts against known test totals; confirm ticket-category charts remain separate and booking/checkout functions still work. Test a rename while another session assigns the category; a conflicting transaction must fail without a partially changed record.
6. Record all browser and DB checks and review the shared-file diffs before merging. Unit tests alone do not establish migration, live database, upload, or browser compatibility.

## Verification performed in build environment

72 PHP files linted successfully. All 26 database-free scripts passed (including 44 new Event Category checks and updated navigation, customer portal and analytics smoke checks). MySQL/TiDB integration and live browser rendering were not executed: PDO MySQL is unavailable. No live database or private configuration was accessed or modified. Full database-level foreign-key normalization and complete new UI translations are not part of this incremental implementation.
