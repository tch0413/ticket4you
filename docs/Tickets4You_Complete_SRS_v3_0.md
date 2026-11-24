**TICKETS4YOU  /  SICC** 

##### **SYSTEM SPECIFICATION** 

# **Tickets4You (T4Y)** 

_Complete Software Requirements Specification_ 

##### **SICC-only event, venue, ticket and operations platform** 

Version 3.0  |  19 November 2026  |  Integrated extra-feature and DFD baseline 

AMCS2194 Mini Project  •  Four-member team approved by tutor 

Prepared from requirements interviews and the final assessed-module allocation. 

|**Member**|**Assessed Module A**|**Assessed Module B**|
|---|---|---|
|TAN|User Account Management|Venue Management|
|JAY|Booking Management (tickets +<br>payments)|Category Management|
|IVAN|Customer Support & Dispute<br>Management|Event Management|
|YONG|Session Management|Staff Scheduling Management|



Document status: Full requirements baseline. Production-only values and unique-feature tutor approvals are separately flagged; they are not presented as decisions already made. 

Coursework constraint: Each of the eight assessed modules must demonstrate Create, Retrieve, Update and Delete with validation and integrated operation. Each member must also propose one tutor-approved unique function beyond CRUD. 

T4Y  •  Software Requirements Specification v2.0   |   Page 1 

**TICKETS4YOU  /  SICC** 

## **Document control** 

|**Field**|**Value**|
|---|---|
|Document code|T4Y-SRS-2026-02|
|Version|2.0 (replaces earlier eight-module SRS baseline)|
|Date|17 November 2026|
|Owners|TAN, JAY, IVAN and YONG|
|Client / venue scope|Sabah International Convention Centre (SICC), Sabah, Malaysia<br>only|
|Implementation|PHP 8 / MySQL-compatible database / Bootstrap 5 / JavaScript /<br>XAMPP|
|Assessed modules|Eight, exactly two per approved team member|
|Approval status|Requirements compiled; tutor confirmation of unique features<br>and production policy values outstanding|



## **Contents** 

1. Introduction, scope and terminology 

2. Stakeholders, actors and authority model 

3. Eight assessed modules: detailed functional requirements and CRUD 

4. Shared services and end-to-end business processes 

5. Status models, calculations and operational policies 

6. Information architecture and data requirements 

7. Non-functional and security requirements 

8. UI, localisation, interface and integration requirements 

9. Verification and acceptance-test catalogue 

10. Individual ownership, rubric compliance and implementation sequence 

11. Open decisions, assumptions and sign-off 

Appendix A. Requirements-to-process and implementation checklist 

12. Latest team/feature allocation 13. Detailed extra-feature specifications 

14. Integration contracts 15. ERD & UML impacts 

16. Whole-system DFD diagrams 

17. Security and acceptance tests 

18. Open decisions and sign-off 

Navigation note: Section headings use Word heading styles and appear in Word’s Navigation Pane. Page numbers are shown in the footer. 

T4Y  •  Software Requirements Specification v2.0   |   Page 2 

**TICKETS4YOU  /  SICC** 

## **1. Introduction, scope and terminology** 

### **1.1 Purpose and intended readers** 

This SRS specifies the required behaviour, constraints, data responsibilities and acceptance criteria for Tickets4You, a browser-based event ticketing system for events held at SICC. It is intended for the four developers, their academic tutor, test reviewers and hypothetical SICC system administrators. It is a specification for a student prototype, not a claim that SICC has deployed or endorsed the product. 

### **1.2 In scope** 

- Public browsing of SICC event listings and public event/venue information. 

- Verified customer registration, booking and payment simulation; event applications by eligible verified accounts. 

- SICC venue, layout and space capacity management; event and independently ticketed event-session management. 

- Ticket categories and pricing, checkout reservations, individual QR ticket issuance and controlled formal transfers. 

- Admin-controlled refunds and disputes, full event-cancellation refunds, account/booking support, staff support requests. 

- Online QR ticket verification, first-admission check-in, staff schedules, four role-specific dashboards, reporting and notifications. 

- Four supported interface locales: English, Bahasa Melayu, Simplified Chinese and Traditional Chinese. 

- Eight independently assessed modules plus shared platform services managed through explicit integration ownership. 

### **1.3 Out of scope and distinctions** 

- Events held at venues other than SICC are not bookable in T4Y. 

- Real transfer of money in the academic prototype. FPX, cards and supported e-wallets are future paymentprovider integrations; simulated transaction outcomes are sufficient for prototyping. 

- Automatic re-entry validation or recording. SICC may supervise physical re-entry manually without T4Y tracking. 

- A separate assessed Ticket Management module, Payment Management module or Dashboard Management module; ticket/payment processing belong to Booking, while dashboards/notifications are shared system capabilities. 

- Automatically generated or unreviewed translations of event descriptions; organiser-supplied approved original-language fallback applies. 

- Real customer records or real payment credentials for prototype demonstrations. 

### **1.4 Definitions** 

|**Term**|**Meaning**|
|---|---|
|Admin|SICC-authorised platform administrator with final operational<br>authority.|
|Organiser|Verified user with event-scoped organiser assignment; primary<br>organiser is the main contact.|
|Staff|Admin-created account with a staff category and event/shift<br>assignments.|



T4Y  •  Software Requirements Specification v2.0   |   Page 3 

**TICKETS4YOU  /  SICC** 

|**Term**|**Meaning**|
|---|---|
|Booking order|One purchaser’s order containing tickets for one event session.|
|Ticket|A separate entitlement with a unique ticket ID and QR token;<br>many tickets may share one booking reference.|
|Hold|Temporary checkout reservation of seats or quantities,<br>typically five minutes.|
|Net Paid Tickets Sold|Count of currently valid paid tickets after<br>refunds/voids/reversals, separate from free/complimentary<br>tickets.|
|Ticket availability|Inventory available for new allocation after valid issued<br>tickets, holds and non-saleable reserved capacity.|
|Formal transfer|T4Y-mediated change of ticket entitlement and replacement QR<br>token after verified acceptance.|
|Screenshot sharing|Informal sharing of the same QR representation; does not<br>change ownership or replace the token.|
|Physical re-entry|SICC-supervised return after an admission; T4Y does not verify<br>or record it.|
|RBAC|Role-based access control combined with event and staff-<br>category scopes.|
|3NF|Third Normal Form relational database normalisation.|
|SRS|Software Requirements Specification.|



### **1.5 Specification authority and requirement classifications** 

Confirmed (C) means agreed during the requirements interviews or the latest team allocation. Proposed (P) means a design recommendation requiring development or tutor agreement. Production policy (PP) means a value or practice that must be formally defined before real public deployment. In this document, requirement IDs within module tables represent confirmed functional intent unless expressly labelled otherwise. 

### **1.6 Source and academic constraints** 

The SRS is grounded primarily in the extended T4Y requirements interview and the latest tutor-approved team arrangement. The uploaded “AMCS2194 Mini Project Assignment Specification (September 2026)” establishes the assessed module count, CRUD expectations, one unique function per student, integration and report/testing evidence. The “AMCS2194 Mini Project Marking Rubrics (October 2026)” assesses problem understanding, system design, core implementation, integration, usability, testing and progress. These course documents describe assessment requirements rather than T4Y domain-specific business rules. 

The assignment specifies a submission deadline of Friday 11 December 2026, 11:59 PM, with presentations beginning in Week 7. Module names and the proposed unique features remain subject to the tutor’s approval. 

T4Y  •  Software Requirements Specification v2.0   |   Page 4 

**TICKETS4YOU  /  SICC** 

## **2. Stakeholders, actors and authority model** 

### **2.1 Actors and responsibilities** 

|**Actor**|**Main permissions and scope**|
|---|---|
|Visitor|View published public event details, search/filter and view<br>venue/applicant information made public; cannot<br>book/pay/apply without verification.|
|Customer|Self-register, verify email, browse, favourite, optionally opt<br>into personalisation, book/pay, view e-tickets, submit eligible<br>refunds and support cases; may apply to organise.|
|Organiser|Same underlying personal account plus approved event-scoped<br>event access; view and manage only assigned events, request<br>collaborators/staff, view authorised sales/attendance.|
|Admin|Manage roles, staff categories and accounts, venues,<br>applications, event publication, scheduling, ticket<br>configuration, refund approvals and reports.|
|Entrance Staff|Within assigned shifts and events, scan/verify tickets, record<br>first check-in, perform authorised lookup; no unrestricted<br>customer/finance access.|
|Booking Staff|Handle permitted booking support and verify booking records<br>within assigned event scope.|
|Finance Staff|Review and approve eligible refund cases when category<br>includes approval permission; use authorised financial<br>interfaces.|
|Event Operations Staff|Perform delegated session/venue/operational tasks, subject to<br>event and shift permissions.|
|Unregistered transfer recipient|Accept formal transfer via verified email link and retrieve own<br>ticket through restricted guest access; does not gain purchasing<br>or other account privileges.|



### **2.2 Access-control policy** 

- Everyone who self-registers initially receives Customer access. An approved event application may add Organiser access to the same account. 

- Admin creates Staff accounts, assigns a single category or configured category model, and schedules operational access; Organisers can request staff but cannot independently create their credentials. 

- Each organiser authenticates using personal credentials, may be assigned multiple events and can switch authorised event contexts without logging out. 

- Organisers sharing an event have the same delegated operational permissions; a primary organiser has additional main-contact/collaborator-request rights, not implicit Admin powers. 

- Staff may be scheduled for multiple events only if actual shift intervals do not overlap, including overnight intervals. 

- Permission and event-assignment changes are effective on the next protected operation, including in existing sessions. Hiding UI elements is insufficient without server enforcement. 

T4Y  •  Software Requirements Specification v2.0   |   Page 5 

**TICKETS4YOU  /  SICC** 

- Sensitive changes require recent password re-verification. Important administrative and financial actions are audited without storing passwords or token secrets. 

### **2.3 Core permission matrix** 

|**Capability**|**Admin**|**Organiser**|**Staff**|**Customer/Guest**|
|---|---|---|---|---|
|Create/manage<br>platform accounts|Yes|Request staff|No|Own account only / No|
|Configure staff<br>categories and<br>permissions|Yes|No|No|No|
|Apply for SICC<br>event/venue|Review|Submit/request|Permitted only|Verified customer|
|Final venue allocation<br>and publication|Yes|Request changes|No|No|
|Manage assigned event<br>operations|Yes|Delegated|Category-limited|No|
|Set per-order ticket<br>limit|Yes|If delegated|If delegated|No|
|Book and simulate<br>payment|Administrative support|Customer role|As authorised|Verified Customer / No|
|Approve refund<br>request|Yes|No unless separately<br>authorised Finance<br>category|Finance category only|No|
|Check in tickets|Override controls|If delegated and<br>scheduled|Entrance category in<br>shift|No|
|View financial<br>dashboards|All assigned to Admin|Only own assigned<br>events|Finance category<br>where allowed|Own payment history<br>only|
|Download formally<br>received ticket|Where authorised|Own tickets|Operational access|Customer / verified<br>guest own ticket|



Matrix legend: “If delegated” never grants unrestricted platform authority. All event-scoped actions must check event assignment, staff category and shift when the action is shift-gated. 

## **3. Assessed modules: detailed functional specifications** 

Each numbered module below is assessed as an independent CRUD module. A transaction record or ticket that must remain for audit may be cancelled, voided, deactivated or archived rather than hard-deleted; tutor acceptance of this Delete demonstration should be documented. 

### **3.1 User Account Management — TAN** 

Purpose: authenticate and manage Customers, Organisers, Staff and Admin identities, secure profile changes, eventscoped roles and account lifecycle. Public visitors need no account to browse, but verified Customer status is required for bookings and applications. 

T4Y  •  Software Requirements Specification v2.0   |   Page 6 

**TICKETS4YOU  /  SICC** 

#### **3.1.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Customer registration and Admin<br>creation of staff accounts; role/event<br>assignment creation.|Unique email; validated required fields;<br>password hash; verification token; staff<br>temporary credential.|
|Retrieve|Search/view authorised users, roles,<br>assigned events and staff account<br>profiles.|List/detail views respect permissions and<br>avoid unnecessary personal data.|
|Update|Update permitted profile fields, change<br>email/password, role/status, staff<br>category or event assignments.|Recent password verification for<br>sensitive account changes; audit and<br>immediate revocation.|
|Delete|Process verified<br>account-deletion/anonymisation or<br>deactivate staff/user accounts.|Preserve booking, payment and audit<br>records under SICC retention rules.|



#### **3.1.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|UAM-001|A visitor may browse published events<br>but must register and verify email before<br>booking, payment, or venue/event<br>application.|Try restricted action as unverified user.|
|UAM-002|New self-registrants are Customers; an<br>approved application adds Organiser<br>access to the existing account.|Grant second role without duplicate<br>identity.|
|UAM-003|Registration requires full name, email,<br>phone, password and confirmation;<br>gender is optional and date of birth is<br>requested only for relevant eligibility<br>checks.|Validate required data and conditional<br>fields.|
|UAM-004|Email verification uses expiring single-<br>use tokens; a failed or unverified<br>registration cannot access restricted<br>features.|Reuse and expire a verification token.|
|UAM-005|Provide Customer/Organiser email-<br>password login and Admin/Staff<br>controlled portals; Staff authenticates by<br>Staff ID and Admin-issued password.|Confirm correct portal identity and<br>denied access.|
|UAM-006|Admin creates Staff accounts after<br>authorised requests and forces a change<br>of securely issued temporary password<br>at first login.|First staff login redirects to password<br>change.|
|UAM-007|Admin maintains Staff categories, each<br>with set permissions; category updates<br>affect all members immediately.|Remove permission while staff is logged<br>in.|
|UAM-008|Staff event assignments and Organiser|Open revoked event URL without logout.|



T4Y  •  Software Requirements Specification v2.0   |   Page 7 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
||event assignments determine accessible<br>events; Admin can revoke access<br>immediately.||
|UAM-009|A personal Organiser account can<br>manage many assigned events; primary<br>organiser has contact and collaborator-<br>request privileges; all approved<br>collaborators share delegated event<br>permissions.|Switch between two assigned events;<br>deny third.|
|UAM-010|Customers may edit full name, phone,<br>profile image and password; changes<br>record actor, time and fields without<br>storing password values.|Update own account, not another<br>account.|
|UAM-011|Change Email is a separate process<br>requiring current-password verification<br>and verification of new address before<br>replacement; notify old email.|Old email remains active until verified.|
|UAM-012|Customer/Organiser self-service<br>password recovery uses expiring single-<br>use links; Staff recovery follows a<br>separately verified Admin assistance<br>process.|Reject expired reset links and<br>unauthenticated staff requests.|
|UAM-013|Inactivity logout is 60 minutes for<br>Admin/Staff and 30 minutes for<br>Customer/Organiser; no independently<br>configured absolute lifetime.|Expire idle session at role limit.|
|UAM-014|A new successful login revokes the<br>account’s previous active session;<br>dashboard background refresh does not<br>count as activity.|Verify single active session and idle<br>polling.|
|UAM-015|Lock down high-risk operations through<br>reauthentication, server-side CSRF<br>checks, secure cookies and role/event-<br>scope checks.|Attempt forged request and direct URL<br>access.|
|UAM-016|Verified deletion requests assess<br>outstanding duties and transactions;<br>deactivate access and delete/anonymise<br>eligible personal data while preserving<br>legally required financial records.|Delete account with completed booking.|
|UAM-017|Record privileged changes and<br>authentication events in audit history<br>without logging passwords, tokens or<br>secret values.|Inspect audit record and absence of<br>secrets.|
|UAM-018|The prototype permits password-only<br>Admin authentication but keeps<br>architecture adaptable for future 2FA.|Admin login works; 2FA is not falsely<br>claimed implemented.|



T4Y  •  Software Requirements Specification v2.0   |   Page 8 

**TICKETS4YOU  /  SICC** 

#### **3.1.3 Validation and permissions** 

- Reject duplicate email and missing/invalid phone, password-confirmation mismatch and unsafe profile upload. 

- Restrict uploaded image types and storage path; validate content server-side. 

- Restrict Staff/Organiser creation, category changes and event assignments to authorized accounts. 

- Use current session/server permissions on every write; do not rely on hidden navigation. 

**Integration contract:** Provides identity, event assignments, roles and staff categories to Event, Booking, Support, Session, Scheduling, QR and shared Dashboards. 

Current extra-feature allocation: TAN owns Customer Rewards as one integrated feature (loyalty, same-session bundle offers and promotional codes). Event-scoped RBAC remains a required core UAM security function, not TAN’s submitted extra feature. 

### **3.2 Venue Management — TAN** 

Purpose: maintain SICC venue/hall/partition inventory, physical capacities, facilities, reusable layouts, schedule blocking and conflict-free venue allocations. Venue records are not event records; Event Management requests approved spaces. 

#### **3.2.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Create venue, hall/partition, venue-<br>layout and maintenance-block records.|Space must belong to an SICC venue;<br>valid layout and positive capacity.|
|Retrieve|Search/list SICC venues, capacity,<br>facilities, layouts and public availability<br>indicators.|Public calendar is informational and<br>does not auto-reserve space.|
|Update|Edit venue facilities, capacities, layout<br>and availability/maintenance details.|Changes cannot violate existing<br>approved event allocations or physical<br>safety.|
|Delete|Remove unused draft<br>venue/layout/maintenance records or<br>deactivate venues with referenced<br>events.|Restrict hard delete where historical<br>event/booking relations exist.|



#### **3.2.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|VEN-001|System manages only SICC venues,<br>including halls and divisible<br>partitions/sections.|Reject non-SICC venue selection.|
|VEN-002|Admin can register the physical capacity<br>and facilities of each space, with multiple<br>saved layouts for seated, standing or<br>mixed events.|Switch between stored layouts.|
||Physical venue capacity is distinct from||
|VEN-003|the approved ticket allocation for any<br>particular event session.|Compare two values in event approval.|
|VEN-004|Maintain blocked periods for|Availability excludes blocked interval.|



T4Y  •  Software Requirements Specification v2.0   |   Page 9 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
||maintenance and other unavailable<br>dates, including recurring and manual<br>blocks where configured.||
|VEN-005|Availability checks consider setup, event<br>duration, teardown and configurable<br>buffers.|Reject overlapping setup or teardown<br>interval.|
|VEN-006|Reservation checks account for parent<br>halls and mutually exclusive<br>subdivisions; incompatible simultaneous<br>space allocations are blocked.|Prevent parent and overlapping partition<br>booking.|
|VEN-007|Venue applications specify preferences<br>only; an application must not reserve the<br>hall until Admin approves allocation.|Competing applications remain<br>unreserved.|
|VEN-008|Only Admin makes final venue<br>assignment decisions, even for<br>Organiser-managed events.|Organiser cannot force allocation.|
|VEN-009|Venue reservations are written<br>transactionally, preventing simultaneous<br>incompatible approvals.|Run parallel approval requests.|
|VEN-010|Reduce venue capacity only when safe<br>with confirmed events and ticket<br>commitments; preserve historical<br>approved values as needed.|Reject unsafe capacity reduction.|
|VEN-011|Public venue calendar reveals only<br>appropriate availability information, not<br>private applicant details.|Check unauthenticated calendar view.|
|VEN-012|Venue creation/update/archive actions<br>are audited and visible in authorised<br>administrative views.|Audit old/new facility values.|



#### **3.2.3 Validation and permissions** 

- Positive integer capacity; valid partitions and parent references. 

- Valid dates and intervals; setup/teardown cannot be negative. 

- Physical seating/layout constraints validated in addition to numerical capacity. 

- Prevent deletion of spaces with active sessions or historical dependencies. 

**Integration contract:** Event Management consumes venue approval/availability; Session Management consumes actual assigned venue, layouts and safe capacity. Booking consumes approved allocation, not raw hall capacity. 

Extra-feature ownership: TAN’s Customer Rewards is integrated with UAM, Category and Booking; Venue Management retains its existing full CRUD and safe capacity responsibilities. 

### **3.3 Booking Management (including Tickets and Payments) — JAY** 

Purpose: provide complete verified-customer order, inventory hold, ticket issuance, simulated payment and transaction history workflows. Lecturer explicitly classifies ticket and payment work under this assessed module. 

T4Y  •  Software Requirements Specification v2.0   |   Page 10 

**TICKETS4YOU  /  SICC** 

#### **3.3.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Create order/line items, checkout holds,<br>simulated payment attempts, individual<br>tickets, refund financial operations and<br>formal transfer records.|Verify account, capacity, unique seat and<br>price snapshot.|
|Retrieve|View orders by reference, ticket/QR<br>access, receipts, payment attempts and<br>authorised booking histories.|Customer sees own orders; Organiser<br>limited booking summaries; staff event<br>scoped.|
|Update|Update permitted<br>booking/payment/ticket states, retry<br>payment inside hold, process transfer<br>and verified financial adjustments.|Reject invalid state transitions and<br>duplicate payment completion.|
|Delete|Cancel/delete eligible unpaid drafts and<br>release unused holds; void tickets for<br>permitted refunds/cancellations.|Financial/issued ticket history is<br>retained, not hard-deleted.|



