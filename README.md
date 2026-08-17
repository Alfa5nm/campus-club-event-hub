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

If a database already exists, the installer asks before replacing it and creates a timestamped SQL backup first. Existing runtime uploads are preserved during application updates.

### Manual setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin and import `database/schema.sql`, followed by `database/seed.sql`.
3. Copy or link this folder into your active XAMPP installation as `htdocs\campus-club-hub`.
4. Visit `http://localhost/campus-club-hub/`.

The default connection is MySQL user `root` with an empty password. Change `config/database.php` if your XAMPP configuration differs.

## Demo accounts

- Student/executive: `amina@student.edu` / `Password123!`
- Student: `nafis@student.edu` / `Password123!`
- Administrator: `admin@campus.edu` / `Admin123!`

## Implemented scope

- Secure signup, login, logout, session regeneration, password hashing, CSRF protection
- Student signup restricted to verified-format `@g.bracu.ac.bd` email addresses
- Editorial, photography-led responsive interface with role-aware dashboards
- Club browsing, live filtering, and administrator/executive profile management
- Progressive membership requests, approvals, removals, and executive-role assignment with non-JavaScript fallbacks
- Event calendar/grid views, live filtering, inline registration, and authorized event management
- Normalized schema with mock data for later registration, attendance, certificate, feedback, announcement, notification, recommendation, and reporting work

Placeholder photography is stored locally under `assets/images`. Source and photographer details are documented in `docs/image-credits.md`.
