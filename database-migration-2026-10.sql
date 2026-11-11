-- Nuzex Maritime update migration for existing installations.
-- BACK UP your database first. Run once, then verify every ALTER completed successfully.
-- If a column already exists, omit that individual ADD COLUMN before rerunning.

ALTER TABLE seafarers
  ADD COLUMN preferred_vessel_type VARCHAR(120) NULL,
  ADD COLUMN preferred_salary_min DECIMAL(10,2) NULL,
  ADD COLUMN preferred_salary_currency VARCHAR(10) DEFAULT 'USD',
  ADD COLUMN coc_expiry DATE NULL,
  ADD COLUMN cop_expiry DATE NULL,
  ADD COLUMN cdc_expiry DATE NULL,
  ADD COLUMN stcw_expiry DATE NULL,
  ADD COLUMN privacy_consent_at DATETIME NULL;

ALTER TABLE jobs
  MODIFY COLUMN status ENUM('draft','published','hidden','closed') DEFAULT 'draft',
  ADD COLUMN application_deadline DATE NULL,
  ADD COLUMN company_name VARCHAR(180) NULL,
  ADD COLUMN job_reference VARCHAR(40) NULL;

ALTER TABLE applications
  ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE application_status_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  changed_by_admin_id INT NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_app_history_application (application_id, created_at),
  CONSTRAINT fk_history_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  CONSTRAINT fk_history_admin FOREIGN KEY (changed_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
);

CREATE TABLE admin_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id BIGINT NULL,
  details JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at),
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
);