#### **3.3.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|BKG-001|Only registered and email-verified<br>Customers may create paid or public-free<br>booking orders.|Unverified customer cannot checkout.|
|BKG-002|A booking order contains tickets for<br>exactly one event session, even when<br>multiple categories and seats are<br>selected.|Reject cross-session cart mixing.|
|BKG-003|An Admin or explicitly delegated event<br>role configures the maximum number of<br>tickets per booking; totals combine all<br>categories.|Limit 3 rejects 2 VIP + 2 Standard.|
|BKG-004|No cumulative customer purchase limit<br>is enforced across separate bookings.|Two compliant sequential orders succeed<br>if stock remains.|
|BKG-005|Ticket reservation begins when the<br>quantity/seats are successfully reserved<br>for checkout; the default hold is 5<br>minutes and Admin may configure per-<br>event duration.|Hold starts on successful reservation.|
|BKG-006|All valid issued tickets, active holds,<br>complimentary reserved stock and<br>blocked stock reduce available-to-book<br>inventory without double-counting.|Inventory recomputation matches<br>quantity ledger.|
|BKG-007|Concurrent seat or stock requests use<br>database transactions and locking or<br>equivalent mechanisms; only one<br>overlapping seat claim can succeed.|Simultaneously reserve last seat.|



T4Y  •  Software Requirements Specification v2.0   |   Page 11 

**TICKETS4YOU  /  SICC** 

|**ID**<br>BKG-008|**System shall / requirement**<br>Customers can retry failed simulated<br>payment attempts before hold<br>expiration; retries do not extend the<br>original deadline.|**Verification / acceptance focus**<br>Retry retains same hold expiry.|
|---|---|---|
|BKG-009|Store order statuses Pending, Paid,<br>Cancelled, Failed and Expired separately<br>from payment attempt states and<br>identifiers.|One order tracks multiple payment<br>attempts.|
|BKG-010|The prototype simulates FPX online<br>banking, cards and supported e-wallets;<br>no real gateway charges or real card-<br>data storage.|Simulation produces payment outcome<br>and receipt.|
|BKG-011|Admin configures event-specific<br>customer fees; display ticket subtotal,<br>fees and grand total before confirmation.|Fee shown before payment.|
|BKG-012|Store monetary values in MYR using<br>decimal arithmetic and documented<br>half-up rounding to two decimal places<br>for final payable/refund amounts.|Compare precise deterministic totals.|
|BKG-013|On verified successful payment, confirm<br>order, consume holds and issue one<br>unique QR-token ticket for every item<br>unit; all tickets share one order<br>reference.|Four tickets yield four unique QR tokens.|
|BKG-014|Ticket access occurs in Customer<br>Dashboard with confirmation email<br>providing authenticated access; optional<br>PDF download remains supported.|Ticket available if email temporarily<br>fails.|
|BKG-015|A payment confirmed after an expired<br>hold is fulfilled only if the original<br>seats/allocation remain safely available;<br>otherwise open a refund or resolution<br>case.|Simulate late callback after seat<br>reallocated.|
|BKG-016|Free public tickets use zero-payment<br>checkout; complimentary tickets use<br>controlled invitations/allocations; both<br>count against session capacity but not<br>paid Tickets Sold or revenue.|Dashboard separates paid/free counts.|
|BKG-017|Tickets have individual<br>paid/free/comp/valid/refunded/voided/ch<br>ecked-in/transfer associations as<br>relevant; partial refund invalidates only<br>the selected ticket.|Refund 1 of 4; others remain usable.|
|BKG-018|Admin may enable formal transfers per<br>event, default allowed until session start<br>unless earlier cutoff is set; only valid<br>unused non-disputed/non-refunding<br>tickets transfer.|Reject transfer after check-in or cutoff.|



T4Y  •  Software Requirements Specification v2.0   |   Page 12 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|BKG-019|Official transfer requires recipient email<br>acceptance through expiring verified<br>link; registration is optional via<br>restricted guest ticket portal. Successful<br>acceptance replaces old QR token and<br>changes entitlement.|Old QR rejected; guest sees only own<br>ticket.|
|BKG-020|Informal screenshot sharing does not<br>transfer ownership or regenerate QR;<br>provide warning about<br>copied/invalid/already-used tickets and<br>potential refusal at entrance.|Check duplicate screenshot admission.|
|BKG-021|Original purchaser retains ordinary<br>refund request/financial recipient<br>responsibility after formal transfer;<br>recipient receives affected-event updates<br>if verified.|Refund goes to original payer.|
|BKG-022|Financial refund status is tracked<br>separately from approved support case<br>status; failed financial refunds remain<br>actionable for Finance Staff.|Approved but failed transaction is not<br>completed.|
|BKG-023|Completed refunds/reversals preserve<br>original payment entries and create<br>auditable adjustment transactions with<br>unique identifiers and idempotency<br>protection.|Repeated refund callback cannot double<br>refund.|
|BKG-024|Cancelled/reversed/voided paid tickets<br>are excluded from current net paid sold<br>and offered for resale only after<br>applicable refund completion,<br>invalidation and sales/eligibility checks.|Releasable quantity follows policy.|
|BKG-025|Transactions and booking reference<br>generation are auditable; duplicate<br>payment-provider callbacks or repeated<br>submissions must not double-issue<br>tickets.|Submit same idempotency key twice.|



#### **3.3.3 Validation and permissions** 

- Enforce verified identity, event session, category quota, seating rules, per-order maximum, opening/closing sales window and hold expiry. 

- Validate atomic state transitions and safe cross-module status synchronization. 

- Never store real card credentials in the prototype. 

- Do not physically delete paid bookings, completed payment attempts or issued ticket evidence. 

**Integration contract:** Consumes UAM verified identity, EVT/SES approved session and sales windows, CAT prices and eligibility; supplies paid tickets, order statuses and transactions to SUP, QR and shared Dashboards. 

Current extra-feature allocation: JAY owns the Refund Policy Interface (templates, event/category overrides, nonoverlapping tiers, version approval and fee handling). Checkout hold expiry remains a core Booking requirement, not the assigned extra feature. 

T4Y  •  Software Requirements Specification v2.0   |   Page 13 

**TICKETS4YOU  /  SICC** 

### **3.4 Category Management — JAY** 

Purpose: define flexible ticket categories, prices, quotas, discounts and eligibility requirements under approved events/sessions. Category definition is separate from the Booking transaction that consumes the category. 

#### **3.4.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Create category and session<br>pricing/quota definitions (Standard, VIP,<br>VVIP, Student, Early Bird, free,<br>complimentary).|Valid event/session association and<br>template configuration.|
|Retrieve|Browse active categories, prices,<br>conditions, allocations and per-session<br>availability indicators.|Public sees only published permitted<br>categories.|
|Update|Edit category name, terms, price, quotas,<br>eligibility and pricing type before the<br>ticket sales lock.|Reject price/category configuration<br>changes once sales begin.|
|Delete|Delete unused draft categories; archive<br>categories with sold/issued/held<br>references.|Existing orders retain frozen category<br>and price snapshots.|



#### **3.4.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|CAT-001|Support event-specific flexible category<br>names and ticket descriptions, including<br>paid, free and complimentary issuance<br>types.|Create category variants.|
|CAT-002|Support configurable standard prices,<br>eligible discounted types and optional<br>pricing-specific quotas.|Enforce subquota if enabled.|
|CAT-003|Allow no more than one applicable<br>discount per ticket; discount stacking is<br>prohibited.|Reject duplicate stacking.|
|CAT-004|Admin configures event-level eligibility<br>checks for Student, Child, Senior and<br>other restricted categories, including<br>whether identity/name matching is<br>needed at admission.|Category check is honoured at gate.|
|CAT-005|Date of birth/eligibility evidence is<br>collected only when required, and<br>unnecessary raw personal evidence is<br>not exposed to organisers or staff.|Unrestricted category has no DOB<br>collection.|
|CAT-006|Prices and the ticket-category<br>configuration are locked once ticket sales<br>begin; paid orders retain original<br>charged amounts.|Attempt late price/category edit.|



T4Y  •  Software Requirements Specification v2.0   |   Page 14 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|CAT-007|Authorised category management is<br>restricted to Admin or role explicitly<br>delegated for the assigned event.|Unauthorised organiser blocked.|
|CAT-008|Category quotas and optional reserved<br>allocations cannot exceed approved<br>session capacity when combined with<br>other commitments.|Reject quota above allocation.|
|CAT-009|Complimentary allocation reserves<br>capacity under Admin/authorised event<br>manager control and converts to issued<br>tickets without double subtraction.|Reserve 10 then issue 3; inventory<br>accurate.|
|CAT-010|Category configuration includes eligible<br>sale window/visibility if permitted by<br>event policy without overriding event<br>publication authority.|Unpublished event not bookable.|
|CAT-011|Archived categories preserve historical<br>reporting labels and price snapshots for<br>already issued tickets.|Old receipt remains unchanged.|
|CAT-012|Category usage and quota statistics are<br>available to authorised Admin/Organiser<br>dashboard users.|Event-scoped aggregation.|



#### **3.4.3 Validation and permissions** 

- Use nonnegative MYR decimal prices and quota integers. 

- Require category identifier uniqueness within configured event scope. 

- Validate discount eligibility before sale and preserve price snapshots on booking. 

- Reject deletion of categories with financial/ticket references. 

**Integration contract:** Consumes Event and Session authority/capacity; Booking reads frozen pricing/eligibility; QR uses identity eligibility rules; Dashboard reports category sales. 

Extra-feature ownership: JAY’s Refund Policy Interface is delivered through Category Management and consumed by IVAN’s Support refund workflow and JAY’s financial settlement logic. 

### **3.5 Customer Support & Dispute Management — IVAN** 

Purpose: provide case lifecycle for customer problems, booking/ticket enquiries, disputes, refund policy assessment and authorised refund decisions. Financial reversal transactions remain under JAY’s Booking Management. 

#### **3.5.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Create customer support cases, payment<br>disputes, ticket/booking enquiries and<br>individual-ticket refund requests.|Validate requester, category, ticket<br>ownership and eligible state.|
|Retrieve|Show own case history to customers;<br>queue, search, priorities and decisions to<br>authorised support/Finance/Admin.|Privacy and event-scope restrictions.|



T4Y  •  Software Requirements Specification v2.0   |   Page 15 

**TICKETS4YOU  /  SICC** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Update|Assign case owner/priority, add<br>response, verify supporting details,<br>calculate and approve/reject eligible<br>refund.|Role-scoped approval and status<br>transition audit.|
|Delete|Delete eligible draft or duplicate<br>requests; close/archive completed cases<br>while preserving financial and<br>evidentiary history.|Never erase legally required submitted<br>dispute/refund history.|



#### **3.5.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|SUP-001|Support cases include booking issues,<br>ticket access, payment issues, refunds,<br>disputes and general customer enquiries.|Route cases to appropriate category.|
|SUP-002|Customers can submit refund requests<br>for one or more individual tickets in a<br>booking when policy permits; unaffected<br>tickets remain valid.|Partial refund keeps other tickets.|
|SUP-003|Admin can configure refund policy by<br>event and ticket category using a default<br>policy template; policy versions are<br>preserved for purchased tickets.|Historic purchase reads frozen version.|
|SUP-004|Ordinary default refund tiers by exact<br>elapsed time before session start: >=31<br>days 100%, >=16 and <31 days 50%, >=8<br>and <16 days 25%, <8 days 0%.|Check boundary second at each cutoff.|
|SUP-005|Refund tier and estimated amount are<br>fixed at valid submission timestamp, not<br>Admin approval or payout date.|Review delay does not lower amount.|
|SUP-006|Ordinary checked-in tickets cannot be<br>refunded while event continues.|Reject request for admitted live event.|
|SUP-007|When SICC formally cancels an event, all<br>affected paid tickets receive full ticket-<br>price plus customer-paid fee refunds,<br>including those already checked in.|Cancellation exception overrides<br>0%/check-in.|
|SUP-008|After postponement, issued tickets<br>remain valid for revised session; Admin<br>configures a special refund window,<br>default 14 days from notification,<br>without silently reducing original rights.|Postponement generates eligible<br>window.|
|SUP-009|Fee refundability for ordinary requests<br>follows Admin event policy and<br>applicable rules, while full formal<br>cancellation covers all customer-paid<br>additional fees.|Compare ordinary and cancellation<br>cases.|



T4Y  •  Software Requirements Specification v2.0   |   Page 16 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|SUP-010|System computes eligibility and<br>estimated per-ticket refund; only Admin<br>or explicitly authorised Finance Staff<br>may approve.|Unprivileged staff cannot approve.|
|SUP-011|An approved support refund does not<br>imply financial payout completion;<br>payment processing failures remain<br>Approved plus Failed/Requires Action.|Show independent statuses.|
|SUP-012|Refund submission under review<br>temporarily restricts admission/transfer<br>to avoid simultaneous consumption;<br>rejected requests release restriction<br>when appropriate.|Reject QR admission of held-for-refund<br>ticket.|
|SUP-013|Financial payout attempts are requested<br>through Booking/Payment service using<br>transaction idempotency and audit<br>identifiers.|Repeated instruction does not duplicate<br>payout.|
|SUP-014|Only after completion and ticket<br>invalidation may refunded inventory<br>return to sale when sales remain open<br>and policy permits.|No premature resale.|
|SUP-015|Case priority supports Low, Normal,<br>High and Urgent, with authorised<br>assignment and Admin-configured<br>response targets.|Priority change tracked.|
|SUP-016|Customer and staff support requests<br>maintain linked replies, status history<br>and notification updates.|Case timeline and audit view.|
|SUP-017|Staff Contact Admin portal supports<br>recovery, activation, permissions, shift<br>enquiries and event operations;<br>unauthenticated submissions must be<br>separately identity-verified.|Staff ID/email alone cannot reset<br>password.|
|SUP-018|Important refund decisions and case<br>updates create mandatory notifications<br>through shared delivery queue.|Email failure preserves case state.|



#### **3.5.3 Validation and permissions** 

- Validate valid customer-ticket association, no duplicate unresolved refund request for same ticket, and exact timestamps. 

- Restrict approval to Admin/authorised Finance category; log decision and reason. 

- Require approval/reversal transactions to preserve original paid amounts and fee breakdown. 

- Delete only removable drafts/duplicates; archive completed disputes with history. 

**Integration contract:** Reads Booking paid amounts/ticket validity, Session start times, Event cancellation/postponement status and Category policy; invokes Booking financial refund service and ticket invalidation; publishes to Dashboards and Notifications. 

T4Y  •  Software Requirements Specification v2.0   |   Page 17 

**TICKETS4YOU  /  SICC** 

Current extra-feature allocation: IVAN owns Personalised Event Recommendation, integrated with the Event module and Customer Dashboard. Tier-based refund calculation remains a required Support business function and consumes JAY’s policy version. 

### **3.6 Event Management — IVAN** 

Purpose: administer SICC event applications, review/approval, authorised organisers, event metadata, publication, lifecycle changes and operational notifications. Venue Management owns space resources; Session Management owns dated ticketed occurrences. 

#### **3.6.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Submit event/venue proposal and create<br>Admin-approved draft event from a<br>proposal.|Verified applicant; approved venue and<br>valid event fields.|
|Retrieve|Search public published events and<br>private applications; view assigned event<br>details and review queue.|Restrict private applicant documents.|
|Update|Request revision, approve/reject, change<br>delegated management, edit authorised<br>details, publish/postpone/cancel/archive.|State-specific and Admin final control.|
|Delete|Delete withdrawn unused draft<br>applications or unpublished drafts<br>where permitted; archive<br>public/completed/cancelled events.|Retain sold booking history and event<br>audit.|



#### **3.6.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|EVT-001|Verified Customers may submit multiple<br>event applications for SICC, with<br>mandatory event, contact, attendance,<br>proposed dates, preferred venue and<br>staffing information. Remarks/uploads<br>are optional as configured.|Missing mandatory data rejected.|
|EVT-002|Applications support Pending Review,<br>Under Review, Requires Revision,<br>Approved, Rejected and Withdrawn<br>states with revision history.|Valid transitions and preserved<br>revisions.|
|EVT-003|Applicant may edit Pending applications;<br>Under Review requires Admin-requested<br>revision before edits; withdrawal<br>permissions are state controlled.|Disallow unauthorised edit.|
|EVT-004|A venue preference or calendar view<br>does not reserve venue capacity during<br>application.|Two applications can prefer same date.|
|EVT-005|Admin checks actual hall/partition,<br>layout, setup/teardown, staff and|Reject venue conflict.|



T4Y  •  Software Requirements Specification v2.0   |   Page 18 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
||equipment constraints before final<br>venue approval.||
|EVT-006|Admin approval creates an event in<br>Draft state and assigns Organiser access<br>using the applicant’s verified email;<br>publication is separate.|Approved proposal not instantly public.|
|EVT-007|An account may be assigned to several<br>events, and events may have multiple<br>approved Organisers with a primary<br>contact.|Multiple assignment switch.|
|EVT-008|Admin may select SICC-managed, shared-<br>managed or organiser-managed<br>operations per event; delegate only<br>specified functions.|Different delegation per event.|
|EVT-009|Admin retains final authority over<br>venues, permissions and publishing or<br>unpublishing the event.|Organiser cannot publish directly.|
|EVT-010|Event lifecycle supports Draft, Published,<br>Ongoing, Completed, Postponed,<br>Cancelled and Archived; ticket sales<br>Open/Closed is a separate dimension.|Lifecycle and sales status separate.|
|EVT-011|Admin configures event sales windows<br>and public pre-sale visibility; automated<br>jobs may open/close sales within those<br>windows.|No checkout outside window.|
|EVT-012|Publication prerequisites include<br>approved venue, dated sessions,<br>capacity, authorised organiser, valid<br>ticket categories/pricing and sale<br>windows.|Reject incomplete publication.|
|EVT-013|An event may have multiple sessions on<br>consecutive dates or multiple same-day<br>sessions; each has separately approved<br>ticket capacity.|Two sequential sessions valid.|
|EVT-014|Organiser-requested date/venue changes<br>require Admin approval and schedule<br>revalidation.|Reject conflicting edit.|
|EVT-015|Postponement preserves affected ticket<br>entitlements for revised session, triggers<br>notifications and opens Admin-<br>configured special refund rights.|Preserve ticket validity.|
|EVT-016|Formal SICC event cancellation<br>invalidates affected ticket admission and<br>starts full-refund cases including<br>customer-paid fees, even for checked-in<br>tickets.|Full cancellation case created.|



T4Y  •  Software Requirements Specification v2.0   |   Page 19 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|EVT-017|Critical event changes notify affected<br>registered purchasers, verified<br>recipients, assigned organisers and staff<br>as known.|Affected-scope recipients only.|
|EVT-018|Event popularity and recommendation<br>eligibility can use ticket sales velocity<br>and approved event categories;<br>personalised history-based<br>recommendations require customer opt-<br>in.|No personalisation without opt-in.|
|EVT-019|Maintain event descriptions per<br>language with organiser-supplied<br>approved translations and original-<br>language fallback.|Missing Chinese translation falls back.|



#### **3.6.3 Validation and permissions** 

- Event title, description, category, requirements, relevant schedules and contact must be validated. 

- No external venue; final venue selection requires Admin. 

- Validate lifecycle transitions and publish dependencies. 

- Block dangerous deletion once event has orders/tickets. 

**Integration contract:** Consumes UAM organiser assignments, Venue approval/availability, Session schedules and Category readiness; broadcasts lifecycle changes to Booking, Scheduling, Support, Notifications and Dashboard. 

Extra-feature ownership: IVAN’s Personalised Event Recommendation is part of Event Management and requires explicit customer opt-in for history-based signals. 

### **3.7 Session Management — YONG** 

Purpose: administer ticketed sessions associated with an overall event, including independent start/end timestamps, approved capacity and event/venue calendar consistency. The Session module does not create customer bookings. 

#### **3.7.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Add one or more dated event sessions<br>under a valid event and selected SICC<br>venue/layout.|Validate interval and approved venue<br>allocation.|
|Retrieve|Show session calendars, dates/times,<br>capacity, linked ticket categories and<br>availability.|Public only published sessions; private<br>internal data scoped.|
|Update|Reschedule or modify permitted session<br>details and approved allocation under<br>Admin authority.|Protect ticket holders and preserve<br>linked historical state.|
|Delete|Remove unused draft sessions; cancel or<br>archive committed sessions without<br>erasing tickets/history.|No hard delete when paid tickets or<br>shifts exist.|



T4Y  •  Software Requirements Specification v2.0   |   Page 20 

**TICKETS4YOU  /  SICC** 

