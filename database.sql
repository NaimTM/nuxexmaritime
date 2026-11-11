-- Nuzex Maritime Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS nuzex_maritime
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nuzex_maritime;

-- ============ ADMIN USERS ============
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super','recruiter','viewer') DEFAULT 'recruiter',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ SEAFARERS ============
CREATE TABLE IF NOT EXISTS seafarers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  phone VARCHAR(40) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nationality VARCHAR(80),
  dob DATE,
  passport_no VARCHAR(60),
  rank_title VARCHAR(120),
  coc_no VARCHAR(80),
  cop_no VARCHAR(80),
  cdc_no VARCHAR(80),
  vessel_experience TEXT,
  current_rank_voyages INT UNSIGNED DEFAULT NULL,
  years_at_current_rank DECIMAL(4,1) DEFAULT NULL,
  total_sea_service_months INT UNSIGNED DEFAULT NULL,
  availability DATE,
  availability_status ENUM('available','from_date','unavailable') NOT NULL DEFAULT 'unavailable',
  cv_path VARCHAR(255),
  photo_path VARCHAR(255),
  is_verified TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ PRIVATE SEAFARER DOCUMENTS ============
CREATE TABLE IF NOT EXISTS seafarer_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seafarer_id INT NOT NULL,
  document_type ENUM('cv','cdc','coc','cop','passport','medical','stcw','other') NOT NULL,
  label VARCHAR(160) DEFAULT NULL,
  stored_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  issue_date DATE DEFAULT NULL,
  expiry_date DATE DEFAULT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verification_note VARCHAR(500) DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY one_current_document (seafarer_id, document_type),
  KEY idx_document_expiry (expiry_date),
  CONSTRAINT fk_seafarer_documents_user FOREIGN KEY (seafarer_id) REFERENCES seafarers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seafarer_document_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT DEFAULT NULL,
  seafarer_id INT NOT NULL,
  admin_id INT DEFAULT NULL,
  action VARCHAR(40) NOT NULL,
  note VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_doc_audit_seafarer (seafarer_id, created_at)
);

-- ============ JOBS ============
CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(200) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  rank_title VARCHAR(120) NOT NULL,
  vessel_type VARCHAR(120),
  flag VARCHAR(80),
  salary_min DECIMAL(10,2),
  salary_max DECIMAL(10,2),
  currency VARCHAR(10) DEFAULT 'USD',
  contract_length VARCHAR(60),
  joining_date DATE,
  joining_port VARCHAR(120),
  requirements TEXT,
  description LONGTEXT,
  status ENUM('draft','published','closed') DEFAULT 'published',
  likes_count INT DEFAULT 0,
  comments_count INT DEFAULT 0,
  shares_count INT DEFAULT 0,
  views_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============ APPLICATIONS ============
CREATE TABLE IF NOT EXISTS applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  seafarer_id INT NOT NULL,
  rank_title VARCHAR(120),
  cv_path VARCHAR(255),
  cover_note TEXT,
  status ENUM('applied','shortlisted','interview','offered','hired','rejected') DEFAULT 'applied',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (seafarer_id) REFERENCES seafarers(id) ON DELETE CASCADE,
  UNIQUE KEY one_apply (job_id, seafarer_id)
);

-- ============ LIKES ============
CREATE TABLE IF NOT EXISTS likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  seafarer_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_like (job_id, seafarer_id),
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (seafarer_id) REFERENCES seafarers(id) ON DELETE CASCADE
);

-- ============ COMMENTS ============
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  seafarer_id INT NOT NULL,
  parent_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  status ENUM('visible','hidden','flagged') DEFAULT 'visible',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (seafarer_id) REFERENCES seafarers(id) ON DELETE CASCADE
);

-- ============ SHARES ============
CREATE TABLE IF NOT EXISTS shares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  platform VARCHAR(30),
  seafarer_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- ============ CREW REQUESTS (shipowners) ============
CREATE TABLE IF NOT EXISTS crew_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company VARCHAR(180),
  contact_name VARCHAR(160),
  email VARCHAR(180),
  phone VARCHAR(40),
  vessel_type VARCHAR(120),
  flag VARCHAR(80),
  ranks_needed VARCHAR(255),
  crew_count INT,
  joining_date DATE,
  port VARCHAR(120),
  contract_length VARCHAR(60),
  notes TEXT,
  status ENUM('new','contacted','completed','closed') DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ CONTACT MESSAGES ============
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160),
  email VARCHAR(180),
  phone VARCHAR(40),
  subject VARCHAR(200),
  message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ JOB ALERTS ============
CREATE TABLE IF NOT EXISTS job_alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180),
  whatsapp VARCHAR(40),
  rank_title VARCHAR(120),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ SHIPOWNERS ============
CREATE TABLE IF NOT EXISTS shipowners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(180) NOT NULL,
  contact_person VARCHAR(160) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  phone VARCHAR(40) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============ CREW DEPLOYMENTS ============
CREATE TABLE IF NOT EXISTS crew_deployments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shipowner_id INT NOT NULL,
  seafarer_id INT DEFAULT NULL,
  vessel_name VARCHAR(160) NOT NULL,
  rank_title VARCHAR(120) DEFAULT NULL,
  sign_on_date DATE DEFAULT NULL,
  sign_off_date DATE DEFAULT NULL,
  status ENUM('Onboard','Completed','Terminated') DEFAULT 'Onboard',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_dep_shipowner (shipowner_id),
  KEY idx_dep_seafarer (seafarer_id),
  CONSTRAINT fk_deployments_shipowner FOREIGN KEY (shipowner_id) REFERENCES shipowners(id) ON DELETE CASCADE,
  CONSTRAINT fk_deployments_seafarer FOREIGN KEY (seafarer_id) REFERENCES seafarers(id) ON DELETE SET NULL
);

-- ============ INVOICES ============
CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(60) NOT NULL UNIQUE,
  shipowner_id INT NOT NULL,
  billing_month VARCHAR(60) NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  pdf_file VARCHAR(255) DEFAULT NULL,
  status ENUM('paid','unpaid') DEFAULT 'unpaid',
  issued_date DATE DEFAULT NULL,
  paid_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_shipowner FOREIGN KEY (shipowner_id) REFERENCES shipowners(id) ON DELETE CASCADE
);

-- ============ INVOICE ITEMS ============
CREATE TABLE IF NOT EXISTS invoice_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  crew_deployment_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_deployment FOREIGN KEY (crew_deployment_id) REFERENCES crew_deployments(id) ON DELETE SET NULL
);

-- ============ DEFAULT ADMIN (password: ChangeMe123!) ============
INSERT IGNORE INTO admins (name, email, password_hash, role) VALUES
('Nuzex Admin', 'admin@nuzexmaritime.com',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JG1nG7Zm1v1n1Pq5r3Iu',
 'super');