-- Nuzex Maritime profile, privacy and document-storage enhancement migration.
-- BACK UP the nuzex_maritime database before running this file.
-- Run this ONCE on an existing installation. It is not intended to be run repeatedly.
USE nuzex_maritime;

ALTER TABLE seafarers
  ADD COLUMN address VARCHAR(500) DEFAULT NULL AFTER nationality,
  ADD COLUMN birthday_gift_consent_at DATETIME DEFAULT NULL AFTER address,
  ADD KEY idx_seafarer_dob (dob),
  ADD KEY idx_seafarer_availability (availability_status, rank_title);

-- Allow any number of user-named "other" documents while retaining one current
-- file per standard document type (CV, CDC, CoC, CoP, passport, etc.).
ALTER TABLE seafarer_documents
  DROP INDEX one_current_document,
  ADD COLUMN standard_document_type VARCHAR(16)
    GENERATED ALWAYS AS (CASE WHEN document_type = 'other' THEN NULL ELSE document_type END) STORED AFTER document_type,
  ADD UNIQUE KEY one_current_standard_document (seafarer_id, standard_document_type),
  ADD KEY idx_seafarer_document_type (seafarer_id, document_type);
