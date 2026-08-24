# Campus Club & Event Hub — Team Feature Documentation

## Document purpose

This document is the technical and presentation handoff for the three project members:

- **Tarannum Diha** — Club, membership, executive-role, and event management
- **Rifat Mahmud** — Event registration and participation lifecycle
- **Faisal Mahbub** — Dashboards, notifications, reporting, and engagement features

It is written so that each member can explain one independent feature area, demonstrate it live, answer database and security questions, and describe how their work connects to the other modules. Authentication and the shared application foundation belong to the project as a whole.

> Presentation rule: describe only the items marked **Implemented now** as working application features. Items marked **Schema-ready / next phase** are represented in the normalized database and specification but do not yet have complete user interfaces.

---

## 1. Project overview

CampusHub is a framework-free PHP and MySQL web application for BRAC University club activities. Students discover clubs, request membership, browse events, and register for events. Approved club executives manage their assigned clubs, members, and events. Administrators have system-wide visibility and management authority.

The application runs on XAMPP:

- Apache serves PHP pages.
- PHP handles sessions, validation, authorization, and business logic.
- MySQL stores normalized relational data.
- JavaScript adds live filtering, view preferences, AJAX actions, and feedback messages.
- Every AJAX operation retains a normal HTML form fallback.

### Current working URL

`http://localhost/campus-club-hub/`

### Technology stack

| Layer | Technology | Responsibility |
|---|---|---|
| Presentation | HTML5 and CSS3 | Editorial responsive interface |
| Browser behavior | Vanilla JavaScript | Search, filters, AJAX, view state, motion |
| Server | PHP 8 | Authentication, authorization, validation, CRUD |
| Database | MySQL / MariaDB | Normalized persistent data and constraints |
| Local environment | XAMPP | Apache, PHP, MySQL, phpMyAdmin |

---

## 2. Shared architecture all members should understand

### 2.1 Request flow

1. The browser requests a PHP page.
2. `includes/bootstrap.php` starts the session and database connection.
3. The page reads the current user from the session.
4. Prepared SQL statements retrieve only the required records.
5. PHP escapes output through `e()` before generating HTML.
6. A form submission includes a CSRF token.
7. PHP verifies the token, permissions, and business rules.
8. The database is changed only after validation succeeds.
9. The user receives either a flash message or a JSON response.

For progressive AJAX forms, `assets/app.js` sends the same form data to an endpoint under `api/`. The endpoint responds in this consistent shape:

```json
{
  "ok": true,
  "message": "Membership updated.",
  "data": {
    "action": "approve",
    "membership_id": 4
  }
}
```

The interface changes only after the server confirms success. If JavaScript is unavailable, the standard PHP form action still works.

### 2.2 Authentication and security foundation

Authentication is shared infrastructure rather than an individual showcase feature.

- New registration accepts only email addresses ending in `@g.bracu.ac.bd`.
- Email and student number are unique.
- Passwords are stored with PHP `password_hash()` and checked with `password_verify()`.
- Successful login regenerates the session identifier.
- Suspended or deactivated accounts cannot log in.
- State-changing forms require a CSRF token.
- SQL uses prepared statements rather than concatenating user input.
- Output is escaped with `htmlspecialchars()` through the `e()` helper.
- Authorization is checked on the server; hiding a button is never treated as permission.

Key files: `signup.php`, `login.php`, `logout.php`, `includes/bootstrap.php`, and `includes/functions.php`.

### 2.3 Role model

The application deliberately does not store “Club Executive” as a global user role.

- `users.role` contains `Student` or `Admin`.
- A student becomes an executive for a particular club through `club_membership.member_role`.
- Executive roles include Executive, President, Vice President, Secretary, and Treasurer.
- The membership must also be Approved and Active.

This allows one student to be President of one club, an ordinary Member of another, and have a Pending request in a third club.

The central authorization function is:

```php
can_manage_club(int $clubId): bool
```

It returns true for an administrator or for an approved, active executive membership in that exact club.

---

## 3. Database relationships

### 3.1 Core relational chain

```text
USERS 1 ── 0..1 STUDENTS
  │
  ├── 1 ── N CLUB_MEMBERSHIP N ── 1 CLUBS
  │                                      │
  │                                      └── 1 ── N EVENTS
  │                                                   │
  └── 1 ── N EVENT_REGISTRATION N ────────────────────┘
                       │
                       └── 1 ── 0..1 ATTENDANCE
                                      ├── 1 ── 0..1 CERTIFICATE
                                      └── registration ── 0..1 FEEDBACK
```

Additional relations:

- One user receives many notifications.
- One authorized user publishes many announcements.
- An announcement may optionally belong to one club.
- One club has many gallery photos.
- One student has many interests through `student_interest`.

### 3.2 Why associative entities are required

`club_membership` resolves the many-to-many relationship between students and clubs. It also stores facts about that relationship: approval, role, join date, and membership status.

`event_registration` resolves the many-to-many relationship between students and events. It stores registration status, cancellation reason, and timestamps. The nullable `qr_token` column remains only for backward compatibility and is not used by the current workflow.

These values do not belong solely to a student or solely to a club/event; they belong to the relationship between them.

### 3.3 Important database constraints

| Constraint | Business rule protected |
|---|---|
| `users.email UNIQUE` | One account per email |
| `students.student_number UNIQUE` | One account per student number |
| `club_membership(student_user_id, club_id) UNIQUE` | No duplicate membership relationship |
| `event_registration(student_user_id, event_id) UNIQUE` | No duplicate event registration relationship |
| `maximum_participants > 0` | Event capacity must be meaningful |
| deadline `<= event_date` | Registration cannot close after the event |
| one unique attendance per registration | Attendance cannot be recorded twice |
| feedback rating from 1 to 5 | Valid rating scale |
| unique certificate/verification numbers | Certificates remain identifiable |

The complete normalized model is available in `docs/campus_club_hub_normalized.drawio`, with executable definitions in `database/schema.sql`.

---

# 4. Tarannum Diha — Club, Membership, and Event Management

## 4.1 Individual feature statement

**“My feature area controls the club-management lifecycle: club discovery and profiles, membership requests and approvals, club-specific executive authority, and event CRUD for authorized executives.”**

This is an independent feature because it owns the relationship that turns students into club members/executives and uses that relationship to authorize event management.

## 4.2 Implemented now

- Visual club directory with database-backed counts
- Live club search and category filtering
- Student membership request
- Duplicate request prevention
- Personal membership-status view
- Executive membership queue
- Approve and reject actions
- Remove member action
- Assign and update executive roles
- Club profile create/update for authorized users
- Event create, read, update, cancel, and delete
- Club-scoped authorization for every management operation
- AJAX membership actions with normal form fallback
- Executive dashboard containing assigned clubs, pending requests, and capacity snapshots

## 4.3 Tables owned or heavily used

### `clubs`

Stores one record per club: name, description, category, contact details, image path, status, and timestamps.

### `club_membership`

Connects a student to a club. This is the authority table for membership and executive access.

Important state combinations:

| Approval | Membership | Meaning |
|---|---|---|
| Pending | Active | Waiting for executive review |
| Approved | Active | Current official member |
| Rejected | Active | Request rejected |
| Approved | Removed | Previously approved, later removed |
| Approved | Resigned | Student left voluntarily |

An executive is authorized only when approval is Approved, membership is Active, and the member role is an executive role.

### `events`

Every event contains `club_id`, so every event belongs to exactly one club. `created_by_user_id` preserves who created it.

## 4.4 Main PHP and AJAX flow

### Membership request