#### **3.7.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|SES-001|A session has independent date,<br>scheduled start/end time,<br>venue/space/layout, attendance and<br>approved ticket capacity.|Session view distinguishes from parent<br>event.|
|SES-002|One event supports multiple consecutive<br>days and multiple sessions on one day;<br>recurring weekly pattern support is not<br>required by default.|Create multiple dates and sessions.|
|SES-003|Each session uses independently<br>approved allocation; sum of tickets sold<br>across non-overlapping sessions may<br>exceed venue capacity at one time.|Two sessions each sell hall capacity.|
|SES-004|Session capacity must not exceed safe<br>venue physical capacity for that session’s<br>approved layout/space.|Reject excessive allocation.|
|SES-005|Session date/time and setup/teardown<br>buffers are checked against venue<br>reservations, maintenance and hall<br>partitions.|Reject overlapping session venue use.|
|SES-006|Sessions preserve event linkage,<br>approved ticket categories, sale window<br>references and timezone (`Asia/Kuching`<br>for display).|Reject orphan session.|
|SES-007|Admin alone approves material session<br>schedule changes where required;<br>authorised Organisers may propose<br>changes.|Delegated user cannot bypass approval.|
|SES-008|Rescheduling must preserve existing<br>tickets and originally agreed refund<br>protections, and invoke<br>postponement/notification processes<br>when relevant.|Existing ticket still valid after<br>postponement.|
|SES-009|Approved session capacity cannot be<br>reduced below committed valid issued<br>tickets or active holds without separate<br>controlled resolution.|Unsafe reduction blocked.|
|SES-010|Session cancellation triggers affected-<br>booking/ticket and financial workflows;<br>physical deletion is prohibited when<br>historical records depend on the session.|Archive session with paid orders.|
|SES-011|Session changes trigger review of<br>affected staff shifts, overtime/grace<br>conflicts and assigned entrance<br>permissions.|Overlapping shift flagged.|
|SES-012|Session-level check-in information and<br>occupancy are accessible only to|Cross-event access denied.|



T4Y  •  Software Requirements Specification v2.0   |   Page 21 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
||authorised staff and dashboard views.||
||Public event page shows correct session||
|SES-013|options, event time and availability with<br>clear timezone labels.|Correct timezone displayed.|



#### **3.7.3 Validation and permissions** 

- Start datetime must precede end datetime; only SICC venue resources allowed. 

- Validate session conflicts including setup/teardown and partition compatibility. 

- Capacity integer >=0, in accordance with approved allocation and committed quantities. 

- Delete only unused drafts, else archive/cancel safely. 

**Integration contract:** Consumes Event and Venue; publishes active session details to Booking, Category, Scheduling, QR and Dashboards. 

Current extra-feature allocation: YONG owns Multi-Factor Authentication (MFA), delivered as a shared authentication extension integrated with TAN’s UAM. Shift conflict detection remains a core scheduling validation. 

### **3.8 Staff Scheduling Management — YONG** 

Purpose: assign authorised staff to events, sessions and operational shifts, prevent overlap, control shift-gated capabilities and notify staff of assignment changes. Staff identity/permissions are owned by TAN. 

#### **3.8.1 Assessed CRUD and evidence** 

|**Operation**|**In-scope business operation**|**Rules / deletion safeguards**|
|---|---|---|
|Create|Create whole-event or custom shift<br>assignment linked to staff account,<br>event/session and function.|Staff active, category eligible, no<br>overlapping shifts.|
|Retrieve|Display own shifts, Admin/team schedule<br>calendar, event staffing coverage and<br>authorised assignment history.|Only authorised staff and event-<br>management personnel.|
|Update|Change start/end times, event, session,<br>category-relevant assignment details and<br>shift status.|Re-check conflicts after edits.|
|Delete|Remove draft unaccepted assignment;<br>cancel confirmed shift while preserving<br>audit and sending updates.|No silent deletion of staffed attendance<br>history.|



#### **3.8.2 Functional requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|SCH-001|Only Admin, or explicitly authorised<br>delegated roles where permitted, may<br>create and manage staff schedules.|Unauthorised organiser denied.|
|SCH-002|Staff account must exist and be active;<br>personnel permissions are read from<br>current UAM staff category.|Disabled staff rejected.|



T4Y  •  Software Requirements Specification v2.0   |   Page 22 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|SCH-003|Support whole-event assignments and<br>custom date/time shifts with explicit<br>event/session references.|Create both shift types.|
|SCH-004|Prevent staff from being assigned to two<br>overlapping shifts, including events on<br>different dates where a shift crosses<br>midnight.|Conflict works across midnight.|
|SCH-005|Staff may be scheduled across different<br>events if shifts do not overlap.|Nonoverlap succeeds.|
|SCH-006|Admin-configured operational grace<br>periods default to 30 minutes before and<br>after each shift.|Permissions active during grace period.|
|SCH-007|Viewing shift history or future schedules<br>need not be shift-limited; shift-specific<br>entrance/operations actions are time-<br>gated.|Outside shift read allowed, scan denied.|
|SCH-008|Staff schedule modifications,<br>cancellations and new assignments<br>create email and in-app notifications.|Recipient notified after reassignment.|
|SCH-009|After event postponement/rescheduling,<br>affected shifts are revalidated and<br>conflicts flagged for review; do not<br>silently overlap assignments.|Reschedule produces review alert.|
|SCH-010|Shift coverage, assigned event and role<br>information are displayed on Staff<br>Dashboard and to Admin.|Proper event-scoped roster.|
|SCH-011|Shift changes are audited with actor,<br>old/new times, affected staff and session.|Audit entry present.|
|SCH-012|Deleting/cancelling a shift must revoke<br>corresponding shift-gated operational<br>permission promptly.|Staff QR scan denied after cancel.|
|SCH-013|Scheduling records remain separate<br>from ticket admissions and do not create<br>re-entry logs.|No exit/re-entry analytics.|



#### **3.8.3 Validation and permissions** 

- Start/end datetimes required and valid; overnight assignments use true datetime comparisons. 

- Validate event assignment, staff active account and required category permission. 

- Check overlap transactionally under concurrent scheduling requests. 

- Cancelled shifts are retained for audit as required. 

**Integration contract:** Consumes staff identity/category from UAM and session data from YONG’s Session module; supplies shift gates to QR check-in and Staff Dashboard; notifications through shared queue. 

Extra-feature ownership: YONG implements MFA within the authentication extension; Staff Scheduling still performs normal shift overlap detection as a required operational rule. 

T4Y  •  Software Requirements Specification v2.0   |   Page 23 

**TICKETS4YOU  /  SICC** 

## **4. Shared services and end-to-end business processes** 

### **4.1 Shared feature ownership and operating boundaries** 

|**Shared capability**|**Primary code owner (proposed)**|**Interfaces / responsibilities**|
|---|---|---|
|Role-specific dashboards and navigation|Joint; TAN coordinates authentication<br>shell|All module maintainers supply<br>permission-aware read models; no ninth<br>assessed module.|
|QR code admission validation and first<br>check-in|YONG coordinates UI; JAY ticket-validity<br>service; TAN authorisation|Online live validation, first admission,<br>duplicate rejection; no re-entry activity<br>recorded.|
|Notification queue and templates|Joint; TAN platform service or agreed<br>owner|Email and in-app notifications, template<br>versions, retries, monitoring; each<br>module emits domain events.|
|Staff Contact Admin portal|IVAN case screens; TAN identity checks|Support cases created from Staff Login;<br>identity verification before credential<br>reset.|
|Payment simulation / refund financial<br>execution|JAY under Booking Management|Never treat Support approval as<br>completed payout.|
|Refund decision and customer dispute<br>workflow|IVAN under Customer Support|Eligibility and decisions; JAY performs<br>financial reversal; JAY ticket voids.|
|Venue availability contract|TAN|IVAN/YONG must not bypass<br>transactional venue conflict check.|
|Translations and shared UI|Joint; module owner supplies strings|Admin approval of system strings;<br>organiser original-language fallback for<br>event descriptions.|
|Audit/event log, scheduled jobs and<br>backups|Joint platform infrastructure|No unrestricted access to sensitive<br>payloads; idempotent handlers.|



### **4.2 Notification and communication requirements** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|NTF-001|Support email and in-app notification<br>center for registered user dashboards;<br>mandatory operational messages cannot<br>be disabled.|Mandatory messages appear in saved<br>history.|
|NTF-002|A paid booking confirmation is queued<br>only when verified payment has resulted<br>in valid issued e-tickets; payment-<br>pending notices are distinct.|No false booking-confirmed email.|
|NTF-003|Notify event applicants of important<br>status changes, revision requests,<br>approval/rejection and withdrawal.|Application status change creates<br>notification.|
|NTF-004|Notify relevant organisers of invitations,|Only assigned organiser recipients.|



T4Y  •  Software Requirements Specification v2.0   |   Page 24 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
||assignments, important event changes<br>and decisions.||
|NTF-005|Notify staff of account access and<br>created/changed/cancelled shifts by<br>email and in-app message.|Shift edit queues notice.|
|NTF-006|Notify known affected purchasers and<br>verified formal ticket recipients for<br>cancellation, postponement or significant<br>venue/session changes.|No attempt to discover anonymous<br>screenshot sharers.|
|NTF-007|Support Admin-configurable reminders<br>per event, default 24 hours before the<br>booked session.|Adjust reminder offset and test<br>scheduler.|
|NTF-008|Provide notification history, unread<br>count, mark-as-read and link to relevant<br>authorised record.|Direct notification link respects RBAC.|
|NTF-009|Allow opt-out of optional<br>promotional/recommendation emails;<br>promotional recommendation email<br>requires explicit opt-in and personalised<br>recommendations require separate<br>history-use opt-in.|Disable promotions without suppressing<br>cancellation.|
|NTF-010|Support favourite-event announcements<br>as optional messaging and customer<br>saved-event history.|Favourite event announcement respects<br>opt-out.|
|NTF-011|A failed email never changes<br>booking/payment/refund/ticket state;<br>record delivery attempt and keep in-app<br>or dashboard status available.|Simulate email transport failure.|
|NTF-012|For temporary failure, retry after 1, 5, 15<br>and 60 minutes from successive failures<br>and alert Admin if all attempts fail;<br>distinguish permanent address rejection.|Verify queue retry timestamps and<br>escalation.|
|NTF-013|Admin may inspect delivery status,<br>recipient, attempt count, last/next<br>attempt and safe failure reason;<br>manually retry appropriate failed<br>records.|No password/QR secret in logs.|
|NTF-014|Admin may manage/preview approved<br>templates and predefined placeholders<br>without editing PHP source, with<br>translation handling where supported.|Template preview uses escaped fields.|
|NTF-015|Use database-backed notification records<br>and development email outbox with<br>optional SMTP integration; delivery<br>retries must not duplicate domain<br>transactions.|Queued email processed independently<br>of booking commit.|



T4Y  •  Software Requirements Specification v2.0   |   Page 25 

**TICKETS4YOU  /  SICC** 

### **4.3 QR Validation & Check-in shared specification** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|QR-001|Every ticket is validated live by unique<br>QR token against current event session,<br>validity, transfer/refund status and<br>identity restrictions.|Refunded or old transferred QR rejected.|
|QR-002|Only authorised and correctly scheduled<br>entrance personnel may perform scan or<br>manual ticket admission, including<br>configured 30-minute grace periods.|Outside grace period denied.|
|QR-003|Record exactly one first successful<br>admission per individual ticket as the<br>canonical attendance record.|Concurrent scans count once.|
|QR-004|Previously scanned tickets are rejected<br>as repeat first-admission attempts;<br>unique attendance never increases on<br>repeated presentation.|Scan same QR twice; count one.|
|QR-005|If SICC allows supervised physical re-<br>entry, supervision takes place outside<br>T4Y: no digital exit verification, re-entry<br>admission, re-entry events, or re-entry<br>analytics are created.|Check database lacks re-entry events.|
|QR-006|Staff may use a permitted secure ticket-<br>reference lookup if scanning fails;<br>manual admission performs the same<br>validity and idempotency checks and<br>retains a justified audit record.|Manual entry cannot bypass invalidity.|
|QR-007|Normal QR validation requires current<br>online records; connectivity outage<br>triggers approved supervised<br>contingency and must not blindly trust a<br>saved screenshot.|Simulate connection outage.|
|QR-008|For named tickets or restricted<br>categories, follow Admin-configured<br>proof and name-matching requirements<br>while minimising stored identity data.|Reject mismatched named ticket.|
|QR-009|Unauthorised and repeated scan<br>attempts may be recorded for limited<br>security audit purposes, but must not be<br>classified or reported as re-entry activity.|No re-entry report/table.|
|QR-010|Ticket screenshots alone do not evidence<br>legitimate ownership; warn of<br>duplicate/invalid screenshot risk and<br>prefer official transfer when available.|UI warning visible near sharing.|



T4Y  •  Software Requirements Specification v2.0   |   Page 26 

**TICKETS4YOU  /  SICC** 

### **4.4 Shared dashboards and reporting** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|DASH-001|Provide four role-specific dashboards:<br>Admin, Organiser, Staff and Customer,<br>using shared navigation and<br>authorisation.|Direct restricted URL blocked.|
|DASH-002|Admin overview includes<br>total/upcoming/ongoing/completed<br>events, applications, booking orders,<br>ticket counts, revenue/refunds, users,<br>organisers, staff, occupancy and unique<br>attendance.|KPIs match raw records.|
|DASH-003|Admin charts cover ticket sales over<br>time, monthly revenue, revenue by<br>event, popular events, booking statuses,<br>category sales and attendance versus<br>sold tickets.|Chart values derived from audited<br>metrics.|
|DASH-004|Organiser dashboard displays a<br>combined assigned-event overview and<br>selectable per-event view; event<br>comparisons are limited to assigned<br>events.|No other organiser data.|
|DASH-005|Organiser booking lists expose booking<br>reference, category, quantity and status<br>only unless further customer data is<br>justified by delegated permission.|No unnecessary contact data.|
|DASH-006|Staff dashboard displays role/category-<br>specific operations, shifts and event<br>selections; schedule viewing and action<br>permission obey separate scopes.|Future shift visible, scan restricted.|
|DASH-007|Customer dashboard shows<br>upcoming/past bookings, e-tickets/QR,<br>receipts/payment history, refund<br>eligibility/progress, notifications, profile,<br>applications, favourites and opted-in<br>recommendations.|Customer sees only own orders.|
|DASH-008|Dashboard default reporting period is<br>the current calendar month, with filters<br>for event, session, venue, status, category<br>and appropriate owner.|Filters recompute source metrics.|
|DASH-009|Dashboard auto-refreshes selected data<br>every 30 seconds without extending idle<br>login time; ticket inventory is validated<br>transactionally rather than relying on<br>refresh cache.|Idle dashboard eventually logs out.|
|DASH-010|Show historical paid tickets and current<br>net valid Paid Tickets Sold as distinct<br>metrics; free/complimentary issued<br>count separately.|Refund changes net, not historic sale<br>count.|



T4Y  •  Software Requirements Specification v2.0   |   Page 27 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|DASH-011|Separate booking order counts by<br>Pending, Paid, Cancelled, Failed and<br>Expired; do not confuse orders with<br>ticket quantities.|Two-ticket order counts as one booking.|
|DASH-012|Show gross paid ticket-price revenue,<br>separate fees, completed refund<br>amounts, confirmed financial reversals<br>and reconciled net ticket revenue<br>without double-counting.|Adjustments tie back to original<br>payment.|
|DASH-013|Report sales by payment completion<br>date, completed refunds by refund<br>completion date, and event scheduling<br>by session date.|Boundary crossing respects specified<br>date type.|
|DASH-014|Distinguish event ticket allocation<br>utilisation, physical venue capacity,<br>actual check-in attendance and<br>unsold/held capacity.|Four distinct metrics displayed.|
|DASH-015|Allow permission-scoped formatted PDF<br>report exports; Excel/CSV was not finally<br>selected by the user and is NOT required<br>under this baseline without further<br>approval.|PDF export follows same row-level<br>permissions.|
|DASH-016|Calculate “most popular” from ticket<br>sales velocity (approved allocation<br>percentage sold per day since<br>publication) with documented tie and<br>short-age handling.|Same metric yields deterministic<br>ranking.|
|DASH-017|Admin dashboard includes notification<br>failure, backup, background job and<br>critical application monitoring without<br>exposing sensitive log material.|Critical alert appears to Admin.|
|DASH-018|Display event recommendation cards in<br>Customer Dashboard only if history-<br>based personalisation opt-in is granted;<br>generic popularity is allowed without<br>individual profiling.|Disable consent and verify<br>recommendation source.|



### **4.5 Shared staff support and administrative operations** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|OPS-001|Provide a Staff Contact Admin entry<br>point on Staff Login for recovery, access,<br>shift and operational requests.|Unauthenticated portal reachable.|
|OPS-002|A recovery request records Staff ID,<br>registered email and issue description,<br>but identity is separately verified before<br>credentials change.|Spoofed Staff ID cannot reset account.|



T4Y  •  Software Requirements Specification v2.0   |   Page 28 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|OPS-003|Admin support queue supports status,<br>priority, assignment, response history<br>and appropriate escalation.|Respond with case timeline.|
|OPS-004|Critical failures trigger Admin alerts<br>immediately; lower-priority failures<br>appear on operational monitoring<br>dashboard.|Configure critical vs routine job failure.|
|OPS-005|Scheduled jobs handle email retries,<br>event reminders, checkout cleanup, sale<br>windows and backups; real-time<br>transactional checks protect checkout<br>independent of job execution.|Hold still unavailable after expiry<br>without job.|
|OPS-006|Admin settings support input validation,<br>confirmation for sensitive changes and<br>full authorised change audit.|Reject invalid configuration value.|



## **5. Critical end-to-end workflows and business rules** 

### **5.1 Registration and email verification** 

1. Visitor opens registration and submits full name, unique email, phone, password and confirmation. 

2. System validates input, hashes password, creates unverified Customer account and sends expiring emailverification link. 

3. Visitor uses a valid single-use link; system marks account verified and allows Customer login. 

4. User may later apply for event organiser access without a second personal account. 

Exceptions and controls: Duplicate email, weak/mismatched password and expired link are rejected; unsuccessful email delivery is queued without silently marking account verified. 

### **5.2 Change registered email** 

5. Authenticated user chooses Change Email, enters current password and proposed new email. 

6. Server re-verifies password, checks new email uniqueness and sends verification challenge. 

7. Old email remains active until the new address is verified through valid single-use link. 

8. System atomically activates new email, invalidates challenge, audits the change and notifies previous address. 

Exceptions and controls: Reject mismatched password, duplicate email, expired token, race on uniqueness and unauthorised crossaccount change. 

### **5.3 Event application, venue approval and publishing** 

9. Verified applicant enters event requirements, preferred SICC venue/date and staffing arrangement. 10. Application progresses through Pending Review / Under Review / Requires Revision / Approved or Rejected/Withdrawn. 

11. Admin evaluates physical venue/layout, conflicts, equipment, setup and teardown, then grants final venue allocation. 

12. Approval creates Draft event, adds authorised organiser event assignment and permits construction of sessions/categories. 

13. Admin checks publication prerequisites and publishes; sales opens and closes according to configured window. 

T4Y  •  Software Requirements Specification v2.0   |   Page 29 

**TICKETS4YOU  /  SICC** 

Exceptions and controls: Venue preference is not a reservation; conflicting allocations and attempted organiser self-publication are rejected. 

### **5.4 Session/category setup and capacity** 

14. Admin or authorised personnel create independent sessions under the approved event with valid SICC space and timing. 

15. Admin approves separate per-session ticket allocation not exceeding safe venue/layout capacity. 

16. Category manager defines flexible categories, quotas, prices, optional eligibility or reserved complimentary stock. 

17. Once ticket sales begin, ticket category configuration and price edits are locked; booked prices remain snapshotted. 

Exceptions and controls: Reject overlapping venue intervals, session capacity above approved safe level, invalid price and incompatible allocation. 

### **5.5 Paid customer booking and ticket issue** 

18. Verified customer selects a published event, specific session, categories, quantities and seats where applicable. 

19. System validates cross-category per-booking maximum and available inventory, transactionally creates hold at reservation time. 

20. Within configured hold (five minutes by default), customer reviews MYR subtotal/fees and attempts simulated payment; failures may retry without extending hold. 

21. On confirmed successful payment and safe seat ownership, order becomes Paid; issue separate ticket ID/QR for each unit and create receipt/notifications. 

22. Customer opens tickets in authenticated Dashboard and may download PDF or share individual screenshots, subject to warning and event rules. 

Exceptions and controls: Concurrent last-seat reservation must reject one claimant. Late payment after hold expiry can fulfil only original safely available allocation, otherwise a resolution/refund case is created. 

### **5.6 Complimentary and free ticket issuance** 

23. Admin/authorised event manager reserves a complimentary allocation within approved session capacity. 

24. Public free ticket follows account-verified free checkout; complimentary issuance follows authorised invitation/allocation. 

25. Issue separate individual ticket ID/QR for every free or complimentary entitlement. 

26. Capacity and attendance include issued tickets while revenue and Paid Tickets Sold exclude zero-price entitlement. 

Exceptions and controls: Do not subtract an issued complimentary ticket again from already reserved allocation; only release eligible invalidated tickets when policy permits. 

### **5.7 Ordinary refund request and financial reversal** 

27. Purchaser selects eligible individual ticket(s), opens a support refund request and supplies necessary reason. 

28. System records exact valid submission timestamp, purchased policy version, ticket/session start, original price and fee treatment. 

29. System computes refund estimate using precise tier boundaries, checked-in eligibility and applicable customerfee policy. 

30. Admin/authorised Finance Staff approves or rejects; approval sends idempotent financial refund instruction to Booking/Payment service. 

31. Only verified refund completion results in final refunded status, affected ticket invalidation and permitted inventory release. 

32. If payment reversal fails, keep case Approved but mark financial transaction Failed/Requires Action; alert Finance Staff. 

T4Y  •  Software Requirements Specification v2.0   |   Page 30 

**TICKETS4YOU  /  SICC** 

Exceptions and controls: Prevent duplicate requests/financial payouts, restrict ticket admission/transfer during unresolved refund, restore valid state on rejection where appropriate. 

