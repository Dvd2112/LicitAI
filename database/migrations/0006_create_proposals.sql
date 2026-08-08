CREATE TABLE IF NOT EXISTS proposals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    licitation_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    status ENUM(
        'draft', 'submitted', 'queued', 'extracting',
        'analyzing', 'analyzed', 'needs_review', 'error'
    ) NOT NULL DEFAULT 'draft',
    error_message TEXT NULL,
    submitted_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_proposals_licitation_company (licitation_id, company_id),
    KEY idx_proposals_status (status),
    CONSTRAINT fk_proposals_licitation FOREIGN KEY (licitation_id) REFERENCES licitations (id) ON DELETE CASCADE,
    CONSTRAINT fk_proposals_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_proposals_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT UNSIGNED NOT NULL,
    licitation_item_id INT UNSIGNED NOT NULL,
    unit_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
    total_price DECIMAL(14, 2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_proposal_items_proposal (proposal_id),
    CONSTRAINT fk_proposal_items_proposal FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE,
    CONSTRAINT fk_proposal_items_licitation_item FOREIGN KEY (licitation_item_id) REFERENCES licitation_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_proposal_documents_proposal (proposal_id),
    CONSTRAINT fk_proposal_documents_proposal FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