1. Student clicks **Request to join** on `clubs.php`.
2. JavaScript posts `club_id`, action, and CSRF token to `api/membership.php`.
3. The endpoint verifies that the session user exists in `students`.
4. It inserts a Pending/Active/Member relationship.
5. The unique student-and-club key rejects duplicate requests.
6. JSON confirms success and the card changes to Pending.

### Executive approval

1. Executive opens `memberships.php`.
2. The page lists only clubs returned by the executive membership query.
3. Approve posts a membership ID to the API.
4. The server obtains the related club ID.
5. `can_manage_club()` checks authority for that club.
6. Only then is the membership changed to Approved and Active.

### Event management

1. The event editor lists only manageable clubs.
2. The compact editor requires only club, title, date, venue, and positive capacity.
3. Creating publishes an Upcoming event with category `General`, start time `09:00`, deadline equal to the event date, and a short default description.
4. Editing changes only the five visible values and preserves other stored metadata.
5. Editing first checks authority against the event’s stored club.
6. Cancelling preserves the event record but changes its status; deleting remains available for administrative cleanup.

## 4.5 Files to know

- `clubs.php` — club listing, filtering, join request, club create/update
- `memberships.php` — personal status and executive management table
- `events.php` — event discovery and authorized event CRUD
- `api/membership.php` — AJAX membership operations
- `includes/functions.php` — executive role list and `can_manage_club()`
- `assets/app.js` — live filters and AJAX interface updates

## 4.6 Recommended live demonstration

1. Log in as an executive student.
2. Open Clubs and demonstrate instant search/category filtering.
3. Open Memberships and show the club-specific member queue.
4. Approve a pending member; explain that the row changes only after JSON success.
5. Change a member role and explain club-scoped executive authority.
6. Open Events and create a published event using the five-field form.
7. Attempt to explain why another club is not manageable.
8. Edit the event, then cancel it rather than deleting it to demonstrate status preservation.

## 4.7 Likely viva questions and answers

**Why is executive not stored in `users.role`?**  
Executive authority is club-specific. A global role would incorrectly give the person access to every club. The membership relationship captures a different role for each club.

**How do you prevent a student from joining the same club twice?**  
The application checks the action, while the database enforces a unique composite key on student and club. The database remains the final protection against concurrent duplicate requests.

**What prevents one executive from editing another club?**  
Every write calls `can_manage_club()` using the target club ID. It verifies an approved and active executive membership for that exact club.

**Why cancel an event instead of deleting it?**  
Cancellation preserves history and lets registrations or notifications reference the event. Deletion is reserved for mistakes or administrative cleanup.

## 4.8 Schema-ready / next phase

- Event poster upload processing (the path column and display are ready)
- Club logo upload processing
- Full gallery upload CRUD
- Rich registration-list export

---

# 5. Rifat Mahmud — Event Registration and Participation Lifecycle

## 5.1 Individual feature statement

**“My feature area safely connects students to events. It validates event status, deadlines and capacity, supports cancellation and re-registration, and feeds each confirmed event into the student dashboard and attendance roster.”**

The working independent feature now covers the complete participation chain: registration, executive roster attendance, status synchronization, certificate generation, protected download, revocation, and public verification.

## 5.2 Implemented now

- Browse database-backed events
- Search by event, club, venue, or category
- Category filtering
- Grid/list view with remembered browser preference
- Live capacity counts and indicators
- Registration deadline validation
- Maximum-capacity validation
- Cancelled/non-upcoming event validation
- Student-only registration
- Duplicate-safe registration and reactivation after cancellation
- Registration rows created without unnecessary check-in tokens
- Registration cancellation with stored reason
- Transactional registration and row locking
- Student dashboard list of confirmed upcoming events
- Progressive AJAX registration/cancellation with normal form fallback
- Direct event-roster display for every active registration
- One-click Present and Absent actions beside each student
- Club-scoped attendance marking and correction
- Transactional attendance and registration-status synchronization
- Automatic certificate PDF generation for Present attendance
- Certificate revocation when attendance changes to Absent
- Protected student/executive PDF download and public code verification