### **5.8 Event cancellation and postponement** 

33. SICC Admin formally cancels or postpones event/session and records reason, scope and authorisation. 

34. For cancellation, affected paid tickets qualify for 100% original ticket price plus all customer-paid fees even if admitted; generate individual cases and audit. 

35. For postponement, preserve existing tickets for new session schedule and publish special refund request window (default 14 days from notification). 

36. Notify known purchasers, verified transferred recipients, assigned organisers and relevant staff. 

37. Revalidate venue reservations, session capacities and staff shifts for rescheduled events. 

Exceptions and controls: Cancellation exception overrides ordinary date tiers and checked-in exclusion. Rescheduling must not silently worsen previous purchase refund protections. 

### **5.9 Formal ticket transfer versus screenshot sharing** 

38. If event transfers enabled and deadline not passed, purchaser selects unused valid ticket without refund/dispute restrictions. 

39. Send expiring single-use verification/acceptance link to recipient email; a T4Y account is optional. 

40. On accepted transfer, atomically invalidate former QR, create replacement token, update entitlement and expose guest ticket through restricted verified-email portal. 

41. Retain original purchaser financial refund responsibility; notify both known parties. 

42. Alternatively purchaser may share QR screenshot informally; no transfer record or token replacement occurs. 

Exceptions and controls: Screenshot may be fake, duplicated or already used; first valid admission consumes ticket. Formal transfer cannot occur after check-in or cutoff. 

### **5.10 Gate check-in and physical re-entry boundary** 

43. Authorised entrance staff within assigned shift/grace scan individual QR or use justified authorised manual lookup. 

44. Live validation checks current ticket token, session, validity, check-in state and optional category identity rules. 

45. Successful first admission creates a unique ticket check-in record and increments unique attendance once. 

46. Repeated QR presentation for another first admission is rejected. 

47. If SICC permits physical re-entry, staff supervise outside T4Y; software makes no exit/re-entry validation or activity record. 

Exceptions and controls: Connectivity outage uses approved supervised contingency; never accept screenshots without necessary validity assurance; do not add re-entry logs or analytics. 

### **5.11 Staff account, assignment and shift changes** 

48. Authorised Organiser requests staffing; Admin reviews request, creates staff account if needed, assigns staff category and eligible event access. 

49. Scheduler creates an event-wide or custom datetime shift, checking active staff account, category and shift overlap. 

50. Staff receives account/shift notification and sees current/next shifts in Staff Dashboard. 

51. Permitted shift-gated actions become available from 30 minutes before to 30 minutes after assignment by default. 

52. Postponement or assignment change triggers overlap re-check and staff notifications. 

Exceptions and controls: Prevent concurrent overlapping shift assignments and immediate access when staff account/category/shift is revoked. 

T4Y  •  Software Requirements Specification v2.0   |   Page 31 

**TICKETS4YOU  /  SICC** 

## **6. State models, calculations and information architecture** 

### **6.1 Canonical lifecycle and status transitions** 

|**Object**|**Primary statuses / transitions**|**Constraints and ownership**|
|---|---|---|
|Application|Pending Review<br>Under Review<br>→<br>→<br>Approved / Rejected / Requires Revision;<br>revision<br>review; withdrawal where<br>→<br>eligible.|IVAN Event; requests must preserve<br>revision and Admin decision trail.|
|Event|Draft<br>Published<br>Ongoing<br>→<br>→<br>→<br>Completed<br>Archived; Postponed /<br>→<br>Cancelled exceptions.|IVAN Event; final publication and venue<br>approval Admin only.|
|Sales status|Scheduled / Open / Closed (independent<br>of Event lifecycle).|Event/Booking; jobs act on configured<br>timestamps.|
|Booking order|Pending<br>Paid; or Cancelled / Failed /<br>→<br>Expired as applicable.|JAY Booking; one booking = one session,<br>may contain many ticket items.|
|Checkout hold|Active<br>Consumed / Expired / Released.<br>→|JAY Booking; hold never extends<br>automatically after payment retry.|
|Payment attempt|Initiated / Processing / Succeeded / Failed<br>/ Expired (provider mapping in future).|JAY Booking; immutable attempt<br>identifiers and idempotent confirmation.|
|Ticket entitlement|Issued valid<br>Checked-in flag or<br>→<br>transfer; Refunded / Voided / Cancelled /<br>Expired as appropriate.|JAY Booking; individual QR; do not<br>conflate check-in with cancellation.|
|Transfer invitation|Pending<br>Accepted / Declined / Expired /<br>→<br>Cancelled.|JAY Booking; acceptance atomically<br>rotates QR token.|
|Refund case|Pending Review<br>Under Review<br>→<br>→<br>Approved / Rejected; Approved<br>→<br>Processing<br>Completed or financial<br>→<br>attention.|IVAN Support owns decision; JAY<br>payment refund owns transaction<br>outcome.|
|Refund transaction|Pending<br>Processing<br>Succeeded /<br>→<br>→<br>Failed / Requires Action.|JAY Booking; failed payout does not<br>equal rejected approval.|
|Support case|Open / Under Review / Waiting for<br>Customer / Resolved / Closed / Archived.|IVAN Support; preserve case history.|
|Staff shift|Draft / Assigned / Changed / Cancelled /<br>Completed.|YONG Scheduling; check overlap and<br>access on changes.|
|Notification delivery|Queued<br>Attempting<br>Accepted / Retry<br>→<br>→<br>Scheduled / Failed / Permanent Failure.|Shared notification service; transport<br>acceptance is not guaranteed inbox<br>delivery.|



### **6.2 Refund calculation and policy precedence** 

**Ordinary eligibility time:** Calculate exact remaining duration = session scheduled start instant minus valid refundrequest submission instant. Display Malaysia local time; do not round remaining duration to whole calendar days before selecting a tier. 

T4Y  •  Software Requirements Specification v2.0   |   Page 32 

**TICKETS4YOU  /  SICC** 

|**Condition**|**Default ticket-price refund**|
|---|---|
|Remaining time<br>31 × 24 hours<br>≥|100%|
|16 × 24 hours<br>remaining time < 31 × 24 hours<br>≤|50%|
|8 × 24 hours<br>remaining time < 16 × 24 hours<br>≤|25%|
|Remaining time < 8 × 24 hours|0%|



**Precedence:** Formal SICC cancellation (100% ticket price and all customer-paid fees, including checked-in) overrides ordinary tier, checked-in exclusion and policy window. Postponement uses Admin-defined special window without worsening existing agreed rights. Ordinary checked-in tickets for continuing events are ineligible. Fee treatment for ordinary refunds uses frozen event/category policy and applicable requirements. 

**Rounding:** Perform calculations with precise decimal arithmetic, define per-ticket fee allocation and discount distribution, then round final payable/refund amounts in MYR to two decimal places using the configured half-up rule. Preserve both original amounts and completed adjustments. 

### **6.3 Inventory equations and counting definitions** 

Inventories are tracked per event session, and where needed per ticket category and seat. A reserved complimentary quota, issued complimentary ticket and active checkout hold are mutually reconciled states, not independent deductions of the same seat. 

**Available-to-Book:** Approved saleable allocation − currently valid issued ticket commitments − active checkout holds − remaining non-saleable/blocked/reserved commitments. Each capacity unit may appear in only one deducted state. 

**Historical Paid Tickets:** All tickets successfully issued against completed positive paid transactions, shown as historical activity even if later refunded/voided. 

**Net Paid Tickets Sold:** Currently valid paid ticket entitlements after completed refunds, voids and confirmed reversals, excluding public-free and complimentary tickets. 

**Free and Complimentary Issued:** Currently valid zero-price public-free or controlled complimentary ticket entitlements; affect capacity, not paid ticket sales revenue. 

**Unique Attendance:** Count distinct individual tickets with a first successful admission record. Do not add attempted scans, manual re-entry or exit events. 

**Gross Ticket Revenue:** Sum successfully paid ticket-price components before later adjustments, allocated to payment completion date. 

− − **Net Ticket Revenue:** Gross ticket-price revenue completed ticket-price refunds confirmed ticket-price payment reversals/adjustments (avoid duplicate deduction). 

**Fees:** Track customer-paid fees, fee refunds and fee reversals distinctly from the ticket-price revenue. Reporting may show combined customer payment total as a separate measure. 

**Most Popular:** Percentage of approved tickets sold per elapsed day since publication, with a minimum elapsed duration and deterministic tie-breaker to be configured for reporting. 

### **6.4 Reporting and history** 

A booking order is counted once in order totals regardless of ticket quantity. Sales use payment completion dates; completed refunds use financial refund completion dates; scheduling views use session dates. Reporting retains historical transactions for cancelled, postponed and archived events. Refund and reversal adjustments never erase 

T4Y  •  Software Requirements Specification v2.0   |   Page 33 

**TICKETS4YOU  /  SICC** 

original gross transaction history. Default dashboard reporting period is the current calendar month. Reports must be permission-scoped. 

### **6.5 Logical entity catalogue and primary attributes** 

The following is a proposed initial logical data dictionary to guide schema and ERD work. Names are implementation proposals (P), not the actual signed-off physical schema; full types, lengths, FK diagrams and 3NF proof are separate coursework deliverables. 

|**Entity / likely table**|**Representative fields and keys**|**Key rule / module owner**|
|---|---|---|
|users|user_id PK; email UNIQUE; staff_id<br>UNIQUE nullable; password_hash;<br>full_name; phone; status;<br>email_verified_at; created_at|TAN; avoid DOB unless eligibility needed;<br>never plaintext password.|
|roles / user_roles|role_id PK; code; user_id FK; role_id FK;<br>assigned_at|TAN; user can be Customer and<br>Organiser concurrently.|
|staff_categories / category_permissions|category_id PK; permission_id FK;<br>enabled; updated_at|TAN; immediately enforced permissions.|
|event_assignments|assignment_id PK; user_id FK; event_id<br>FK; role; is_primary; approved_by; status|TAN + IVAN; event-scope authorisation.|
|auth_sessions|session_id PK; user_id FK;<br>last_activity_at; revoked_at; role_context|TAN; one active session, role-specific idle<br>timeout.|
|verification_tokens|token_id PK; user_id FK; token_hash;<br>purpose; expires_at; used_at|TAN; single-use expiring challenges.|
|organisations|organisation_id PK; name; details; status;<br>approved_at|IVAN/TAN; approved changes with<br>revision history.|
|venues|venue_id PK; name; SICC_address; active;<br>notes|TAN; SICC spaces only.|
|venue_spaces|space_id PK; venue_id FK;<br>parent_space_id FK; physical_capacity;<br>type|TAN; hall partitions and nesting.|
|venue_layouts|layout_id PK; space_id FK; capacity;<br>layout_type; is_active|TAN; seated/standing/mixed layouts.|
|venue_blocks / reservations|reservation_id PK; space_id FK; start_at;<br>end_at; buffer; event_id FK; status|TAN; prevent conflicting resource<br>overlaps transactionally.|
|event_applications|application_id PK; applicant_id FK;<br>preferred_space; proposed_schedule;<br>status; submitted_at|IVAN; not an automatic venue<br>reservation.|
|application_revisions|revision_id PK; application_id FK;<br>version; payload; changed_at|IVAN; audit review and requested<br>revisions.|
|events|event_id PK; application_id FK; title;<br>category; status; sales_open_at;<br>sales_close_at; published_at|IVAN; Admin controls publication.|
|event_translations|event_id FK; locale; title; description;<br>approved_version|IVAN; source language fallback.|



T4Y  •  Software Requirements Specification v2.0   |   Page 34 

**TICKETS4YOU  /  SICC** 

|**Entity / likely table**<br>event_sessions|**Representative fields and keys**<br>session_id PK; event_id FK; space_id FK;<br>layout_id FK; start_at; end_at;<br>approved_capacity; status|**Key rule / module owner**<br>YONG; per-session capacity and<br>attendance.|
|---|---|---|
|ticket_categories|category_id PK; event_id FK; name; type;<br>eligibility; price; status; locked_at|JAY Category; price/config locked after<br>sale start.|
|session_category_allocations|allocation_id PK; session_id FK;<br>category_id FK; quota;<br>reserved_comp_qty|JAY Category/YONG; cannot exceed<br>approved allocation.|
|booking_orders|booking_id PK; booking_ref UNIQUE;<br>buyer_user_id FK; session_id FK; status;<br>hold_expiry_at|JAY Booking; one session/order.|
|booking_items|booking_item_id PK; booking_id FK;<br>category_id FK; seat_id nullable;<br>unit_price_snapshot; fee_snapshot|JAY Booking; amount immutable after<br>confirmed purchase.|
|checkout_holds / seat_claims|hold_id PK; booking_id FK; session_id FK;<br>seat_id nullable; qty; expires_at; status|JAY Booking; transactionally exclusive<br>active seats.|
|payment_transactions|payment_id PK; booking_id FK;<br>idempotency_key UNIQUE; amount; fees;<br>method; status; completed_at|JAY Booking; simulated provider events<br>and real-gateway-ready abstraction.|
|tickets|ticket_id PK; booking_item_id FK;<br>current_owner_ref; token_hash UNIQUE;<br>issued_type; valid_state; first_checkin_at|JAY Booking; one QR per ticket.|
|ticket_transfers|transfer_id PK; ticket_id FK; sender_ref;<br>recipient_email; token_hash; status;<br>expires_at; accepted_at|JAY Booking; verified guest acceptance +<br>QR rotation.|
|refund_policies|policy_id PK; event_id FK; category_id<br>nullable; version; tiers; fee_terms;<br>effective_from|IVAN/JAY Category; freeze purchase<br>policy reference.|
|support_cases / messages|case_id PK; customer_id FK; event_id FK;<br>case_type; priority; status; assigned_to;<br>message_id FK|IVAN; customer/staff support history.|
|refund_requests|refund_id PK; ticket_id FK; case_id FK;<br>policy_version; submitted_at;<br>estimated_amount; approval_state|IVAN; preserve valid submission<br>timestamp.|
|refund_transactions|refund_txn_id PK; refund_id FK;<br>payment_id FK; amount; provider_ref;<br>status; completed_at|JAY Booking; approval =Y payout,<br>idempotent.|
|financial_adjustments|adjustment_id PK; payment_id FK; kind;<br>ticket_component; fee_component;<br>effective_at|JAY Booking; append-only audited<br>reversals.|
|ticket_checkins|checkin_id PK; ticket_id UNIQUE FK;<br>session_id FK; first_checked_at;<br>checked_by FK|Shared QR; ONE first admission per<br>ticket; no re-entry table.|
|staff_shifts|shift_id PK; staff_user_id FK; event_id FK;|YONG; overlap detection incl overnight.|



T4Y  •  Software Requirements Specification v2.0   |   Page 35 

**TICKETS4YOU  /  SICC** 

|**Entity / likely table**|**Representative fields and keys**|**Key rule / module owner**|
|---|---|---|
||session_id FK nullable; start_at; end_at;<br>status||
|notifications / deliveries|notification_id PK; recipient_ref; type;<br>channel; template_id; status; retry_at;<br>attempts|Shared; automatic retries independent of<br>business status.|
|audit_logs|audit_id PK; actor_id; action;<br>resource_type; resource_id; occurred_at;<br>safe_delta|Shared; no token/password secrets.|
|settings / jobs / backups|setting_key PK; setting_value;<br>changed_by; scheduled_job_id PK;<br>last_result; backup_ref|Shared; restricted administrative status.|



### **6.6 Foreign keys, constraints and consistency** 

- Use stable surrogate primary keys and foreign keys rather than email strings as permanent links to payments, events or tickets. 

- Create unique indexes for registered email, staff identifier, booking reference, payment idempotency key, current QR token and one successful first-checkin row per ticket. 

- Enforce referential integrity for events ↔ sessions ↔ categories/orders/tickets, users ↔ event assignments and staff shifts, booking ↔ payments/refunds. 

- Enforce database-level exclusivity or transactionally protected seat claims per session; a simple UI availability check cannot prevent overselling. 

- Use controlled transactional updates around inventory holds, order settlement, issue of QR tickets, formal transfer token rotation and refund completion/void/release. 

- Lock or transactionally validate venue-reservation conflicts across parent hall and subspaces, including buffer times. 

- Do not represent price, money or quantities as unvalidated floating-point or formatted strings; use suitable DECIMAL or integer minor units internally. 

- Maintain immutable financial payment/adjustment entries sufficient to reconstruct gross and net revenue even after account anonymisation. 

- Maintain audit/event histories for relevant writes; do not physically delete referenced historical bookings, payments or ticket entitlements. 

### **6.7 Data lifecycle and retention** 

Account suspension is an access control state and differs from account deletion. Deletion requests require proof of requester authority, checks for pending duties or transactions, and removal/anonymisation only where eligible. Necessary payment/refund history and security audit evidence remain under documented retention policy. Temporary verification/reset tokens and expired checkout metadata follow cleanup rules. Exact retention durations and any legal exemptions must be approved by SICC before real deployment; this SRS does not invent fixed statutory periods. 

T4Y  •  Software Requirements Specification v2.0   |   Page 36 

**TICKETS4YOU  /  SICC** 

## **7. Non-functional requirements and security** 

### **7.1 Security and authentication (confirmed unless annotated)** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|NFR-001|Use secure password hashes such as PHP<br>password_hash/password_verify; never<br>store or log a plaintext password.|Inspect database records and source.|
|NFR-002|Use PDO prepared statements and<br>parameterisation for all untrusted<br>inputs; validate server-side and safely<br>encode untrusted output.|SQL injection and XSS regression tests.|
|NFR-003|Use CSRF protection for state-changing<br>requests and secure session cookies;<br>regenerate session IDs after<br>authentication.|Attempt missing/invalid CSRF; inspect<br>cookie settings.|
|NFR-004|Enforce current role, event assignment,<br>category permission and shift gate on<br>every protected server endpoint.|Direct URL/API requests cannot bypass<br>check.|
|NFR-005|Revalidate current password or<br>equivalent recent authentication for<br>changes to email, password and other<br>sensitive security settings.|Sensitive change without password<br>denied.|
|NFR-006|Admin/Staff idle timeout 60 minutes;<br>Customer/Organiser 30 minutes; no<br>additional absolute session-duration cap<br>in prototype.|Test role-specific idle expiry and polling.|
|NFR-007|One active account session at a time; new<br>login invalidates previous session;<br>account suspension revokes sessions and<br>event permission revocations take effect<br>immediately.|Concurrent login and revoked role test.|
|NFR-008|Rate-limit login, recovery and<br>verification attempts; use single-use and<br>expiring security tokens.|Brute-force/replay verification tests.|
|NFR-009|Restrict private file storage outside<br>public document root and serve via<br>permission-checked application<br>endpoints.|Direct URL to private upload fails.|
|NFR-010|Accept application PDF/JPG/PNG uploads<br>up to a proposed 10 MB per file with<br>content validation and safe generated<br>storage names.|MIME spoof, oversized file rejected.|
|NFR-011|Never store full real payment-card<br>credentials in prototype; simulated<br>transaction records must be clearly<br>distinguishable from live provider<br>confirmations.|Check absence of card data.|



T4Y  •  Software Requirements Specification v2.0   |   Page 37 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|NFR-012|Record actor/action/resource/timestamp<br>and safe change metadata for significant<br>security and administrative actions;<br>exclude QR secrets, password values and<br>unneeded personal fields.|Audit log privacy review.|
|NFR-013|Prohibit unapproved identity profiling:<br>personalised history-based<br>recommendations require explicit opt-in<br>and an easy withdrawal mechanism.|Consent removal stops history-based<br>suggestions.|
|NFR-014|Require HTTPS and production-hardened<br>hosting before public deployment; local<br>XAMPP instance is for development only.|Deployment checklist sign-off.|
|NFR-015|Prepare architecture for future Admin<br>2FA without claiming 2FA has been<br>implemented in the prototype.|Admin sign-in succeeds with selected<br>prototype approach.|



### **7.2 Performance, reliability and availability** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|QAL-001|Preserve ticket inventory under high<br>contention; every attempted seat claim<br>or quota consumption validates<br>authoritative transactional state.|Stress-test parallel last-seat requests.|
|QAL-002|Dashboard polling interval is 30 seconds;<br>it must neither be used as inventory lock<br>nor reset inactivity timer.|Leave dashboard idle and measure<br>timeout.|
|QAL-003|Scheduled tasks implement safe retries<br>with idempotency keys or deduplication;<br>email retry does not create extra<br>payment/ticket records.|Run queue worker twice for same job.|
|QAL-004|Critical job/backup/payment/refund<br>failures generate prompt Admin alerts;<br>lower-priority issues appear in Admin<br>system-health listing.|Simulate failed scheduled task.|
|QAL-005|Use daily database backups and conduct<br>periodic restore tests; protect backup<br>copies against unrelated user access.|Restore a recent sample backup.|
|QAL-006|Use transaction rollback or safe<br>compensation when booking, payment<br>settlement, ticket issue, transfer or<br>refund processing fails partway through.|Inject controlled mid-transaction failure.|
|QAL-007|Maintain meaningful user error<br>messages and never expose stack traces,<br>raw SQL, token secrets or credentials<br>publicly.|Trigger expected server validation error.|



T4Y  •  Software Requirements Specification v2.0   |   Page 38 

**TICKETS4YOU  /  SICC** 

