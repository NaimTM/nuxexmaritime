-- Migration Script: Document Uploads & Availability Status
ALTER TABLE users ADD COLUMN IF NOT EXISTS availability_status ENUM('available', 'unavailable') DEFAULT 'available';
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS seafarer_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL, -- e.g., 'CDC', 'Passport', 'COP', 'Yellow Fever', 'Other'
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