## 5.3 Tables owned or heavily used

### `event_registration`

This is the central associative entity between `students` and `events`.

| Column | Purpose |
|---|---|
| `registration_id` | Stable primary key |
| `student_user_id` | Registered student |
| `event_id` | Selected event |
| `registration_status` | Registered, Cancelled, Attended, or Absent |
| `qr_token` | Nullable legacy compatibility field; new registrations leave it empty |
| `cancellation_reason` | Audit detail for cancellation |
| `updated_at` | Last state-change timestamp |

### Downstream participation tables

- `attendance` has at most one row per registration.
- `certificate` has at most one row per attendance.
- `feedback` has at most one row per registration and requires attended participation by business rule.

This creates a traceable chain from person to event to attendance to certificate.

## 5.4 Transaction and concurrency flow

Registration is deliberately transactional:

1. Begin a database transaction.
2. Verify the current account is a student.
3. Select the event and count active registrations using `FOR UPDATE`.
4. Reject missing or non-Upcoming events.
5. Reject an expired registration deadline.
6. Reject an event at maximum capacity.
7. Insert the registration with no check-in token, or reactivate the existing cancelled relationship.
8. Commit only after every check succeeds.
9. Roll back if any validation or database operation fails.

`FOR UPDATE` matters when two students attempt to take the final available place simultaneously. Locking serializes the capacity decision inside the transaction.

## 5.5 Cancellation and re-registration design

Cancellation updates the existing row rather than deleting it. This preserves registration history and the cancellation reason. If the student later registers again while the event is eligible, the composite unique key routes the operation to `ON DUPLICATE KEY UPDATE`, which restores Registered status, clears the cancellation reason, and leaves the legacy token empty.

## 5.6 Files to know

- `events.php` — event UI and accessible server-side fallback
- `api/event-registration.php` — transactional AJAX endpoint
- `dashboard.php` — student’s confirmed upcoming events
- `assets/app.js` — registration button/count state changes
- `database/schema.sql` — registration, attendance, and certificate relationships

## 5.7 Recommended live demonstration

1. Log in as an ordinary student.
2. Open Events and switch between grid and list views.
3. Search for a venue or filter by category.
4. Register for an Upcoming event and show the count increment without page reload.
5. Open Dashboard and show that the event appears in Upcoming.
6. Return and cancel the registration; show the count decrement.
7. Explain the saved Cancelled row and cancellation reason.
8. Explain the deadline/full/cancelled validation cases using the event card fields and code flow.
9. Open Attendance, select the event, and mark a registered student directly from the roster.
10. Mark Present, download the generated PDF, and verify its public code.
11. Correct the record to Absent and show that the certificate becomes Revoked.

## 5.8 Likely viva questions and answers

**Why use a transaction?**  
Capacity checking and registration insertion must succeed as one unit. Otherwise two users could both observe the same final place and exceed capacity.

**How are duplicate registrations prevented?**  
The composite unique key on student and event allows only one relationship row. Re-registration updates a cancelled row rather than creating another.

**Why does the schema still contain `qr_token`?**
It remains nullable so existing XAMPP installations upgrade safely. New registrations do not generate or use tokens, and attendance targets the registration ID selected from the authorized event roster.

**Why keep cancelled registrations?**  
It preserves audit history, supports a cancellation reason, and allows controlled reactivation without duplicate records.

**What happens without JavaScript?**  
The normal form submits to `events.php`, which executes the same core checks and redirects with a flash message.

## 5.9 Next phase

- Registration detail/history export
- Bulk attendance import
- Feedback submission after Present attendance
- Optional email reminders

---

# 6. Faisal Mahbub — Dashboards, Notifications, Reporting, and Engagement

## 6.1 Individual feature statement