|**ID**|**System shall / requirement**|**Verification / acceptance focus**|
|---|---|---|
|QAL-008|Support mobile, tablet and desktop<br>screen widths for booking, staff scanning<br>and dashboards.|Manual responsive UI check.|
|QAL-009|Maintain code in reusable<br>module/services and a shared design<br>system to simplify cross-member<br>integration.|Inspect common auth and data contracts.|



Performance target note (P/PP): User has not confirmed production response-time, throughput, uptime or recovery-point objectives. For academic testing, collect measured page/checkout timings and parallel-booking results rather than describing invented SLA percentages as confirmed. 

### **7.3 Operational configuration baseline** 

|**Key**|**Confirmed value / policy**|**Notes**|
|---|---|---|
|OP-01|Supervised physical re-entry allowed by<br>SICC where desired; T4Y neither verifies<br>nor records re-entry.|First check-in retained once.|
|OP-02|Approved refund with failed payout<br>stays approved; Finance investigates<br>Failed/Requires Action.|No duplicate reversal.|
|OP-03|Default 14-day special postponement<br>refund window from customer<br>notification; Admin configurable.|Applicable consumer/legal requirements<br>override defaults.|
|OP-04|Documented SICC per-data-type<br>retention; exact durations reviewed<br>before production.|No arbitrary fixed period claimed.|
|OP-05|Initial email attempt, then retry after<br>1/5/15/60 minutes; alert Admin if all<br>unsuccessful.|Bounded retry policy.|
|OP-06|Official transfer allowed until session<br>start unless Admin sets earlier cutoff.|Only valid unused tickets.|
|OP-07|No absolute session lifetime, only role<br>idle timeout.|Production security review<br>recommended.|
|OP-08|30-minute grace period before and after<br>shifts; Admin configurable.|Shift-specific operational access.|
|OP-09|Support priority Low, Normal, High,<br>Urgent; Admin target response settings.|Targets not numerically specified.|
|OP-10|MYR decimal calculations; final two-<br>decimal half-up rounding.|Document fee/proration logic.|
|OP-11|Admin-approved translations; approved<br>original-language fallback for<br>untranslated event description.|Four locale options.|
|OP-12|Immediate Admin critical-error alert;|Alert channel must be configured.|



T4Y  •  Software Requirements Specification v2.0   |   Page 39 

**TICKETS4YOU  /  SICC** 

|**Key**|**Confirmed value / policy**|**Notes**|
|---|---|---|
||routine errors in monitoring dashboard.||



### **7.4 Privacy and record access** 

Treat account data, staff credentials, support messages, refund evidence, payment history, private event documents and guest-transfer verification links as sensitive. Use purpose-based data access, event-level scope, restricted file downloads and the minimum necessary data in notices and reports. Additional DOB/eligibility fields are collected only for relevant ticket categories. Records required for auditing remain traceable after a person requests account deletion while eligible profile data is removed or anonymised. 

## **8. User interface, localisation and integration contracts** 

### **8.1 Required screens and interface behaviour** 

|**Actor / role**|**Core screens**|**Critical UI behaviour**|
|---|---|---|
|Visitor|Home, event listing/search, event details,<br>venue information, login/register.|No checkout before verified account;<br>publish only Admin-approved events.|
|Customer|Profile/Change Email, upcoming/past<br>bookings, checkout, ticket/QR, receipt,<br>refund request, support, notifications,<br>favourites, recommendations.|Inline errors; successful booking persists<br>if email fails.|
|Organiser|Assigned-events overview, application<br>forms/status, authorised event details,<br>performance and delegated controls.|Per-event context; no private data from<br>unrelated events.|
|Admin|Event/venue/applicant queue, roles/staff,<br>ticket settings, support/refunds,<br>monitoring, KPI/financial dashboard and<br>settings.|Confirmation for sensitive actions and<br>change audit.|
|Entrance Staff|Assigned shift/event selector, QR<br>scanner/manual lookup, validation result<br>and first-admission count.|Online live validation; no re-entry<br>UI/history.|
|Booking/Finance Staff|Assigned queues, booking reference<br>lookup and role-allowed refund/finance<br>actions.|No cross-event data leakage.|
|Guest transferred recipient|Email-verified invitation acceptance and<br>own ticket display/download.|No account required; no purchaser<br>account access.|



### **8.2 Interface validation and accessibility** 

- Display field-specific input errors close to the affected controls and preserve safe form values after validation failure. 

- Display explicit success, waiting, retry and failure states for booking, payment simulation, refund approval and email delivery separately. 

T4Y  •  Software Requirements Specification v2.0   |   Page 40 

**TICKETS4YOU  /  SICC** 

- Support keyboard navigation and programmatic labels, readable text and contrast, mobile-width inputs and clear status differences that do not depend on colour alone. 

- Confirm purchase quantity, total price, fee line items, session date/time, transfer recipient and refund result before sensitive actions. 

- Prohibit automatic duplicate submissions; show transaction reference and safe recovery instructions after network interruption. 

- Provide English, Bahasa Melayu, Simplified Chinese and Traditional Chinese user-interface locale options with stable string keys. 

- Use Admin-approved interface translations; organisers may submit event description translations, and absent translations display the approved source language without unreviewed machine-generated text. 

- For dashboard charts, provide readable labels and tabular data alternatives in reports for accessibility. 

### **8.3 Shared service contracts (proposed API responsibilities)** 

|**Service owner**|**Inputs / calls from other modules**|**Returns / side effects**|
|---|---|---|
|UAM/TAN|requirePermission(account, action,<br>event?, shift?)|Allow/deny based on current<br>role/category/assignment/schedule;<br>security audit.|
|Venue/TAN|checkSpaceAvailability(space, start, end,<br>layout, buffers)|Conflict check, reservation/hold decision.|
|Event/IVAN|getEventState(event),<br>approveEvent(application)|Draft/Published/Cancelled states,<br>organiser assignments.|
|Session/YONG|getSession(session),<br>verifyCapacity(session, qty)|Start/end, approved allocation, valid<br>venue/layout constraints.|
|Category/JAY|getSellableCategory(session, category),<br>snapshotPrice()|Price, eligibility, quotas and policy<br>snapshot.|
|Booking/JAY|reserveInventory(), settlePayment(),<br>issueTicket(), voidTicket(),<br>transferTicket()|Order/ticket/payment IDs; transactional<br>changes; events for notifications.|
|Support/IVAN|calculateRefund(ticket, timestamp),<br>approveRefund(refund)|Frozen amount, permission-gated<br>decision; payout request to Booking.|
|Scheduling/YONG|isShiftAuthorized(staff, event, time),<br>assignShift()|Time-gate decision and conflict/audit<br>updates.|
|Notifications/shared|enqueueNotice(type, recipient, resource)|Stored in-app record, delivery retries,<br>escalations.|
|Dashboard/shared|getKpis(role, eventScope, dateFilter)|Aggregated permission-scoped read<br>results; 30-second refresh.|



### **8.4 Technical environment** 

|**Component**|**Baseline**|
|---|---|
|Workspace|C:\xampp\htdocs\T4Y|
|Database|t4y_db|



T4Y  •  Software Requirements Specification v2.0   |   Page 41 

**TICKETS4YOU  /  SICC** 

|**Component**|**Baseline**|
|---|---|
|Backend|PHP 8, modular controllers/services/repositories and PDO|
|Frontend|HTML, CSS, JavaScript and Bootstrap 5|
|Local platform|XAMPP, Apache and MySQL-compatible database, VS Code|
|Jobs|Windows Task Scheduler during Windows/XAMPP<br>development; cron-equivalent production scheduler|
|Files|Private upload storage outside public root; controlled retrieval<br>endpoints|
|Testing|Fictional SICC records and simulated financial operations|
|Production deployment|HTTPS hosting/hardened DB/backup/email/payment review<br>before real use|



## **9. Verification strategy and acceptance tests** 

### **9.1 Assessment/test evidence rule** 

Every assessed Create/Retrieve/Update/Delete process in each of the eight modules requires clearly identified input validation, at least one valid and one invalid/boundary test where meaningful, documented expected output, actual screenshots and a process-specific activity diagram/flowchart. The joint system also requires integrated end-to-end tests. Keep evidence files grouped by module and test ID. 

### **9.2 Functional acceptance catalogue** 

|**Test ID**|**Area**|**Scenario / input**|**Expected result**|
|---|---|---|---|
|UAM-AT01|UAM|Register with valid email and<br>matching password|Creates unverified Customer;<br>verification required.|
|UAM-AT02|UAM|Repeat email / invalid<br>registration fields|Rejects and displays relevant<br>field errors.|
|UAM-AT03|UAM|Verify expired/reused token|Rejects link without activating<br>account.|
|UAM-AT04|UAM|Change email without correct<br>password|Rejects change; old email<br>stays active.|
|UAM-AT05|UAM|Verify new email after correct<br>password|Activates new address and<br>audits.|
|UAM-AT06|UAM|Staff initial temporary login|Forces password reset before<br>work access.|
|UAM-AT07|UAM|Revoke organiser event while<br>active|Restricted event action<br>instantly denied.|
|UAM-AT08|UAM|New login from second device|Prior session is invalidated.|
|UAM-AT09|UAM|Idle<br>Admin/Staff/Customer/Organi|Expire at respective<br>60/60/30/30 minutes.|



T4Y  •  Software Requirements Specification v2.0   |   Page 42 

**TICKETS4YOU  /  SICC** 

|**Test ID**|**Area**|**Scenario / input**<br>ser sessions|**Expected result**|
|---|---|---|---|
|VEN-AT01|Venue|Create and retrieve SICC<br>hall/layout|Persist capacity, facilities and<br>readable layout.|
|VEN-AT02|Venue|Overlapping reservation<br>including setup buffer|Conflicting approval rejected.|
|VEN-AT03|Venue|Unsafe venue capacity<br>reduction|Block and explain active<br>commitments.|
|VEN-AT04|Venue|Attempt hard delete<br>referenced hall|Archive/deactivate or reject<br>according to policy.|
|BKG-AT01|Booking|Select tickets across categories<br>above order max|Reject combined quantity.|
|BKG-AT02|Booking|Make multiple compliant<br>bookings|Permit while stock remains.|
|BKG-AT03|Booking|Two users reserve last seat<br>concurrently|Exactly one active<br>reservation.|
|BKG-AT04|Booking|Expired hold and retry<br>payment|No automatic extension; stock<br>released safely.|
|BKG-AT05|Booking|Successful order for four<br>tickets|One booking reference, four<br>QR tokens.|
|BKG-AT06|Booking|Duplicate successful callback|No duplicate<br>charge/order/tickets.|
|BKG-AT07|Booking|Late successful payment after<br>hold expiry|Fulfil only if original<br>allocation safely available;<br>otherwise resolution.|
|BKG-AT08|Booking|Formal transfer acceptance<br>by guest|Old QR invalid; guest link<br>retrieves replacement token.|
|BKG-AT09|Booking|Issue complimentary without<br>charge|Consumes session capacity,<br>zero paid sales.|
|CAT-AT01|Category|Create category with valid<br>price/quota|New ticket category visible<br>per authorised event.|
|CAT-AT02|Category|Edit price after sales begin|Edit denied; historic snapshot<br>unchanged.|
|CAT-AT03|Category|Delete already issued category|Archive or block hard<br>deletion.|
|CAT-AT04|Category|Apply two discounts<br>simultaneously|Reject stacking beyond one<br>discount.|
|SUP-AT01|Support|Submit valid case and view<br>history|Recorded with priority, owner<br>and status.|
|SUP-AT02|Support|Refund request exactly<br>31/16/8 days before|Select correct tier at exact<br>boundary.|



T4Y  •  Software Requirements Specification v2.0   |   Page 43 

**TICKETS4YOU  /  SICC** 

|**Test ID**<br>SUP-AT03|**Area**<br>Support|**Scenario / input**<br>Ordinary refund on checked-<br>in ticket|**Expected result**<br>Denied if event continuing.|
|---|---|---|---|
|SUP-AT04|Support|Cancel event after check-in|Full ticket price and customer-<br>paid fees refunded.|
|SUP-AT05|Support|Finance refund payout fails|Approved case retained,<br>transaction Needs Action.|
|SUP-AT06|Support|Refund one ticket of four|Other three remain valid.|
|EVT-AT01|Event|Submit and revise event<br>application|Status/revision preserved.|
|EVT-AT02|Event|Approved application creates<br>event|Draft event; not auto-<br>published.|
|EVT-AT03|Event|Publish incomplete event|Denied with missing<br>prerequisite guidance.|
|EVT-AT04|Event|Postpone event|Tickets valid on revised<br>session; special<br>window/notices.|
|SES-AT01|Session|Create separate sessions<br>beyond single simultaneous<br>hall capacity|Allowed if non-overlapping<br>and each respects capacity.|
|SES-AT02|Session|Overlap room or reduce<br>capacity under holds|Rejected until safe.|
|SES-AT03|Session|Delete sold-ticket session|Cancellation/archive, not hard<br>deletion.|
|SCH-AT01|Scheduling|Assign same staff to<br>overlapping shifts|Conflict rejected including<br>overnight.|
|SCH-AT02|Scheduling|Assign to non-overlapping<br>shifts|Allowed, even across different<br>events.|
|SCH-AT03|Scheduling|Use shift-gated QR check-in<br>just outside grace|Denied; within configured<br>grace permitted.|
|SCH-AT04|Scheduling|Cancel shift while user active|Operational access revoked;<br>notification emitted.|
|QR-AT01|Shared QR|Scan valid ticket first time|One successful check-in;<br>attendance +1.|
|QR-AT02|Shared QR|Rescan ticket or use old<br>transferred QR|Reject new first admission;<br>attendance unchanged.|
|QR-AT03|Shared QR|Permit supervised physical re-<br>entry|No T4Y exit/re-entry records<br>or analytics.|
|NTF-AT01|Notifications|Fail email after completed<br>booking|Tickets remain valid; retry at<br>configured schedule.|
|NTF-AT02|Notifications|Exhaust retry attempts|Alert Admin; maintain|



T4Y  •  Software Requirements Specification v2.0   |   Page 44 

**TICKETS4YOU  /  SICC** 

|**Test ID**|**Area**|**Scenario / input**|**Expected result**|
|---|---|---|---|
||||delivery history.|
|DASH-AT01|Dashboards|Refund a paid ticket|Net paid sold decreases;<br>historic paid sale remains.|
|DASH-AT02|Dashboards|Free/comp issued tickets|Consume inventory; do not<br>increase revenue.|
|DASH-AT03|Dashboards|Leave dashboard open but<br>idle|30-second refresh does not<br>prevent logout.|
|DASH-AT04|Dashboards|Organiser selects unassigned<br>event / exports|Reject cross-event data; PDF<br>report permission scoped.|
|SYS-AT01|Platform|Restore daily backup|Restored test database<br>consistent.|
|SYS-AT02|Platform|Submit repeated risky request<br>or invalid CSRF|Reject safely without<br>duplicate state change.|



### **9.3 Test execution and evidence template** 

|**Field**|**Instruction**|
|---|---|
|Test case ID|Match catalogue and module process diagram.|
|Preconditions|User role, event/session state, existing DB rows, relevant<br>configured limits.|
|Input data|Use clearly identified fictional valid/invalid/edge inputs.|
|Action|Describe the exact steps and endpoint/UI control.|
|Expected result|Specific status, validation message, row mutation, email/queue<br>and audit effects.|
|Actual result|Screenshot and database snapshot or log evidence; pass/fail.|
|Regression|Verify dependent module did not break, e.g. Booking<br>↔<br>Category<br>Session.<br>↔|
|Owner|Individual module assignee, with shared integration testers as<br>appropriate.|



### **9.4 Non-functional test classes** 

- Authorisation: role, event, staff category, shift and session revocation; direct-link bypass; customer support privacy. 

- Security: password hashing, CSRF, SQL injection resistance, unsafe uploads, token replay, session controls, leaked secrets. 

- Concurrency: last seat simultaneous checkout, overlapping venue approval, overlapping shift creation and late payment callback. 

- Recovery: email retry, refund processing failure, database rollback, provider callback duplication, safe job retry and backup restore. 

T4Y  •  Software Requirements Specification v2.0   |   Page 45 

**TICKETS4YOU  /  SICC** 

- Language/UI: four locales, responsive layout, missing translation fallback, labelled errors and accessible controls. 

- Reporting: historical paid vs net sold, free/complimentary issuance, ticket allocation vs physical occupancy, refunds by completed date. 

- Privacy: account deletion request, anonymisation of eligible fields, preserved financial linkage, guest ticket-only access. 

## **10. Team allocation, coursework evidence and implementation sequence** 

### **10.1 Confirmed four-member / eight-module allocation** 

|**Member**|**Module A**|**Module B**|**Unique feature candidate**<br>**(tutor approval required)**|
|---|---|---|---|
|TAN|User Account Management|Venue Management|Customer Rewards: loyalty<br>points, same-session<br>bundles and promo codes<br>(one integrated feature)|
|JAY|Booking Management (ticket +<br>payment)|Category Management|Refund Policy Interface:<br>versioned event/category<br>refund tiers and<br>refundable fee<br>configuration|
|IVAN|Customer Support & Dispute<br>Management|Event Management|Personalised Event<br>Recommendation: explicit-<br>opt-in preferences and<br>history-based ranking|
|YONG|Session Management|Staff Scheduling Management|Multi-Factor<br>Authentication (MFA):<br>protected enrolment,<br>challenge and recovery|



Each assigned module must demonstrate four working CRUD operations with appropriate validation, meaningful sub-functions and database integration. One additional unique non-CRUD function is required per student across their two modules and must be approved by the tutor. The quoted feature candidates have not been represented as already approved. 

### **10.2 Separate boundaries: individually assessed vs shared platform** 

|**Assessed module**|**Owns**|**Consumes / coordinates; must NOT**<br>**duplicate**|
|---|---|---|
|TAN User Account|user/auth/roles/staff<br>category/permission/event user<br>assignment|Consumes Event assignments; does not<br>create YONG shifts.|
|TAN Venue|venue space/layout/physical<br>capacity/reservation|IVAN event requests; YONG session<br>reservation checks.|



T4Y  •  Software Requirements Specification v2.0   |   Page 46 

**TICKETS4YOU  /  SICC** 

|**Assessed module**|**Owns**|**Consumes / coordinates; must NOT**<br>**duplicate**|
|---|---|---|
|JAY Booking|order/holds/payments/tickets/QR<br>issuance/transfers/financial refund<br>transaction|IVAN Support approval, Category prices,<br>YONG Session allocation.|
|JAY Category|ticket category/price/eligibility/quota<br>configuration|IVAN Event, YONG session approved<br>capacity; no payment settlement.|
|IVAN Support|case/dispute/refund<br>request/eligibility/approval|JAY payment payout/void; UAM account;<br>Event cancellation rights.|
|IVAN Event|event application/event<br>lifecycle/organiser<br>collaboration/publication|Venue space approval and Session<br>schedules from TAN/YONG.|
|YONG Session|session dates/times/capacity/change<br>approvals|IVAN parent event, TAN venue, JAY<br>sold/held commitments.|
|YONG Scheduling|staff shifts/assignment conflict/shift-<br>gated time state|TAN identities and staff category; YONG<br>sessions.|
|Joint shared services|dashboards, notification queue, QR gate<br>workflow, audit, platform structure|Agreed module services; no unassigned<br>shared capability.|



### **10.3 Required academic report evidence** 

AMCS2194 Project Specification (September 2026), pp. 6–10, requires Chapter 1 project ideation; Chapter 2 requirement acquisition, DFD Context/Diagram 0/Diagram 1, per-process UML Activity Diagram or Flowchart, one system-wide Class Diagram, ERD, 3NF and Data Dictionary, plus UI mockup explanations. Chapter 3 covers development environment and individual/team implementation; Chapter 4 covers test cases, actual input/result screenshots, UAT and user manual. Each student must document diagrams and testing for every process performed in their two modules. 

|**Evidence item**|**Recommended artifact / mapping**|
|---|---|
|Context, Diagram 0, Diagram 1|Whole T4Y DFD; decomposition from eight modules and<br>shared services.|
|UML Activity / Flowchart|Separate flow for each assessed CRUD process and relevant<br>unique function; labelled by member/module.|
|ERD + 3NF + Data Dictionary|Whole shared t4y_db, with complete attributes and keys; this<br>SRS only seeds the data catalogue.|
|System-wide UML Class Diagram|Classes mirror final ERD attributes; operations not required for<br>marking.|
|UI mockups|All actual screens, organised by module, with readable<br>screenshot explanation.|
|Programming documentation|Show architecture, master pages/navigation/database links;<br>each member documents own code.|
|Acceptance test case|One per process, valid and invalid/boundary sets, expected<br>result and full before/after screenshots.|



T4Y  •  Software Requirements Specification v2.0   |   Page 47 

**TICKETS4YOU  /  SICC** 

|**Evidence item**|**Recommended artifact / mapping**|
|---|---|
|Individual presentation|Only present personally allocated modules with<br>demonstration, code walkthrough and questions.|



The course specification states that the team presents in Week 7 and submits by 11 December 2026, 11:59 PM. Assignment/presentation details should be confirmed against current tutor instructions. 

### **10.4 Recommended staged plan for integration** 

