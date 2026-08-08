CREATE TABLE IF NOT EXISTS document_extractions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    licitation_document_id INT UNSIGNED NULL,
    proposal_document_id INT UNSIGNED NULL,
    status ENUM('pending', 'processing', 'done', 'failed') NOT NULL DEFAULT 'pending',
    method ENUM('pdftotext', 'tesseract') NULL,
    page_count SMALLINT UNSIGNED NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_document_extractions_licitation_document (licitation_document_id),
    KEY idx_document_extractions_proposal_document (proposal_document_id),
    CONSTRAINT fk_document_extractions_licitation_document FOREIGN KEY (licitation_document_id) REFERENCES licitation_documents (id) ON DELETE CASCADE,
    CONSTRAINT fk_document_extractions_proposal_document FOREIGN KEY (proposal_document_id) REFERENCES proposal_documents (id) ON DELETE CASCADE,
    CONSTRAINT chk_document_extractions_single_source CHECK (
        (licitation_document_id IS NOT NULL AND proposal_document_id IS NULL)
        OR (licitation_document_id IS NULL AND proposal_document_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_chunks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_extraction_id INT UNSIGNED NOT NULL,
    page_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    content LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_document_chunks_extraction (document_extraction_id),
    CONSTRAINT fk_document_chunks_extraction FOREIGN KEY (document_extraction_id) REFERENCES document_extractions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