**“My feature area turns relational data into useful role-aware information. The same dashboard recognizes students, club executives, and administrators, then displays the statistics, alerts, queues, and actions appropriate to their authority.”**

The implemented independent feature combines the role-aware dashboard with a working notification centre and announcement publishing lifecycle. Advanced feedback CRUD, leaderboard formulas, and full reports remain next-phase modules.

## 6.2 Implemented now

- Role-aware Student, Executive, and Administrator dashboard branches
- Real database statistics rather than hard-coded totals
- Student upcoming-registration agenda
- Student membership total
- Latest user notifications with read/unread state
- Executive assigned-club count
- Executive pending membership queue
- Executive event capacity snapshots
- Administrator user, club, registration, and event totals
- Administrator club moderation queue
- Homepage database statistics, upcoming stories, activity, and calls to action
- Animated statistics, reveal motion, toast feedback, and reduced interface friction
- Seeded announcements and notifications for demonstration
- Two-field club and system announcement publishing with generated titles
- One-time transaction-protected notification fan-out to eligible recipients
- Paginated notification inbox, unread badge, individual read, and read-all actions
- Automatic certificate, attendance, and announcement notifications

## 6.3 Dashboard role decision

The dashboard calculates three conditions:

1. `is_admin()` checks the global Admin role.
2. An executive query checks for approved, active executive memberships.
3. If neither condition is true, the user receives the student dashboard.

Priority is Admin, then Executive, then Student. This ensures an administrator sees system-wide controls even if related data exists elsewhere.

## 6.4 Data aggregation examples

### Student view

- Counts approved and active memberships for the logged-in user.
- Joins registration, event, and club to show the user’s upcoming Registered events.
- Queries notifications by recipient and orders newest first.

### Executive view

- Determines all clubs the user can manage.
- Uses those club IDs in parameterized `IN` queries.
- Finds Pending membership requests for those clubs.
- Left-joins event registrations to display event capacity and registration volume.

### Administrator view

- Counts all users.
- Counts Active clubs and Pending clubs.
- Counts active registrations and current events.
- Retrieves non-Active clubs for the moderation queue.

These are derived reports. They are calculated from source tables rather than stored as duplicate totals, avoiding stale data.

## 6.5 Notifications and announcements model

`notification` belongs to exactly one recipient user and stores message, type, timestamp, and read state. This supports an in-application inbox without email or SMS.

`announcement` has one publisher and an optional club:

- `club_id = NULL` represents a system-wide announcement.
- A club ID represents a club-specific announcement.
- The simplified publisher activates new notices immediately; legacy status and expiry columns remain compatible.

The publishing desk asks only for audience and message, derives a concise title and notice type, activates the notice immediately, and records `notified_at` to prevent duplicate fan-out. The inbox supports paginated recipient-only reading, individual mark-as-read, read-all, and a live unread badge.

## 6.6 Files to know

- `dashboard.php` — all three role-aware dashboard branches
- `index.php` — public statistics and active campus content
- `announcements.php` and `api/announcement.php` — publishing lifecycle and one-time fan-out
- `notifications.php` and `api/notification.php` — recipient inbox and read state
- `database/seed.sql` — demonstration announcements and notifications
- `database/schema.sql` — notification, announcement, and feedback models
- `assets/app.js` — number animation, reveal motion, and toast messages
- `assets/style.css` — role-neutral editorial design system

## 6.7 Recommended live demonstration

Use separate browser sessions or log out between accounts.

1. Log in as a Student and show memberships, upcoming events, and notifications.
2. Log in as an Executive and show assigned clubs, pending members, and event capacity.
3. Log in as Administrator and show system totals and moderation queue.
4. Explain that the page is one controller with role-aware data branches, not three duplicated dashboards.
5. Return to Home and explain that statistics are queried from the live database.
6. Publish a club notice as an Executive and show its delivery in a member’s inbox.
7. Mark the notification read and explain how `notified_at` prevents duplicate fan-out after editing.