|**Stage**|**Deliverables**|**Owners / dependency**|
|---|---|---|
|0 – Foundation|Approved module boundaries, database<br>shared keys/FKs, app shell,<br>auth/session/role checks, fictional seed<br>data.|All, TAN leads auth; shared schema sign-<br>off.|
|1 – Eight CRUD foundations|All individual<br>Create/Retrieve/Update/Delete operations<br>with working validation and history-safe<br>Delete actions.|TAN, JAY, IVAN, YONG each own two<br>modules.|
|2 – Core event/booking chain|Venue<br>Event<br>Session<br>Category<br>→<br>→<br>→<br>→<br>Booking<br>Ticket/Payment simulation<br>→<br>flows.|TAN<br>IVAN<br>YONG<br>JAY.<br>→<br>→<br>→|
|3 – Unique functions|Customer Rewards; Refund Policy<br>Interface; Personalised Event<br>Recommendation; MFA; exact<br>feature approval by tutor required.|Each respective member.|
|4 – Shared operations|Support integration, staff shifts, QR first<br>check-in, notifications and role<br>dashboards.|Shared code ownership agreed in<br>repository.|
|5 – Verification|Per-process activity diagrams, detailed<br>test cases, screenshots,<br>ERD/3NF/dictionary, UI mockups, joint<br>UAT.|All; one integrator reviews cross-module<br>contracts.|
|6 – Submission|Complete source, database,<br>documentation and final local<br>demonstration.|Team review before 11 Dec 2026 11:59<br>PM.|



### **10.5 Project directory suggestion (P)** 

Use modular PHP rather than page-by-page mixed SQL and HTML, with a shared public/front-controller and roleaware middleware. Example roots: app/Controllers, app/Services, app/Repositories, app/Views, app/Middleware, config/, database/migrations, database/seeds, storage/private_uploads, storage/logs, storage/email_outbox, public/assets, tests/. In production, expose public/ only through web server root and protect secrets and private files. 

T4Y  •  Software Requirements Specification v2.0   |   Page 48 

**TICKETS4YOU  /  SICC** 

## **11. Outstanding decisions, dependencies and sign-off** 

### **11.1 Confirmed decisions not to reopen** 

|**Topic**|**Final confirmed rule**|
|---|---|
|Assessed modules|Exactly 8: TAN UAM/Venue; JAY Booking/Category; IVAN<br>Support & Dispute/Event; YONG Session/Staff Scheduling.|
|Ticket/payment boundaries|Under Booking Management, not standalone assessed modules.|
|SICC venue scope|SICC events only.|
|Quantity limit|Per-order only across all categories; Admin or delegated role<br>configures; repeat orders allowed.|
|Tickets|One unique QR per individual ticket; one shared booking<br>reference per order.|
|Informal sharing|Individual screenshot may be given to another attendee; does<br>not change entitlement or protect from duplicate/invalid use.|
|Formal transfer|Admin-enabled per event; verified recipient email link, no<br>account required; token replacement and ownership update.|
|First admission|Count each ticket once; duplicate first admissions rejected.|
|Physical re-entry|May be SICC-supervised but NOT recorded or validated by T4Y.|
|Inactivity|Admin/Staff 60 minutes; Customer/Organiser 30 minutes; no<br>additional absolute maximum.|
|Refund policy|Exact 31/16/8-day tier, valid request timestamp, versioned<br>policy, cancellation full paid+customer fees including checked-<br>in.|
|Email failure|Retry 1/5/15/60 min; Admin alert after unsuccessful attempts;<br>business transaction unaffected.|
|Language|English, Bahasa Melayu, Simplified Chinese, Traditional<br>Chinese.|
|Payment prototype|Simulated, with future real FPX/cards/e-wallet integration<br>architecture.|
|Records|Retain necessary audit/financial history; anonymise/delete<br>eligible personal information.|
|Staff grace|30 minutes before/after shifts, configurable by Admin.|



### **11.2 Pending tutor approval and production-only policy values** 

|**Item**|**Status / owner**|**Impact / suggested action**|
|---|---|---|
|Unique features and exact Delete<br>demonstration for retained records|Tutor approval required (P)|Obtain tutor sign-off that<br>deactivation/archival/voiding meets<br>CRUD assessment requirements.|



T4Y  •  Software Requirements Specification v2.0   |   Page 49 

**TICKETS4YOU  /  SICC** 

|**Item**|**Status / owner**|**Impact / suggested action**|
|---|---|---|
|Shared dashboard/QR/notification code<br>ownership|Team coordination required (P)|Designate explicit implementer/reviewer,<br>avoid unassigned feature.|
|Full ERD, foreign keys, 3NF data<br>dictionary|Design deliverable pending|Freeze cross-module key contracts<br>before independent CRUD work.|
|SICC data-retention periods and deletion<br>rights|Production policy outstanding (PP)|Formal review by SICC/legal/privacy<br>team; no fixed years assumed.|
|Live payment/email provider and<br>merchant integration|Future production choice (PP)|Select only when moving beyond<br>simulation/outbox; harden and test.|
|Absolute session lifetime vs inactivity-<br>only design|Confirmed prototype; production<br>security risk (PP)|Do not add absolute timeout without<br>approval; revisit privileged production<br>accounts.|
|Physical supervised re-entry SOP|SICC operations outside software (PP)|SICC defines wristband/re-entry<br>procedure; no digital tracking in T4Y.|
|Precise support response target per<br>priority|Admin configuration required|Keep priorities and configurable targets;<br>concrete hour values not given.|
|Performance throughput, uptime,<br>recovery targets|Non-functional acceptance values<br>pending (PP)|Measure prototype and agree<br>measurable deployment goals.|
|Popular-events velocity time window /<br>ties|Implementation precision pending (P)|Document calculation floor, review<br>refunds treatment and deterministic<br>ranking.|
|Chinese translation reviewer and<br>completeness|Operational policy pending|Admin approves core locale strings;<br>approved original-language event<br>fallback.|
|PDF report vs Excel|PDF is baseline; Excel support has<br>been discussed but requires final<br>team/tutor confirmation before sign-<br>off.|Baseline includes PDF; separately<br>approve any Excel/CSV expansion.|
|Payment status and refund reversal<br>provider mappings|Future integration detail|Define actual provider codes later, keep<br>independent internal state models.|
|Acceptance of unregistered guest link<br>after repeated expiry|Implementation detail (P)|Verify email per retrieval, issue expiring<br>links and prevent enumeration.|
|Pricing category edits after sale start|Confirmed locked|Do not add new post-start<br>categories/prices without new user<br>approval.|



### **11.3 Sign-off checklist** 

- ☐ Tutor confirms final eight assessed module titles and four candidate unique non-CRUD functions. 

- ☐ Tutor confirms safe Delete demonstrations for committed financial/booking/event records. 

- ☐ Team signs shared database keys, event/session relationship and module ownership contracts. 

- ☐ Each developer identifies 4 CRUD functions, validations, diagram and valid/invalid test evidence in each assigned module. 

T4Y  •  Software Requirements Specification v2.0   |   Page 50 

**TICKETS4YOU  /  SICC** 

- ☐ Joint owners assigned to shared dashboard, QR first-entry workflow, notifications and platform monitoring. 

- ☐ SICC-specific retention and real production integrations remain explicitly listed as future policy decisions, not assumed complete. 

## **Appendix A. Requirement audit and process checklist** 

### **A.1 Minimum process diagram inventory for assessed modules** 

|**Member**|**Module**|**Minimum process/diagram candidates**|
|---|---|---|
|TAN|UAM|Create account, retrieve/search<br>users, update profile/role/email,<br>delete/deactivate account; customer<br>rewards extra feature in UAM.|
|TAN|Venue|Create space/layout, retrieve/search<br>availability, update venue, delete/archive<br>venue.|
|JAY|Booking|Create booking/pay/issue tickets,<br>retrieve bookings, update<br>cancellation/payment status, delete<br>unpaid draft/release hold; refund<br>policy interface extra feature.|
|JAY|Category|Create category, retrieve categories,<br>update allowed pricing/allocations,<br>delete/archive category.|
|IVAN|Support & Dispute|Create case/refund request, retrieve<br>cases, update decision/priority,<br>delete/archive eligible draft;<br>personalised recommendation extra<br>feature.|
|IVAN|Event|Create application/event, retrieve events,<br>update approval/publication,<br>delete/archive draft event.|
|YONG|Session|Create session, retrieve sessions, update<br>scheduled session, delete/cancel draft<br>session.|
|YONG|Staff Scheduling|Create shift, retrieve roster, update<br>shift, delete/cancel shift; MFA extra<br>feature shared with TAN<br>authentication.|



T4Y  •  Software Requirements Specification v2.0   |   Page 51 

**TICKETS4YOU  /  SICC** 

### **A.2 Dependency-critical integration sequence** 

1. UAM authorisation + Venue + Event prerequisites → 2. Session → 3. Category and pricing → 4. Booking holds/payment/ticket issuance → 5. Support refund and dispute outcomes → 6. Scheduling/QR shared operations → 7. Dashboards/notifications/reporting → 8. integration regression and documentation. 

### **A.3 Traceability guide** 

Module requirement prefixes: UAM (User Account), VEN (Venue), BKG (Booking including tickets/payments), CAT (Category), SUP (Support/Dispute), EVT (Event), SES (Session), SCH (Staff Scheduling). Shared prefixes: NTF (notification), QR (check-in), DASH (dashboard), OPS (platform operations), NFR (security), QAL (quality/reliability). Requirement-to-test links should be recorded in the test documentation and kept stable when code changes. 

T4Y  •  Software Requirements Specification v2.0   |   Page 52 

**TICKETS4YOU  /  SICC** 

## **12. Change control and approved extra-feature allocation (19 Nov 2026)** 

### **12.1 Revision authority and supersession** 

This Section 12 onwards amends the original v2.0 requirement baseline. If any earlier heading, table or module unique-function proposal conflicts with this addendum, Sections 12-19 take precedence. The eight assessed modules do not change. The four new extra features supersede the earlier RBAC/holds/refund-calculator/shift-conflict uniquefeature candidates; those earlier behaviours remain core requirements where applicable. 

|**Member**|**Assessed module 1**|**Assessed module 2**|**Assigned extra feature**<br>**(ONE integrated feature per**<br>**student)**|
|---|---|---|---|
|**TAN**|User Account Management|Venue Management|Customer Rewards: loyalty<br>points, bundle offers and<br>promo codes|
|**JAY**|Booking Management<br>(ticket/payment functions)|Category Management|Refund Policy Interface|
|**IVAN**|Event Management|Customer Support & Dispute<br>Management|Personalised Event<br>Recommendation|
|**YONG**|Session Management|Staff Scheduling Management|Multi-Factor Authentication<br>(MFA)|



The four-member team is lecturer-approved. Exact extra-feature acceptance and any assessment treatment of nondestructive Delete operations should still be signed off by the tutor. Each of the eight assessed modules retains Create, Retrieve, Update and Delete, complete validation, and integration requirements (AMCS2194 Assignment Specification, September 2026, pp. 2-4). 

### **12.2 Source classification** 

|**Classification**|**Meaning**|**Examples**|
|---|---|---|
|**Confirmed**|Chosen in the requirements interviews<br>or latest team allocation|SICC-only venue scope; per-order limits;<br>individual QR; the names and owners of<br>the four extras|
||Thil  d h|Rewards earning ratio; bundle scope|
|**Proposed design**|ecnca strategy suggeste ere, not<br>separately chosen by the user|limited to one session; MFA enrolment<br>policy|
|||MFA required roles, rewards monetary|
|**Production / tutor sign-off**|Requires policy or assessment authority|value, data retention durations, unique-<br>feature approval, payment provider|



### **12.3 Assessed CRUD remains independent of extras** 

|**Module**|**Create**|**Retrieve**|**Update**|**Delete / archive**<br>**safeguard**|
|---|---|---|---|---|
|**UAM**|Create verified<br>customers / Admin-<br>created staff|Users/role assignment<br>search|Profiles, role links,<br>status, verified email|Delete eligible data or<br>deactivate preserving<br>necessary<br>audit/transactions|
|**Venue**|Add space/layout|Search<br>room/layouts/capacity|Update venue and<br>facility settings|Archive unused venue;<br>block deletion with<br>reservations|
|**Booking**|Create<br>order/holds/tickets/pay<br>ment attempt|View bookings,<br>receipts, tickets|Change legal order<br>states; valid<br>transfer/payout|Remove draft/cancel<br>safely; retain paid<br>transaction history|



T4Y  •  Software Requirements Specification v2.0   |   Page 53 

**TICKETS4YOU  /  SICC** 

||||outcomes||
|---|---|---|---|---|
|**Category**|Create ticket category,<br>pricing / policy<br>versions|Review categories, tier<br>schedules|Edit before sale; create<br>future policy version|Remove unused draft;<br>retain historic<br>reference|
|**Support & Dispute**|Open<br>support/refund/dispute|View statuses and<br>relies|Decision, priority,<br>assignment and|Delete eligible<br>draft/duplicate;|
||case|p|history|archive completed case|
||Submit|Search|Review ublish|Delete uncommitted|
|**Event**|alication/create||, p,|draft archive ticketed|
||pp<br>event draft|event/application|cancel, postpone|;<br>event|
|**Session**|Create independently<br>allocated session|View session calendar|Reschedule, capacity<br>within commitments|Remove draft/cancel<br>committed session<br>with history|
|**Staff Schedulin**|Create shift|View rosters|Reassign and adjust|Cancel shift and retain|
|**f g**|assignment||shift|audit|



T4Y  •  Software Requirements Specification v2.0   |   Page 54 

**TICKETS4YOU  /  SICC** 

## **13. Detailed extra-feature specifications and business workflows** 

### **13.1 IVAN - Personalised Event Recommendation (Event Management)** 

Purpose: suggest suitable published SICC events on the Customer Dashboard. Ownership: IVAN supplies recommendation rules and event interfaces; TAN owns user consent identity; JAY provides permitted booking/category signals; shared dashboard consumes ranked event IDs. This is not a ninth module. 

|**ID**|**Functional requirement / verification**|
|---|---|
|**REC-01**|Allow each customer to explicitly opt in before individual<br>browsing/booking history is used for personalised<br>recommendations. Withdrawal must stop further history-<br>based recommendations.|
|**REC-02**|Maintain an independent preference for promotional<br>recommendation EMAIL; opting in to dashboard<br>personalisation does not authorise promotional emails.|
|**REC-03**|Use only permitted category preferences, favourites and<br>opted-in event interactions/booking history; do not infer<br>sensitive attributes or expose individual history to organisers.|
|**REC-04**|Candidate events must be SICC-only, published, publicly<br>viewable, valid for display and appropriate to configured<br>session/category restrictions.|
||Ranking algorithm and weights must be documented and|
|**REC-05**|reproducible. Proposed prototype: deterministic category<br>affinity, favourites, valid prior booking interests and event<br>popularity.|
|**REC-06**|When consent is absent/withdrawn, show generic popular or<br>upcoming events without personal history profiling.|
||Suppress or clearly label cancelled, completed, expired,|
|**REC-07**|unapproved, otherwise unavailable events; recommendation<br>cards must not claim inventory is reserved.|
||Provide recommended event title, category, approved|
|**REC-08**|available session summary and an authorised event-details<br>link, with no unnecessary customer data.|
||Allow user to remove favourite, change preferences and|
|**REC-09**|provide negative feedback; reflect changes at the next<br>permitted refresh.|
||Support the four languages for UI labels and the approved-|
|**REC-10**|original event description as fallback where translation is<br>unavailable.|
||Record recommendation calculation version/consent|
|**REC-11**|provenance if stored; avoid saving inferred sensitive<br>categories and honour retention/erasure rules.|
||Apply role/event-scope checks: organisers may see aggregate|
|**REC-12**|recommendation outcomes only if expressly permitted; no<br>unrelated customer browsing history.|



#### **13.1.1 Recommendation data flow** 

53. Customer consents to history-based personalisation in UAM preferences, separately from marketing email optin. 

54. IVAN reads approved upcoming Event and Session records, category labels and public visibility states. 

55. Only for consented customers, obtain least-necessary permitted booking/favourite/interaction signals from JAY/TAN services. 

T4Y  •  Software Requirements Specification v2.0   |   Page 55 

**TICKETS4YOU  /  SICC** 

56. Generate, filter and sort candidate event IDs; apply publication, visibility, age-eligibility and validity filters. 

57. Customer Dashboard displays cards. Booking still checks live inventory at checkout; no recommendation reserves inventory. 

58. On opt-out, stop reading personalised signals and display generic alternatives; purge or cease processing retained signals according to policy. 

#### **13.1.2 Inputs, outputs and validation** 

|**Input**|**Validation / output**|
|---|---|
|**consent_state**|Must be active explicit opt-in for purpose =<br>HISTORY_BASED_RECOMMENDATIONS; separate from<br>PROMO_EMAIL.|
|**user preference categories**|Must reference valid public event/category tags; no<br>sensitive/protected profiling dimensions.|
|**event / session catalogue**|Published and currently visible, not cancelled/archived;<br>future/eligible session.|
|**booking and browsing signal**|Scoped to consenting user; only permitted fields and retention<br>window.|
|**output ranked_event_ids**|Deterministic scores with fallback; dashboard sees authorised<br>public event information, not hidden score inputs.|



#### **13.1.3 ERD/UML effect** 

Add Consent (User 1:N Consent), UserPreference (User 1:N UserPreference), EventInteraction (User 1:N and Event 1:N EventInteraction), and RecommendationFeedback (User/Event 1:N). Event, EventSession, Category, Booking and User remain authoritative existing classes. A RecommendationEngine service may be described in design text, but the coursework UML class diagram requires complete attributes and does not require operations. 

#### **13.1.4 Accept/reject examples** 

|**Case**|**Expected**|
|---|---|
|**No consent**|Generic upcoming/popular SICC events only; no personalised<br>browsing signal use.|
|**Consent on / recently booked VIP music**|Eligible music event preference boosts recommendations; not<br>a guarantee of seats.|
|**Consent withdrawn**|Personalised retrieval stops; recommendation email remains<br>off unless separately opted in.|
|**Unpublished/outside-SICC event**|Never returned to customer recommendations.|



### **13.2 JAY - Refund Policy Interface (Category Management)** 

Purpose: an Admin-controlled interface to create, preview, validate and publish versioned refund templates and per-event/per-category overrides. JAY owns configuration and snapshots; IVAN owns support approval and eligibility assessment; JAY Booking handles simulated payout after approval. 

|**ID**|**Functional requirement / verification**|
|---|---|
|**RPI-01**|Admin may create a reusable default policy template with<br>non-overlapping refund tiers and fee refund flags.|
||Admin may set policy for an event and optional ticket|
|**RPI-02**|category, using documented precedence: category override,<br>otherwise event policy, otherwise approved default template.|
||Default ordinary tiers use exact elapsed time before the|
|**RPI-03**|specific session: >=31 days 100%; >=16 and <31 50%; >=8 and<br><16 25%; <8 days 0%.|
|**RPI-04**|Validate that tiers cover intended time domain, do not overlap|



T4Y  •  Software Requirements Specification v2.0   |   Page 56 

**TICKETS4YOU  /  SICC** 

||or leave unintended gaps, rates are 0-100 percent, and<br>boundaries use exact seconds or equivalent unambiguous<br>durations.|
|---|---|
|**RPI-05**|Preview refund amount by category and per-ticket actual paid<br>price after allocated discounts, plus refundable fees according<br>to the applicable policy.|
||An authorised Admin publishes an immutable version.<br>f|
|**RPI-06**|Existing purchased tickets remain bound to original effective<br>policy version; future purchases may use a new version.|
|**RPI-07**|Display policy and fee treatment before payment and retain<br>version reference in BookingItem/Ticket purchase snapshots.|
|**RPI-08**|Customer Support calculates eligibility at the valid request<br>submission timestamp and sends policy-derived amount to<br>authorised Finance/Admin approval.|
||Ordinary checked-in tickets are ineligible while event|
|**RPI-09**|continues; formal SICC cancellation overrides and refunds full<br>actual paid ticket amount and all customer-paid fees even<br>after check-in.|
|**RPI-10**|Refund approval and financial payout states are independent;<br>only completed refunds invalidate ticket and release eligible<br>capacity for resale.|
|**RPI-11**|Protect policy administration via UAM permission checks,<br>CSRF validation, audit logs and publication confirmation.|
|**RPI-12**|Support approved translations of public policy summaries; do<br>not change published customer rights retroactively.|



#### **13.2.1 Refund-policy publishing workflow** 

59. Admin selects default template or event/category override, defines tier boundaries, percentages and refundable fee rules. 

60. Interface validates cover/overlap, effective date, scope, ownership and price-policy compatibility. 

61. Preview shows exactly which tier applies to sample timestamps and paid amounts, including discount allocation. 

62. Admin publishes a new RefundPolicyVersion; policies referenced by existing orders remain immutable. 

63. Booking attaches the applicable version to each purchased ticket snapshot; Support consumes that version during refund review. 

64. Approval event instructs Booking financial payout; failed payout is tracked as Requires Action, not as case rejection. 

#### **13.2.2 Proposed policy data model** 

