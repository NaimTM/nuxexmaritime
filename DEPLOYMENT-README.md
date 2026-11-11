# Nuzex Maritime — Updated package and deployment guide

## What's included

- Existing public website, job board, seafarer registration/profile and admin pages retained.
- Production-aware database configuration using environment variables (no root/blank-password default).
- Generic production database error page with server-side logging.
- Safer session cookie settings, CSRF validation, session ID regeneration at login/registration, and redirect validation.
- CV upload helper with size, extension and MIME checks; private-storage download endpoint with authorization checks.
- Apache access-denial rules for the fallback CV storage directory and legacy public CV directory.
- Correct `/settings.php` compatibility route and corrected internal links to the existing `setting.php` page.
- Normalized canonical URL generation that excludes query parameters.
- Application status history and admin audit log; candidate application history display.
- Added profile fields for certificate expiry and vessel/salary preferences, plus schema fields for job reference/company/deadline metadata.
- Removed the bundled sample CV and insecure public hash helper.

## Before deployment

1. Make a complete file and database backup.
2. Use a currently supported PHP release (PHP 8.2+ recommended) with PDO MySQL, Fileinfo, and required standard extensions enabled.
3. Create a dedicated MySQL database and a dedicated database user with only the privileges needed by this application. Do not use MySQL `root`.
4. Set the hosting environment variables shown in `.env.example`. In particular set `APP_ENV=production`, the real HTTPS `SITE_URL`, and a strong unique `DB_PASS`.
5. Set `CV_STORAGE_DIR` to a directory outside `public_html` / the document root, e.g. `/home/ACCOUNT/private_uploads/cv`, and ensure PHP can write to it. If the host cannot store files outside the web root, confirm the included Apache deny rule is active; for Nginx, configure an equivalent deny rule at the server level.
6. Import `database.sql` for a new database. For an existing database, back it up and apply `database-migration-2026-10.sql` once. If a column already exists, remove that specific `ADD COLUMN` statement before applying the migration. Do not run the migration repeatedly.
7. Create an administrator from the command line, from the project root: `php bin/create-admin.php "Admin Name" admin@yourdomain.com super`. Do not expose this script through a browser. If your hosting does not provide CLI access, ask the host to run it or use a one-time protected maintenance procedure and remove it immediately afterward.
8. Do not upload the original ZIP's CVs or other real personal data. This updated package excludes the sample CV found in the source archive.
9. Confirm `uploads/cv` is denied by the web server. Existing legacy CVs in that folder should be moved into private storage and their database paths updated before enabling the site publicly.
10. Configure outbound email separately with authenticated SMTP or a reputable transactional mail provider before relying on email notifications. This package does not pretend email delivery is configured.

## Smoke tests required before going live

- Homepage, About, Services, Compliance, Contact, FAQ, Jobs, Seafarers, Shipowners, Privacy, Terms and Cookie pages load over HTTPS.
- Register, login, logout, profile update, password/settings actions, account deletion and CSRF failure behavior.
- CV uploads reject wrong file types, over-limit files and failed uploads; CV URLs cannot be accessed directly; unauthenticated users cannot download CVs; one seafarer cannot access another's CV.
- Only published vacancies can be applied to; duplicate applications are blocked; application status changes appear in the candidate dashboard.
- Employer crew requests and contact forms validate fields and save correctly.
- Admin role permissions are correct; viewer accounts cannot update application status; audit history is written.
- Test on mobile and desktop; verify error logs, backup and restore process, and 404/500 behavior.

## Important limitations

This archive has been syntax-checked locally. A complete live acceptance test still requires your hosting configuration, database credentials, web-server type, SMTP provider, and actual deployment environment. If the server uses Nginx, IIS, or unusual shared-hosting restrictions, adapt the access-denial and environment configuration accordingly. Do not publish until the tests above pass.

## Storage migration for existing CVs

This package supports legacy stored path names for downloads while the old files still exist, but it is strongly recommended to move all legacy CVs out of the public `uploads/cv` directory. Copy each existing CV into the configured private storage directory, keep its filename, and change its database path to `private_uploads/cv/FILENAME` (or your configured storage-relative path) in both `seafarers.cv_path` and `applications.cv_path` where applicable. Verify authorized downloads, then delete the old public copies.