## 6.8 Likely viva questions and answers

**Why calculate reports instead of storing them?**  
Counts and rankings change whenever source records change. Calculating them prevents duplicated, stale values and follows normalization principles.

**How does the dashboard know someone is an executive?**  
It queries membership records for Approved, Active status and an executive role. Authority is derived from the relationship, not only from the user account.

**Why is `club_id` optional in announcements?**  
A system notice applies to everyone and has no single club. A club notice needs a club foreign key.

**What is the difference between an announcement and a notification?**  
An announcement is published content for an audience. A notification is a recipient-specific message stored for one user.

**How would the leaderboard work without a table?**  
An aggregate SQL query groups by club and calculates a score from event count, attendance, and average rating. The ranking is a derived report, not permanent data.

## 6.9 Next phase

- Feedback and rating submission/moderation
- Rule-based event recommendations from student interests
- Active-club leaderboard query and view
- Complete administrative reports and export

Suggested leaderboard formula from the specification:

```text
Activity Score = (Number of Events × 5)
               + Total Attendance
               + (Average Rating × 10)
```

---

## 7. Cross-feature integration

No member’s feature is isolated from the database workflow:

1. **Diha’s membership system** grants a club-specific executive role.
2. That authority allows an executive to create an event.
3. **Rifat’s registration system** allows eligible students to register for that event.
4. Registration data appears on the student and executive dashboards.
5. Executive attendance changes the registration to Attended or Absent in the same transaction.
6. Present attendance issues a certificate; a later Absent correction revokes it.
7. **Faisal’s engagement/reporting system** summarizes registrations, notifications, ratings, and club activity.

This is the project’s strongest integration story: authorization creates managed events, events create participation records, and participation records become engagement and reports.

---

## 8. Current feature-status matrix

| Module | Database | Working UI | CRUD/action coverage | Owner |
|---|---:|---:|---|---|
| Authentication | Yes | Yes | Signup/login/logout | Shared |
| Club profiles | Yes | Yes | Create/read/update | Diha |
| Memberships | Yes | Yes | Request/approve/reject/remove/role | Diha |
| Events | Yes | Yes | Create/read/update/cancel/delete | Diha |
| Event registration | Yes | Yes | Register/cancel/reactivate | Rifat |
| Attendance | Yes | Yes | Direct roster mark and correction | Rifat |
| Certificates | Yes | Yes | Issue/revoke/download/verify | Rifat |
| Notifications | Yes | Yes | Paginated read/read-all/fan-out | Faisal |
| Announcements | Yes | Yes | Audience/message publish, edit, remove | Faisal |
| Feedback | Yes | Not yet | Schema-ready | Faisal |
| Recommendations | Derived | Limited dashboard context | Next phase | Faisal |
| Leaderboard/reports | Derived | Dashboard summaries | Partial | Faisal |

---

## 9. Demonstration preparation

### 9.1 Reset predictable demo data

For a clean demonstration, import `database/schema.sql` followed by `database/seed.sql` in phpMyAdmin. This recreates the schema, so do it only when replacing the current demo database is acceptable.

### 9.2 Seeded accounts

| Persona | Email | Password | Useful demonstration |
|---|---|---|---|
| Executive student | `amina.rahman@g.bracu.ac.bd` | `Password123!` | Computing Club President |
| Student | `nafis.karim@g.bracu.ac.bd` | `Password123!` | Registration and membership request |
| Administrator | `admin@campus.edu` | `Admin123!` | Admin dashboard and all-club authority |

Fresh seed data uses BRACU-format student addresses, and newly created student accounts must use `@g.bracu.ac.bd`. Existing installations preserve their previously seeded login addresses when upgraded without reset.

### 9.3 Suggested presentation order

