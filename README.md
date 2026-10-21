# Document Management Portal (Portfolio Demo)

A PHP + MySQL demonstration app for department-wise document listing, role-based login, and PDF upload workflows. This public portfolio edition is anonymized and does not include real uploaded documents, original organization branding, a populated database dump, or pre-created users.

## Features

- Separate user and administrator login pages
- Department-wise document folders
- PDF upload form with file-size and extension checks
- Document metadata stored in MySQL
- Admin delete workflow scoped to the administrator's assigned department
- Responsive CSS layout

## Stack

- PHP 8+
- MySQL or MariaDB
- Apache (for example, a local XAMPP setup)

## Local setup

1. Install and start Apache and MySQL/MariaDB.
2. Create an empty database named `document_portal_demo`.
3. Import `database/schema.sql` into that database.
4. Review `db.php`. Its default `root` / empty-password settings are for a local XAMPP-style development environment only. For any other setup, provide `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` as environment variables. Do not put production credentials in source control.
5. Create at least one user and one administrator record locally. Generate a password hash in your terminal with:

   ```bash
   php -r "echo password_hash('CHOOSE_A_LOCAL_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Insert the generated hash into the `users.password` column. Set `role` to `user` for a regular user or `admin` for an administrator. For an administrator, set `unit` to one of the demo departments shown in `gsd_docs.php` and `upload.php` (for example, `DEPT-01`). Use fictional usernames and names.
6. Open the project through your local Apache document root. Do not expose a local development instance to the public internet.

## Public repository / privacy notes

- The `uploads/` folder is intentionally empty except for `.gitkeep`. Only use synthetic documents created for the demo.
- `database/schema.sql` contains table definitions only. It has no real people, accounts, password hashes, or document records.
- The original organization logos, embedded logo data, and the original SQL dump have been excluded.
- `.gitignore` excludes runtime uploads and local secrets. Check `git status` before each push.

## Important before publishing or deploying

This sanitized package removes sensitive sample data and organizational branding, but that does **not** establish that you own the source code or have permission to publish work created during an internship. Get written approval from your supervisor/organization and follow your internship agreement or IP policy before making the repository public. Do not add internal documents, real records, employee information, credentials, internal URLs, or proprietary branding.

This is a learning/portfolio demo and has not been security-reviewed for production deployment. Before deploying it, review CSRF protection for state-changing actions, stronger PDF content validation, authentication/session hardening, upload storage access controls, and production database permissions.
