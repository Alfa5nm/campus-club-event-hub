# Campus Club & Event Hub

XAMPP-ready PHP and MySQL application for campus club, membership, executive-role, and event management.

## Setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin and import `database/schema.sql`, followed by `database/seed.sql`.
3. Copy or link this folder into `C:\xampp\htdocs\campus-club-hub`.
4. Visit `http://localhost/campus-club-hub/`.

The default connection is MySQL user `root` with an empty password. Change `config/database.php` if your XAMPP configuration differs.

## Demo accounts

- Student/executive: `amina@student.edu` / `Password123!`
- Student: `nafis@student.edu` / `Password123!`
- Administrator: `admin@campus.edu` / `Admin123!`

## Implemented scope

- Secure signup, login, logout, session regeneration, password hashing, CSRF protection
- Club browsing and administrator/executive profile management
- Membership requests, approvals, removals, and executive-role assignment
- Event browsing and authorized event creation, editing, cancellation, and deletion
- Normalized schema with mock data for later registration, attendance, certificate, feedback, announcement, notification, recommendation, and reporting work