|**Entity**|**Important attributes / relationship**|
|---|---|
|**RefundPolicyTemplate**|template_id PK; name; created_by FK User; status;<br>default_fee_rule; applicable_currency.|
||policy_version_id PK; template_id FK; event_id FK; category_id<br>f|
|**RefundPolicyVersion**|FK nullable; version_no; effective_from; immutable published<br>state.|
|**RefundTier**|tier_id PK; policy_version_id FK; minimum_seconds;<br>maximum_seconds nullable; refund_rate DECIMAL(5,2).|
||booking_item_id PK; policy_version_id FK;|
|**BookingItem snapshot**|actual_paid_unit_amount; allocated_discount; allocated_fee;<br>version reference.|
|**RefundRequest**|request_id PK; ticket_id FK; submitted_at; eligible_amount;<br>policy_version_id FK; approval_status.|



T4Y  •  Software Requirements Specification v2.0   |   Page 57 

**TICKETS4YOU  /  SICC** 

###### **RefundTransaction** 

refund_txn_id PK; refund_request_id FK; amount, status, idempotency_key; payment-provider simulation status. 

#### **13.2.3 Acceptance examples** 

|**Test**|**Expected**|
|---|---|
|**Exactly 31 days before session**|100% ordinary ticket-price refund.|
|**Exactly 16 days before session**|50% ordinary ticket-price refund.|
|**Exactly 8 days before session**|25% ordinary ticket-price refund.|
|**7 days 23 hours before session**|0% ordinary ticket-price refund.|
|**Try overlapping 8-20 and 16-30 tiers**|Reject validation; do not publish.|
|**Change policy after paid purchase**|Old ticket uses frozen version; new orders use permitted<br>effective version.|
|**SICC cancels after check-in**|Full actual paid ticket amount plus all customer-paid fees.|
|**Refund payout fails**|Approved case retained; financial state Requires Action; no<br>double payment.|



### **13.3 TAN - Customer Rewards (one integrated feature)** 

Purpose: provide one coherent customer-rewards subsystem covering loyalty points, bundle purchase offers and promotional codes. TAN owns the reward programme and entitlement/offer configuration. JAY owns the checkout price snapshot, redemption application, seat holds and payment finalisation. These three subfeatures count as ONE proposed assessed extra function, subject to explicit tutor acceptance. 

|**ID**|**Functional requirement / verification**|
|---|---|
|**REW-01**|Associate a reward account with a verified customer account;<br>access limited to the owner and permitted Admin support.|
|**REW-02**|Provide a loyalty ledger of earning, redemption, reversal and<br>authorised adjustment entries; balances must be reproducible<br>from entries.|
|**REW-03**|Award loyalty value only for qualifying verified paid<br>bookings, not pending, unpaid, free or complimentary tickets.|
|**REW-04**|Configure earning rate, point value, expiry and eligibility<br>through Admin policy; no monetary rate has yet been<br>confirmed.|
|**REW-05**|Create promotion codes with event/category scope, validity<br>dates, maximum uses, optional per-user limits and controlled<br>percentage/fixed-value discounts.|
||Prevent reuse of expired, disabled, exhausted or incompatible|
|**REW-06**|promo codes and prevent duplicate redemptions on payment<br>retries.|
|**REW-07**|Allow bundle offers composed of eligible ticket categories for<br>the same event session (proposed constraint to respect one-<br>session-per-order booking rule).|
||Count every ticket unit inside a bundle toward the|
|**REW-08**|<br>configurable per-booking limit; no cumulative per-customer<br>purchase limit applies.|
|**REW-09**|Reserve bundle components transactionally so a partially<br>available bundle is not sold.|
|**REW-10**|Apply at most one discount benefit to an order unless a future<br>explicitly approved stacking policy changes this rule.|
|**REW-11**|Display discount name, pre-discount subtotal, discount<br>amount,separatelyitemised applicable fees and finalpayable|



T4Y  •  Software Requirements Specification v2.0   |   Page 58 

**TICKETS4YOU  /  SICC** 

||amount before payment.|
|---|---|
|**REW-12**|Allocate any bundle/promo discount to individual tickets in<br>purchase snapshots using decimal half-up rounding and a<br>residual-cent reconciliation.|
||On completed eligible partial refunds, reverse associated|
|**REW-13**|earned/redeemed reward value according to a documented<br>ledger rule; do not silently delete entries.|
||Event cancellation returns the actual amount originally paid|
|**REW-14**|including fees and updates related points or redemption states<br>without paying more than collected.|
||Promo/reward eligibility must be checked again at checkout|
|**REW-15**|commit under transactional locks; quoted pricing does not<br>guarantee a reservation.|
||Support audit history and prevent negative cash totals; a|
|**REW-16**|negative points balance after prior redemption requires a<br>separately approved recovery rule.|



#### **13.3.1 Proposed reward business process** 

65. Admin defines one approved reward programme and publishes loyalty rules, event/category promo codes or same-session bundle offers. 

66. Verified Customer selects tickets or an offer, respecting one event session per booking and total ticket quantity per booking. 

67. JAY requests a reward quote from TAN-owned Reward Service. TAN returns eligibility, capped value, expiration and version/quote reference. 

68. Checkout shows a single accepted discount option, allocated per ticket; customer confirms itemised payable amount. 

69. Booking creates a transaction-safe hold; price/promo limits are rechecked immediately before payment completion. 

70. After confirmed payment, the system records one idempotent redemption and an immutable reward earning entry, with the original price snapshots. 

71. For completed refunds or cancellations, financial settlement and reward reversal entries are linked without deleting the original ledger history. 

#### **13.3.2 Proposed rewards tables and relationships** 

|**Table**|**Primary fields / references**|
|---|---|
|**reward_programs**|program_id PK, title, earn_rate, redeem_rate, status,<br>effective_dates, created_by FK.|
|**reward_accounts**|reward_account_id PK, user_id FK UNIQUE, status; balance<br>derived from ledger.|
||ledger_id PK, reward_account_id FK, booking_id FK nullable,|
|**reward_ledger**|points_delta INTEGER, entry_type, idempotency_key UNIQUE,<br>created_at.|
|**promotions**|promotion_id PK, code_hash UNIQUE, event_id/category_id FK<br>nullable, type, value, max_redemptions, active_dates.|
|**promotion_redemptions**|redemption_id PK, promotion_id FK, booking_id FK UNIQUE<br>when applied, user_id FK, applied_amount DECIMAL, status.|
|**bundle_offers**|bundle_id PK, session_id FK, name, status, fixed_offer_price<br>DECIMAL nullable, allowed_dates.|
|**bundle_components**|component_id PK, bundle_id FK, category_id FK, quantity<br>INTEGER positive.|
|**order_discounts**|discount_id PK, booking_id FK, source_type, source_ref,|



T4Y  •  Software Requirements Specification v2.0   |   Page 59 

**TICKETS4YOU  /  SICC** 

amount DECIMAL, per_ticket_allocation_snapshot. 

#### **13.3.3 Explicit design boundaries / unresolved pricing rules** 

|**Issue**|**Baseline / unresolved status**|
|---|---|
||PROPOSED: one session only; cross-session/event bundles|
|**Bundle scope**|would contradict the one-session-per-booking requirement<br>and need new approval.|
|**Discount stacking**|Existing rule: one discount per booking; clarify whether<br>loyalty earning still occurs on a discounted purchase.|
|**Points values and expiration**|Admin-defined parameters; no earn rate or expiry period<br>selected yet.|
|**Negative points after refund**|Policy TBD; hold correction ledger entries and flag balance for<br>Admin instead of silently losing history.|
||Original purchaser remains payer and normal reward|
|**Rewards for already transferred ticket**|account; formal transfer does not shift reward ledger by<br>default.|



#### **13.3.4 Rewards acceptance examples** 

|**Case**|**Expected**|
|---|---|
|**Promo code used after its end time**|Reject with clear error; preserve checkout safely.|
|**Bundle has 2 VIP + 2 Standard, maximum 3 per booking**|Reject; all bundle units count in the same booking limit.|
|**Apply promo and loyalty redemption simultaneously**|Do not stack; show one chosen permitted benefit.|
|**Two checkout threads redeem last promo use**|One succeeds; the other fails transactionally without creating<br>duplicate discount.|
|**Successful purchase then one-ticket refund**|Reconcile its allocated discount and points reversal without<br>changing other tickets.|
|**SICC cancels event**|Refund actual money paid plus customer-paid fees; reward<br>ledger reverses according to policy.|



### **13.4 YONG - Multi-Factor Authentication (MFA)** 

Purpose: provide an additional, independently verifiable login factor integrated into TAN’s main authentication system. TAN retains responsibility for core credentials, password sessions, role permissions and account updates. YONG owns MFA enrolment, challenge, recovery and risk controls. MFA is an extra feature across the existing eight modules, not a new standalone module. 

|**ID**|**Functional requirement / verification**|
|---|---|
|**MFA-01**|Allow authenticated user to enrol an MFA factor through a<br>recent-password-verified, CSRF-protected setup procedure.|
||For the prototype, TOTP authenticator verification is the|
|**MFA-02**|recommended provider-independent factor; SMS dependency<br>is not presumed.|
||Require successful verification of the enrolment challenge|
|**MFA-03**|before marking the factor active; protect stored factor secrets<br>with authenticated encryption.|
||After successful password verification, place any user who|
|**MFA-04**|must complete MFA in a pending-MFA session with no<br>protected module access.|
||Validate time-based one-time codes, account/factor binding,|
|**MFA-05**|bounded time-window tolerance, brute-force attempt limits<br>and generic error responses.|
|**MFA-06**|Use single-use, securely generated and hashed recovery codes;|



T4Y  •  Software Requirements Specification v2.0   |   Page 60 

**TICKETS4YOU  /  SICC** 

||display recovery codes securely only at generation and<br>require re-verification to regenerate.|
|---|---|
|**MFA-07**|Log important MFA enrolment, success, failure, recovery and<br>removal events without recording codes, secrets or QR<br>provisioning contents.|
|**MFA-08**|Support verified lost-factor account recovery through<br>authorised identity-checking, with special handling for staff<br>and privileged accounts.|
|**MFA-09**|Do not let new sessions inherit older MFA approval and do not<br>reset inactivity timer via dashboard auto-refresh.|
|**MFA-10**|If enrolled MFA becomes required, deny password-only access<br>for that session unless a verified recovery flow succeeds.|
||Admin/Staff have 60-minute inactivity timeouts;|
|**MFA-11**|Customer/Organiser 30 minutes, consistent with UAM<br>baseline.|
|**MFA-12**|Treat initial activation and role enforcement (optional vs<br>mandatory) as an explicit tutor/product decision because<br>previous prototype policy allowed password-only Admin<br>login.|



#### **13.4.1 MFA authentication workflow** 

72. TAN AuthService checks email/Staff ID and password with existing rate limits, account status and role access. 

73. AuthService reads the current MFA enrolment/enforcement policy and asks YONG-owned MFAService whether the session needs a second factor. 

74. If MFA applies, create pending_mfa session. No booking, Admin, Staff or Organiser protected routes are available yet. 

75. MFAService challenges a registered factor; verify TOTP or one unused recovery code and enforce attempts/expiry. 

76. On success, promote the pending session to fully authenticated, regenerate session identifier and record audit metadata. 

77. On failure or expiry, remain unauthenticated for protected operations, show generic error and apply controlled retry/lockout. 

#### **13.4.2 Data model and permission boundary** 

|**Class/table**|**Required fields and links**|
|---|---|
|**mfa_factors**|factor_id PK, user_id FK, type, encrypted_secret, enabled_at,<br>status, key_version.|
|**mfa_challenges**|challenge_id PK, factor_id FK, nonce_hash, expires_at,<br>attempt_count, verified_at; no plaintext codes.|
|**mfa_recovery_codes**|recovery_code_id PK, factor_id FK, code_hash, used_at; single-<br>use.|
|**auth_sessions (TAN)**|session_id PK, user_id FK, mfa_state pending/verified/none,<br>last_human_activity_at, revoked_at.|
|**role_mfa_policy (proposed)**|role_id FK, enforcement_mode, policy_version; exact scope<br>needs confirmation.|



Secrets must never be passed to ordinary dashboard services. TAN owns login/session lifecycle and permission decisions; YONG exposes only MFA verification result and enrolment status. MFA recovery requests use the existing verified staff support flow. 

#### **13.4.3 Acceptance examples** 

|**Case**|**Expected**|
|---|---|



T4Y  •  Software Requirements Specification v2.0   |   Page 61 

**TICKETS4YOU  /  SICC** 

|**Correct password, enrolled MFA, no challenge**|Access to protected routes denied until MFA verification.|
|---|---|
|**Correct password + valid TOTP**|New session marked MFA-verified, access restricted by<br>normal UAM permissions.|
|**Wrong/expired code repeatedly**|Rate-limited challenge attempts; no secret/code leaks.|
|**Use recovery code twice**|First valid use succeeds; second use fails.|
|**MFA reset request for staff**|Separate identity verification and auditable recovery; no reset<br>based only on Staff ID/email.|
|**Password-only Admin with no enrolled MFA**|Allowed only if final prototype MFA enforcement policy<br>explicitly permits it.|



T4Y  •  Software Requirements Specification v2.0   |   Page 62 

**TICKETS4YOU  /  SICC** 

## **14. Cross-module integration contract and transaction boundaries** 

### **14.1 Ownership and requested data exchanges** 

|**Provider (owner)**|**Consumer**|**Contract / data returned**|**Security and consistency**|
|---|---|---|---|
|**TAN UAM**|YONG MFA|user_id,<br>pending_auth_session_id,<br>MFA applicability|Only password-verified<br>pending sessions; no<br>password hash sent to factor<br>service.|
|**YONG MFA**|TAN UAM|factor_verified boolean,<br>challenge_ref, audit reference|Never return TOTP seed or<br>recovery-code plaintext.|
|**TAN Rewards**|JAY Booking|reward quote: eligible source,<br>discount value, quote version|Revalidate under transaction;<br>one discount; prevent<br>duplicate redemption.|
|||payment-confirmed booking|Idempotency key per|
|**JAY Booking**|TAN Rewards|line amounts; refund-<br>completed ticket adjustments|booking/refund; ledger<br>append only.|
|**JAY Category / Policy**|IVAN Support|effective policy_version_id,<br>tiers, fee rules, visibility|Purchased ticket snapshot is<br>authoritative; no historical<br>overwrite.|
|||approved refund instruction,|Only authorised|
|**IVAN Support**|JAY Booking|ticket IDs, approved MYR<br>amount, idempotency key|Admin/Finance approval;<br>independent payout status.|
|**JAY Booking**|IVAN Event Rec|minimal permitted booking-<br>category interaction signals|Only for consented customer;<br>no raw payment<br>card/provider details.|
|||consent state, category|Stop personalised signals on|
|**TAN UAM**|IVAN Event Rec|preferences, active customer<br>ID|withdrawal; separate promo<br>email opt-in.|
|**IVAN Event Rec**|Customer Dashboard|ranked published SICC event<br>identifiers and displayed<br>explanation|No guarantee of stock;<br>dashboard rechecks visibility.|
|**YONG Sessions**|JAY Booking|session start, allocation, sale<br>window and current status|Read session in transaction<br>before inventory commit.|
|**YONG Staff Scheduling**|Shared QR gate|assigned event/session, staff<br>shift and grace windows|No access outside authorised<br>30-minute before/after grace<br>period.|
|**JAY Ticket Ledger**|Shared QR gate|individual token status,<br>session, first_admission_at|Live online check; no re-entry<br>activity tracking.|



### **14.2 Proposed internal service boundaries (PHP interfaces)** 

|**Service**|**Suggested method signature / response**|
|---|---|
|**AuthService (TAN)**|authenticatePassword(login, password) -><br>pending_or_authenticated_session; revokeAccess(user_id,<br>scope).|
|**MFAService (YONG)**|beginChallenge(user_id, pending_session_id) -> challenge_id;<br>verifyChallenge(challenge_id, code) -> verified/denied.|
||quoteReward(user_id, session_id, item_qty, offer) ->|
|**RewardService (TAN)**|quote_id/discount; commitReward(booking_id,<br>verified_payment_id) -> ledger_entry.|
|**RefundPolicyService (JAY)**|resolvePurchasedPolicy(ticket_id) -> immutable policy_version<br>and tier/fee rules; publishPolicy(admin, draft) -> version_id.|



T4Y  •  Software Requirements Specification v2.0   |   Page 63 

**TICKETS4YOU  /  SICC** 

|**SupportService (IVAN)**|submitRefund(ticket_ids, request_at) -> case_id/estimate;<br>approveRefund(case_id, actor) -> payout_instruction.|
|---|---|
|**PaymentService (JAY)**|processRefund(approval_id, idempotency_key) -><br>pending/succeeded/failed/needs_action.|
|**RecommendationService (IVAN)**|getRecommendedEvents(user_id, consent_context) -><br>eligible_event_ids; recordConsentChange(user_id, state).|
|**ShiftService (YONG)**|isStaffOnDuty(user_id, session_id, timestamp) -> true/false<br>under configured grace period.|



These are proposed method boundaries and must not be treated as final external HTTP API routes. In the PHP/XAMPP prototype they can be ordinary namespaced PHP services. Requests must validate CSRF where applicable, permission scope, immutable snapshots and idempotency. 

### **14.3 Transaction and failure invariants** 

#### **Booking / rewards / payment** 

- Hold and inventory allocation are transactionally validated against the current session/category capacity; unique seat claim prevents parallel double sale. 

- The booking remains one event session even for bundles; all component ticket quantities count toward perbooking limit. 

- A confirmed simulated payment issues exactly one unique QR per individual ticket; email queue failure cannot roll back a completed booking. 

- Reward redemption is committed once using payment/booking idempotency key; failure is logged for safe reconciliation rather than issuing duplicate discounts. 

#### **Policy / support / refund** 

- Ticket purchase references the policy version that existed at sale; Admin may publish a new version only for future applicability unless a lawful favourable exception is documented. 

- Valid refund submission freezes eligibility at its submission timestamp; Admin/authorised Finance Staff controls case approval. 

- Financial refund transaction remains independent; Failed/Requires Action needs safe retry and does not change the decision back to Rejected. 

- Only completed refund invalidates an individual ticket and releases eligible resale inventory when sales remain open. 

#### **Authentication / recommendations** 

- Pending MFA authentication grants no protected access until factor verification, subject to final enrolment/enforcement policy. 

- History-based personalisation requires explicit opt-in, and promotional email also requires its own separate opt-in. 

- Role changes/event revocations take effect immediately even on a logged-in session; user actions are checked on the server. 

### **14.4 Financial and policy examples** 

|**Input**|**System calculation**|
|---|---|
|**Discounted ticket list price MYR200, allocated promo**|Return actual ticket payment MYR180 plus customer-paid|
|**MYR20, fee MYR5, SICC cancels**|MYR5 fee = MYR185 (not pre-discount MYR205).|
|**Session begins 30 March 2027 20:00 MYT; request exactly 16**|Default ordinary percentage 50% of actual paid ticket-price|
|**days beforehand**|portion; fee policy separate.|
|**Four-ticket bundle with per-order max 3**|Block regardless of bundle promotional eligibility; do not split<br>silently into multiple booking orders.|



T4Y  •  Software Requirements Specification v2.0   |   Page 64 

**TICKETS4YOU  /  SICC** 

**One refunded item from three-ticket order** 

Revoke only the affected ticket QR, allocate line-item discount correctly, preserve other two tickets. 

T4Y  •  Software Requirements Specification v2.0   |   Page 65 

**TICKETS4YOU  /  SICC** 

## **15. ERD and UML class diagram specification: extra-feature impact** 

### **15.1 Unified logical entity model and group ownership** 

The whole-system ERD uses authoritative keys, foreign keys and immutable financial snapshots. The figure files delivered with this SRS show the master logical relationship view and smaller extra-feature relationship views. The final coursework ERD/3NF/data dictionary must capture ALL physical attributes and cardinalities, not merely the abbreviated diagram labels shown in the overview. 

|**Data owner**|**Core tables**|**Added/extended extra-feature tables**|
|---|---|---|
|**TAN**|users, user_roles, roles, permissions,<br>auth_sessions, venues, venue_layouts|reward_programs, reward_accounts,<br>reward_ledger, promotions,<br>promotion_redemptions, bundle_offers,<br>bundle_components|
|**JAY**|booking_orders, booking_items, tickets,<br>payment_transactions, ticket_categories,<br>checkout_holds|refund_policy_templates,<br>refund_policy_versions, refund_tiers,<br>order_discounts, refund_transactions<br>(existing feature extended)|
|**IVAN**|events, applications, event_translations,<br>support_cases, refund_requests|consents, user_preferences,<br>event_interactions,<br>recommendation_feedback|
|**YONG**|event_sessions, session_history,<br>staff_shift_assignments|mfa_factors, mfa_challenges,<br>mfa_recovery_codes, role_mfa_policy<br>(proposed)|
|**Shared team**|notifications, notification_deliveries,<br>audit_logs, checkins|Record<br>offer/policy/MFA/recommendation<br>events in shared audit/log surfaces|



### **15.2 Necessary ERD relationship additions** 

