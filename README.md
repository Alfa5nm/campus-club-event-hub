# Campus Club & Event Hub

XAMPP-ready PHP and MySQL application for campus club, membership, executive-role, and event management.

## Setup

### Automatic Windows setup

Double-click `install.bat`. It will:

1. Find XAMPP on common Windows drives.
2. Confirm that PHP, MySQL/MariaDB, Apache, and phpMyAdmin are available.
3. Copy the project into XAMPP's `htdocs` directory.
4. Start Apache and MySQL.
5. Create and seed the database.
6. Open the application and phpMyAdmin.

If a database already exists, the installer can preserve it and apply the idempotent core-expansion migration, or create a timestamped SQL backup before resetting it. Existing runtime uploads are preserved during application updates.

### Manual setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin and import `database/schema.sql`, followed by `database/seed.sql`.
3. Copy or link this folder into your active XAMPP installation as `htdocs\campus-club-hub`.
4. Visit `http://localhost/campus-club-hub/`.

The default connection is MySQL user `root` with an empty password. Change `config/database.php` if your XAMPP configuration differs.

## Demo accounts

- Student/executive: `amina.rahman@g.bracu.ac.bd` / `Password123!`
- Student: `nafis.karim@g.bracu.ac.bd` / `Password123!`
- Administrator: `admin@campus.edu` / `Admin123!`

## Implemented scope

- Secure signup, login, logout, session regeneration, password hashing, CSRF protection
- Student signup restricted to verified-format `@g.bracu.ac.bd` email addresses
- Editorial, photography-led responsive interface with role-aware dashboards
- Club browsing, live filtering, and administrator/executive profile management
- Progressive membership requests, approvals, removals, and executive-role assignment with non-JavaScript fallbacks
- Event calendar/grid views, live filtering, inline registration, and authorized event management
- QR event passes plus executive QR/manual attendance with transactional status synchronization
- Automatic PDF certificate issuance, revocation, protected download, and public verification
- Club/system announcement publishing with one-time recipient fan-out and automatic expiry
- Paginated notification centre with unread badges and progressive mark-as-read actions
- Offline QR generation/scanning dependencies stored locally under `assets/vendor`
- Normalized schema with mock data for later feedback, recommendation, and reporting work

Placeholder photography is stored locally under `assets/images`. Source and photographer details are documented in `docs/image-credits.md`.

Local QR dependency sources and licenses are documented in `docs/dependency-credits.md`.

## Team documentation

The detailed ownership, architecture, demonstration, and viva guide for Faisal Mahbub, Rifat Mahmud, and Tarannum Diha is available in `docs/TEAM_FEATURE_DOCUMENTATION.md`.

A polished, illustrated Microsoft Word edition is available in `docs/CampusHub_Team_Feature_Documentation.docx`. Editable SVG sources for its architecture, schema, security-flow, and team-integration figures are stored under `docs/diagrams`.
