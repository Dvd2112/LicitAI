CREATE TABLE IF NOT EXISTS licitation_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    licitation_id INT UNSIGNED NOT NULL,
    doc_type VARCHAR(100) NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_licitation_documents_licitation (licitation_id),
    CONSTRAINT fk_licitation_documents_licitation FOREIGN KEY (licitation_id) REFERENCES licitations (id) ON DELETE CASCADE,
    CONSTRAINT fk_licitation_documents_user FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