|**Source entity**|**Relationship**|**Target entity**|**Why**|
|---|---|---|---|
|**users**|1 : 0..1|reward_accounts|One customer has one<br>programme account when<br>enabled.|
|**reward_accounts**|1 : many|reward_ledger|Balance is sum of immutable<br>earning/redemption/reversal<br>rows.|
|**promotions**|1 : many|promotion_redemptions|Track finite usage and per-<br>user constraints.|
|**booking_orders**|1 : 0..many|promotion_redemptions|Prevent duplicate discounts<br>and preserve checkout audit.|
|**bundle_offers**|1 : many|bundle_components|One bundle contains<br>required category quantities.|
|||f|Bundle remains in one|
|**event_sessions**|1 : many|bundle_offers|ticketed session unless new<br>approval.|
|**booking_orders**|1 : many|order_discounts|Allocate applied reduction to<br>each individual ticket.|
|**users**|1 : many|consents|Track consent purpose,<br>version, grant/withdrawal.|
|**users / events**|1 : many / 1 : many|event_interactions|Only store permitted<br>interactions and references.|
|**users**|1 : many|mfa_factors|Multiple devices/factors|



T4Y  •  Software Requirements Specification v2.0   |   Page 66 

**TICKETS4YOU  /  SICC** 

||||possible; implementation<br>may constrain count.|
|---|---|---|---|
|**mfa_factors**|1 : many|mfa_challenges /<br>mfa_recovery_codes|Authentication recovery and<br>challenge traceability.|
|**refund_policy_versions**|1 : many|refund_tiers|Nonoverlapping refund<br>boundaries.|
|**booking_items**|many : 1|refund_policy_versions|Frozen purchase contract for<br>later refund.|
||||Audit payment attempts|
|**refund_requests**|1 : many|refund_transactions|independently of case<br>decision.|



### **15.3 Keys, normalisation and integrity** 

#### **Required database rules** 

- Use surrogate PKs (BIGINT or UUID) and explicit FK constraints; email and promo hashes are alternate unique keys, not primary references. 

- Use DECIMAL for money; integer for points. Avoid storing a redundant points_balance as the only source of truth; if cached, reconcile to ledger. 

- Separate bundle heading and bundle component lines (one-to-many) to avoid repeating category IDs or quantities in a single table field. 

- Keep each refund tier as its own row with minimum and maximum bounds; enforce non-overlap in application transaction validation and appropriate DB constraints. 

- Do not store raw MFA codes, TOTP secrets or full sensitive data in audit events; encrypt TOTP secret at rest with an externally managed key. 

- Keep recommendation consent and marketing-email consent separate by purpose; withdrawal must have a timestamp and immediate effect. 

- Use idempotency keys and uniqueness for successful order settlement, individual QR tokens, reward redemptions and refunds. 

- Define appropriate indexes for session_id/category_id, event ownership, booking order reference, active holds expiry, consent lookup and MFA challenge expiry. 

### **15.4 UML class diagram requirements (no methods required for coursework)** 

According to the AMCS2194 specification, the team must deliver ONE class diagram for the entire system; classes must include complete attributes, and operations/methods are not required for marking. The provided system UML diagram depicts the important persistent classes and associations. Finalise full attribute lists from the 3NF data dictionary rather than adding speculative class methods solely for diagrams. 

|**Extra feature**|**Class additions / UML associations**|**DFD process impact**|
|---|---|---|
|**Recommendation**|Consent, UserPreference,<br>EventInteraction,<br>RecommendationFeedback associated<br>with User, Event and Booking|Process 6.0 decomposes into 6.4<br>consented signals, 6.5 rank and 6.6<br>output/change notifications.|
|**Refund Policy Interface**|RefundPolicyVersion, RefundTier,<br>RefundPolicyTemplate associated with<br>Category, Event, BookingItem,<br>RefundRequest|Process 4.0 includes 4.3 tiers, 4.4 publish<br>version, 4.5 snapshot; 5.0 reads<br>immutable purchase terms.|
||RewardAccount, RewardLedger,|1.0 includes rewards|
|**Customer Rewards**|Promotion, PromotionRedemption,<br>BundleOffer, BundleComponent,<br>OrderDiscount with User,Booking,|configuration/ledger; 3.0 includes<br>reward quote and discount commit.|



T4Y  •  Software Requirements Specification v2.0   |   Page 67 

**TICKETS4YOU  /  SICC** 



<!-- Start of picture text -->
Session, Category<br>MFAFactor, MFAChallenge,  1.0 includes password stage then 1.4<br>MFA RecoveryCode + AuthSession.mfa_state  MFA validation before authenticated<br>with User and RoleAssignment access.<br><!-- End of picture text -->



<!-- Start of picture text -->
Tickets4You - Logical ERD (Master Entity Relationships)<br>Tor , — [Relehaigameat = 7 ~ =a >™~<br>ts GIN PR sien BIGINT PE<br>it VARCHAR UNIQUE a |west BGT se:us3: BIGINTFKBION sa |cerchalga Bu BIGINT ——sRami<br>sword VARCHAR lex eT ope ENUMCTOTR sen ak VARCHAR fargeremreilind<br>st ENUM cot IGINT PRNUL sr phere VARBINARY spe.ae DATETIME onsst DATETIME<br>ol yee a DATETIME prion vein AT eld. DATETIME snp coun NT<br><!-- End of picture text -->

**Figure 15-A. MFA data relationships (logical, abbreviated attributes).** 



<!-- Start of picture text -->
Teckets¥ou - Logical ERD (Master Entty Relationships)<br><!-- End of picture text -->

**Figure 15-B. Refund Policy Interface: version, tier and purchase snapshot relationships.** 

Separate full-resolution logical ERD, extra-feature ERDs, full UML class diagram and focused UML extension diagrams are supplied in the companion diagrams package. Their drawn entity columns are condensed for legibility; they are not substitutes for a physical data dictionary. 

T4Y  •  Software Requirements Specification v2.0   |   Page 68 

**TICKETS4YOU  /  SICC** 

## **16. Complete T4Y DFD: notation, balancing and subprocesses** 

### **16.1 DFD drawing notation** 

|**Symbol**|**Meaning**|**Rule applied in supplied diagrams**|
|---|---|---|
||External entity (Customer, Organiser,<br>f|External actors communicate only via a|
|**Rectangle**|Admin, Staff, Guest, Payment Simulator,<br>Email Service)|process; duplicated actor boxes are the<br>same external entity.|
||Transformation/process; Context|Verb phrase and stable process number;|
|**Numbered ellipse**|process 0; Diagram 0 processes 1.0-9.0;<br>Level 1 1.1, 1.2 etc.|9.0 shared services is NOT an assessed<br>ninth module.|
|||Only processes read/write stores. Never|
|**Two parallel horizontal lines**|Persistent data store D1-D11|connect external actor directly to a<br>store.|
|**Arrow with noun/data label**|Movement of data, e.g. booking details,<br>verified MFA result, policy version|No unlabeled flowchart YES/NO decision<br>arrows; DFD is not an activity flowchart.|



### **16.2 Context Diagram (Process 0)** 

The system boundary contains exactly one T4Y process. Customer/Visitor, Organiser, Admin, Staff/Finance, verified Guest Recipient, Payment Simulator/Provider and Email Service/Dev Outbox are the external entities. Their requests enter T4Y, and authorised event details, results, tickets, notices, payment instructions and delivery statuses cross the boundary. 



<!-- Start of picture text -->
cast<br>‘TAY DFD - Context Diagram (Process=0) as<br>~ ——<br>l| y<br>———<br>reel ee<br>“= —<br>ae ———<br>=F<br>= ae<br>Dewey rues ome SRR<br>esaie<br><!-- End of picture text -->

**Figure 16-A. Context Diagram - one whole-system process (full-resolution PDF/SVG also provided).** 

### **16.3 Diagram 0 (major process decomposition)** 

The eight coursework modules appear as numbered processes 1.0 User Accounts/MFA/Rewards, 2.0 Venue, 3.0 Booking/Ticket/Payment, 4.0 Category/Refund Policy, 5.0 Support/Dispute, 6.0 Event/Recommendation, 7.0 Session and 8.0 Staff Scheduling. Process 9.0 consolidates the shared QR admission, notification and dashboard transformations; this is an architectural decomposition, not an additional assessed module. 

T4Y  •  Software Requirements Specification v2.0   |   Page 69 

**TICKETS4YOU  /  SICC** 



<!-- Start of picture text -->
<£_» -—- a<br>Cee > cae<br>vsti Sas<br>se strate areca<br>= es<br>Dashoarae —[$$=——<br><!-- End of picture text -->

**Figure 16-B. Diagram 0 - eight assessed processes plus explicitly shared operation 9.0.** 

### **16.4 Diagram 1 decomposition list** 

|**DFD file**|**Subprocesses and special-feature scope**|
|---|---|
||1.1 Registration, 1.2 Role assignment, 1.3 Password auth, 1.4|
|**Diagram 1.1 - User Accounts**|MFA challenge (YONG), 1.5 Rewards (TAN), 1.6<br>Authorise/notify.|
|**Diagram 1.2 - Venue**|2.1 Space creation, 2.2 Layout/capacity, 2.3 Availability, 2.4<br>Reservation, 2.5 Update/archive.|
||3.1 Purchase eligibility, 3.2 Reward quote/price, 3.3 Hold, 3.4|
|**Diagram 1.3 - Booking**|Payment simulation, 3.5 QR issue, 3.6 Transfer/refund<br>settlement.|
|**Diagram 1.4 - Category**|4.1 Category, 4.2 Quota, 4.3 Refund tiers, 4.4 Published<br>immutable version, 4.5 Price/policy snapshot.|
|**Diagram 1.5 - Support**|5.1 Case, 5.2 Refund eligibility, 5.3 Approval, 5.4 Financial<br>instruction, 5.5 Failure resolution.|



T4Y  •  Software Requirements Specification v2.0   |   Page 70 

**TICKETS4YOU  /  SICC** 



<!-- Start of picture text -->
6.1 Application, 6.2 Authority/venue, 6.3 Publication, 6.4<br>Diagram 1.6 - Event<br>Consented signals, 6.5 Recommendations, 6.6 Changes.<br>7.1 Create, 7.2 Venue check, 7.3 Capacity, 7.4 Schedule change,<br>Diagram 1.7 - Session<br>7.5 Cancel/archive.<br>ed<br>8.1 Shift, 8.2 Access validation, 8.3 Conflict check, 8.4 Save/edit,<br>Diagram 1.8 - Staff Schedule<br>8.5 Cancel/notify.<br>9.1 Queue notifications, 9.2 Retry, 9.3 Online QR, 9.4 Unique<br>Diagram 1.9 - Shared<br>checkin, 9.5 KPI aggregation, 9.6 export/audit.<br><!-- End of picture text -->

**Figure 16-C. Diagram 1.3 - Booking, Rewards quote, Holds, Payment, QR and financial exceptions.** 

### **16.5 Cross-level balancing and extra-feature traceability** 

|**Context external flow**|**Diagram 0 parent transformation**|**Diagram 1 realization / data stores**|
|---|---|---|
|**Customer credentials / security alerts**<br>|1.0 Accounts; 9.0 shared messages<br>|1.3 validates password; 1.4 MFA; D1<br>auth/D11 factor; 9.1/9.2 messages.<br>|
|**Customer booking / ticket delivery**<br>|3.0 Booking; 9.0 message service<br>|3.1-3.5; D3 orders/tickets/payments; 9.1<br>email job.<br>|
|**Customer preference /**<br>**recommendation**|6.0 Event; 1.0 consent/account|6.4-6.5 use D9 consent/events + approved<br>booking signals; return event IDs.|
|**Admin venue / event / session**<br>**approvals**|2.0 Venue; 6.0 Event; 7.0 Sessions|2.3-2.4 -> 6.2-6.3 -> 7.2-7.4; D2/D6/D7.|
|**Admin category and refund policy**<br>~~P~~|4.0 Category<br>|4.3-4.4 write D4 immutable version; 5.2<br>later reads purchased policy.<br>~~d~~|
|**Customer support / refund case**<br>|5.0 Support; 3.0 financial settlement<br>|5.1-5.5; D5 case, D4 policy, D3 payment<br>and ticket states.<br>|
|**Admin staff shifts / staff checkin**|8.0 Scheduling; 9.0 shared QR|8.2-8.4 D8 shift; 9.3 live QR uses D3 ticket<br>state.|
|**Guest transfer acceptance / ticket**<br>**receipt**|3.0 Booking/Transfer|3.6 validates verified guest email;<br>changes ticket owner and QR; return<br>guest ticket link.|
|**Payment simulator result**<br>~~P~~|3.0 Booking/Payment<br>|3.4 uses unique payment reference;<br>updates order/QR 3.5.<br>~~o~~|
|**Email provider acceptance/failure**<br>|9.0 shared notifications<br>|9.1-9.2 D9 queued messages/retries, alert<br>Admin after 1/5/15/60 min delays.<br>|



Balancing note: Processes 1.0-9.0 cover the external system requests and outputs of Process 0. Detailed Level 1 figures intentionally show representative primary flows; integration contract tables above list the additional authorised data exchanges needed in the full implementation. Final assessment diagrams should be reviewed by the tutor for exact balancing and may repeat external actors to avoid crossing lines. 

T4Y  •  Software Requirements Specification v2.0   |   Page 71 

**TICKETS4YOU  /  SICC** 

## **17. Non-functional controls, security and detailed cross-feature tests** 

### **17.1 Security and privacy controls** 

|**Control**|**Required behaviour**|
|---|---|
|**Authentication**|Secure password hashing/verification, sessions, role/event<br>checks on server, single active login account session, 60/30-<br>minute inactivity.|
|**MFA**|Pending challenge state cannot bypass login; encrypted factor<br>secrets; hashed single-use recovery codes; rate limits.|
|**Financial data**|MYR DECIMAL and documented HALF_UP at two decimal<br>places; idempotent payment/refund/reward postings.<br>l|
|**Customer consent**|Separate opt-in flags for personalised history and promotional<br>email; default generic recommendations.|
|**Refund policy**|Immutable version linked to paid item; eligibility frozen at<br>valid request timestamp; full SICC cancellation override.|
|**QR checkin**|Unique QR per ticket, live first admission once; no T4Y<br>physical exit or re-entry activity records.|
|**Notification reliability**|Booking valid even if mail fails; retry after 1/5/15/60 minutes<br>then Admin alert; secure customer dashboard access.|
|**Uploads**|PDF/JPG/PNG up to recommended 10MB, MIME/content<br>verification, private storage, virus-check option and<br>authorised download.|
|**Audit and retention**|Who/what/when without secrets; preserve legal transaction<br>history, anonymise eligible data; exact periods not selected.|
|**Localisation**|English, Malay, Simplified Chinese, Traditional Chinese;<br>Admin review; approved original event text fallback.|
|**Automation**|Windows Task Scheduler/cron for queue/expiry; transaction<br>checks still enforce inventory at request time.<br>i|
|**Admin operations**|Separate refund approval from financial completion; monitor<br>failed jobs, delivery, backups, critical alerts.|



### **17.2 Integrated extra-feature acceptance test matrix** 

|**Test ID**|**Test scenario**|**Expected result**|
|---|---|---|
|**IAT-01**|Customer has not opted into history-<br>based recommendations|Customer Dashboard shows generic<br>SICC events only.|
|**IAT-02**|Consent opted in but promotional email<br>not opted in|Personalised dashboard cards appear;<br>no recommendation marketing email.|
|**IAT-03**|Create refund policy with overlapping<br>time bands|Interface rejects publication and<br>identifies overlapping bands.|
|**IAT-04**|Purchase item under version 1, then<br>Admin publishes version 2|Original item still uses version 1 when<br>refund requested.|
|**IAT-05**|Apply loyalty redemption and a promo<br>simultaneously|Single-discount policy enforced, amount<br>never negative.|
|**IAT-06**|Bundle includes more than configured<br>total tickets per booking|Entire booking blocked, irrespective of<br>price promotion.|
|**IAT-07**|Two customers race for last seat and<br>promo redemption|Only valid inventory/usage is<br>committed, no oversell/duplicate<br>redemption.|
|**IAT-08**|Successfully pay, then email delivery<br>fails|Paid order/tickets/rewards remain valid,<br>retry scheduled.|



T4Y  •  Software Requirements Specification v2.0   |   Page 72 

**TICKETS4YOU  /  SICC** 

|**IAT-09**|MFA-enrolled user enters correct<br>password but skips factor|Protected<br>Admin/Staff/Customer/Organiser page<br>blocked while MFA pending.|
|---|---|---|
|**IAT-10**|MFA recovery code reused|Second use rejected; audit and rate<br>limits apply.|
|**IAT-11**|Checked-in paid ticket with event<br>continuing requests refund|Ordinary refund rejected.|
|**IAT-12**|SICC cancellation after ticket check-in|Full actual paid ticket price and all paid<br>fees, even after admission.|
|**IAT-13**|Refund one discounted bundle<br>component|Only that ticket invalidated, allocated<br>actual paid amount refunded; reward<br>reversals consistent.|
|**IAT-14**|Event/session postponed, staff shift<br>overlaps new date|Existing ticket validity preserved; shift<br>conflict flagged and notifications sent.|
|**IAT-15**|Guest receives screenshot but sender<br>entered first|Duplicate QR rejected; formal transfer<br>would have invalidated old token.|
|**IAT-16**|Staff physically supervises a re-entry|No re-entry record or analytics stored;<br>unique attendance unchanged.|



### **17.3 Coursework deliverables per member** 

Each student must provide CRUD tests (normal, invalid/boundary), separate activity diagrams for EACH process, and a tutor-approved additional feature demonstrating a distinct business function. In Chapter 2, the whole team submits Context, Diagram 0, Diagram 1, a complete whole-system ERD with 3NF dictionary, and ONE UML class diagram with full attributes (operations unnecessary). Refer to AMCS2194 Assignment Specification September 2026, pp. 2-9, and marking rubric October 2026. 

T4Y  •  Software Requirements Specification v2.0   |   Page 73 

**TICKETS4YOU  /  SICC** 

## **18. Open decisions, assumptions, sign-off and document delivery** 

### **18.1 Mandatory final confirmations** 

|**Priority**|**Open decision**|**Proposed handling if not yet chosen**|
|---|---|---|
|||Offer optional enrolled TOTP factor in|
|**High**|Does YONG MFA need to be mandatory<br>for any role during prototype?|prototype while password-only remains<br>for unenrolled accounts; no forced-role<br>requirement without explicit approval.|
||Can TAN’s loyalty + bundle + promo be|Demonstrate them as one integrated|
|**High**|counted as one unique coursework<br>feature?|Customer Rewards engine and get tutor<br>sign-off.|
|**High**|Can a bundle span multiple<br>sessions/events?|Default proposed one session only,<br>preserving confirmed booking order<br>scope.|
|**High**|Reward point conversion, earning,<br>expiry and negative points policy|Admin-configurable; obtain approved<br>sample values before implementing real<br>redemptions.|
|**High**|Should refund-policy interface<br>ownership remain JAY despite Support<br>consuming its terms?|Database and configuration JAY; case<br>decision IVAN; payout JAY (explicit<br>module contract).|
|**Medium**|Physical record Delete vs archive<br>accepted by lecturer?|Demonstrate true Delete on<br>unreferenced draft records, controlled<br>cancellation for retained records.|
|**Medium**|Export Excel in addition to confirmed<br>PDF|Feature requested in interview but old<br>master marked Excel unconfirmed;<br>obtain explicit team sign-off.|
|**Medium**|Precise data retention durations|SICC/legal policy before production;<br>prototype uses fictional data.|
|**Medium**|Recommender score formula/popularity<br>lookback window|Prototype may use documented<br>deterministic scoring; final weights<br>require IVAN/team review.|
|**Medium**|MFA recovery policy for privileged lost<br>factors|Require validated support identity check<br>and audit; design exact operator<br>workflow.|
|**Medium**|Payment provider, SMTP provider and<br>production SLA|Only simulated provider and<br>development email outbox required<br>now; production selections deferred.|
||i|Name a specific member to|
|**Medium**|Shared dashboard/QR/notification code<br>owner|implement/review shared operations,<br>avoiding unassigned cross-team<br>functionality.|



### **18.2 SRS / diagram file inventory** 

|**Document**|**Contents**|
|---|---|
|**Tickets4You_Complete_SRS_v3_0_Extra_Features_DFD.doc**<br>**x**|Complete v2 system SRS amended with all four extra-feature<br>specifications, DFD mapping, ERD/UML impact, interface<br>contracts and tests.|
|**Tickets4You_Complete_SRS_v3_0_Extra_Features_DFD.pdf**|Rendered reference copy of Word specification.|
|**T4Y_DFD_Complete_Diagrams.pdf**|11 full-resolution DFD pages: Context, Diagram 0, and Level 1<br>decompositions 1.1-1.9.|
|**T4Y_ERD_Complete_Diagrams.pdf**|Logical whole-system ERD, extra feature cross-cut and four|



T4Y  •  Software Requirements Specification v2.0   |   Page 74 

**TICKETS4YOU  /  SICC** 

||focused feature ERDs.|
|---|---|
|**T4Y_UML_Complete_Diagrams.pdf**|Whole-system attribute-only UML class diagram, full extra-<br>feature view and four focused class diagrams.|
|***.drawio, *.svg, *.png**|Editable DFD source files and viewable vector/raster exports<br>for diagrams.net or vector editors.|
|**T4Y_SRS_v3_Diagrams.zip**|All diagram files, documentation and PDF diagram books.|



Sign-off statement: confirmed module responsibilities and customer decisions take precedence over technical alternatives. Everything marked PROPOSED or PENDING is not yet a final product/business commitment. This baseline can guide parallel development now, with high-priority items closed before final coursework testing. 

T4Y  •  Software Requirements Specification v2.0   |   Page 75 

