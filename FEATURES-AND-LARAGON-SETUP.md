# Nuzex Maritime — Seafarer documents and crew requests update

## Included
- Seafarer fields for voyages at current rank, years at rank, total sea-service months, and joining availability.
- Private document upload for CV, CDC, CoC, CoP, passport, medical, STCW/training and one current “other” document.
- Issue and expiry dates. Re-uploading a document type replaces the prior database record/file after the replacement has been saved successfully.
- Private download endpoint: only the document owner or a logged-in admin can access new private uploads.
- Admin seafarer list filters and sorting, candidate details, document download and verification workflow. No recruiter/candidate database portal has been added.
- Crew request status now supports `completed` in addition to existing statuses.

## IMPORTANT: back up first
Make a full copy of website files and export your MySQL database before updating.

## Existing Laragon database
1. Open phpMyAdmin or HeidiSQL and select `nuzex_maritime`.
2. Import `database-migration-seafarer-documents.sql` exactly once. Do not run it twice (the `ALTER TABLE` statements are not idempotent).
3. Keep existing website files, then copy the updated files over them.
4. The default private storage path is `C:\laragon\nuzexmaritime_private\documents\` when the website is in `C:\laragon\www\nuzexmaritime\`. Ensure PHP can write to this directory. It is outside `www` so files are not directly public.
5. Sign in as a seafarer, open Profile, upload a test PDF, set expiry date, and verify the document appears. Re-upload it and confirm the old file is replaced.
6. Sign in as admin, open Seafarers → Details, download the file, and set document status. Test that a different seafarer cannot access another candidate's download URL.
7. Submit a test crew request and mark it Completed in Admin → Crew Requests.

## Notes / limitations
- Uploaded documents are limited to 10 MB. Supported formats: PDF, JPG, PNG, DOC and DOCX. MIME validation is applied, but server-side malware scanning is not included.
- Existing legacy CVs stored in `uploads/cv/` remain in their original location and are not automatically imported into the private document table. New uploads through My Documents use private storage. Review and migrate legacy CVs separately before production.
- Document expiry reminders are visible to admins through the candidate document details and expired-count indicator; automated email reminders are not included because mail delivery configuration is not available in this package.
- Existing `availability` is retained. `availability_status` distinguishes available now, available from date, and unavailable.
- The employer portal/candidate database access for recruiters was intentionally not added. Crew requests are still handled in the admin dashboard.
- This update needs local runtime testing against your Laragon PHP/MySQL versions before production.