1. Shared introduction: problem, roles, technology, and normalized model.
2. Diha: club membership authority followed by five-field event creation.
3. Rifat: register, mark attendance from the event roster, download the certificate, and verify its public code.
4. Faisal: publish a club notice, show the one-time inbox delivery, then demonstrate read state and role-aware dashboards.
5. Shared conclusion: security, progressive enhancement, future modules.

### 9.4 Before presenting

- Start Apache and MySQL in XAMPP.
- Confirm the app URL works.
- Confirm seed accounts can log in.
- Use a wide browser window for the main demo, then briefly resize for responsiveness.
- Avoid changing seed passwords immediately before the presentation.
- Keep phpMyAdmin open on a second tab to show tables and relationships.
- Rehearse both successful operations and one rejected business-rule case.

---

## 10. General viva reference

### What normal form is the schema?

The relational design is intended to satisfy Third Normal Form. Repeating groups such as interests and gallery images are separated into child tables. Many-to-many relationships use associative tables. Non-key facts describe their table’s key and are not duplicated across unrelated entities.

### Why use foreign keys?

Foreign keys prevent orphan records and express ownership. Cascades remove dependent records where the parent no longer exists, while `SET NULL` preserves attendance if the executive membership that marked it is later removed.

### Why use both PHP and database validation?

PHP produces friendly business-rule errors. Database constraints provide the final integrity guarantee, including when simultaneous requests occur or another client bypasses the browser.

### What is progressive enhancement?

The base feature works with ordinary HTML forms and server redirects. JavaScript upgrades it to inline updates and toast feedback. Accessibility and reliability do not depend on JavaScript.

### What makes the interface dynamic?

The pages use live database counts, instant search and filters, remembered event view preference, asynchronous actions, capacity updates, role-specific dashboards, intersection-based motion, and clear success/error feedback.

### What are the main HTTP/API failure statuses?

- `400` — unknown or malformed action
- `401` — user is not signed in
- `403` — signed-in user lacks permission
- `405` — wrong request method
- `409` — business conflict such as duplicate/full/expired registration
- `419` — expired or invalid CSRF token
- `422` — invalid submitted role/value

---

## 11. Source map

| Path | Purpose |
|---|---|
| `database/schema.sql` | Complete normalized database definition |
| `database/seed.sql` | Mock users, clubs, memberships, events, and activity |
| `docs/campus_club_hub_normalized.drawio` | Visual normalized schema |
| `dashboard.php` | Student/executive/admin dashboard and module shortcuts |
| `attendance.php` / `certificates.php` / `verify-certificate.php` | Roster attendance, downloads, and verification UI |
| `announcements.php` / `notifications.php` | Publishing and recipient inbox UI |
| `api/membership.php` / `api/event-registration.php` / `api/attendance.php` | Membership and transactional participation JSON actions |
| `api/announcement.php` / `api/notification.php` | Fan-out and read-state JSON actions |
| `api/_json.php` | JSON, authentication, method, and CSRF contract |
| `assets/app.js` | Dynamic browser behavior |
| `assets/style.css` | Responsive editorial design system |
| `install.bat` | XAMPP discovery, deployment, database setup, and launch |

---

## 12. Ownership summary cards

### Tarannum Diha

**Showcase:** club-specific membership authority and event management.  
**Best proof:** approve a member, assign a role, and create/edit an event.  
**Core concept:** authorization derived through an associative entity.

### Rifat Mahmud

**Showcase:** registration roster, attendance marking, certificate generation, and public verification.
**Best proof:** select an event, mark a registered student Present, download the PDF, verify its code, then demonstrate revocation.
**Core concept:** one transactional participation chain from registration to verified certificate.

### Faisal Mahbub

**Showcase:** announcement publishing, one-time notification fan-out, inbox state, and role-aware dashboards.
**Best proof:** publish once, inspect recipient delivery/read state, then compare Student, Executive, and Administrator views.
**Core concept:** audience content becomes recipient-specific engagement without duplicate fan-out.
