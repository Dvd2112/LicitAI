CREATE TABLE IF NOT EXISTS licitation_requirements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    licitation_id INT UNSIGNED NOT NULL,
    category ENUM(
        'documentacao', 'especificacao_tecnica', 'quantidade', 'certificacao',
        'experiencia', 'prazo', 'condicao_comercial', 'capacidade_tecnica',
        'obrigacao_legal', 'requisito_financeiro', 'outro'
    ) NOT NULL DEFAULT 'outro',
    description TEXT NOT NULL,
    mandatory TINYINT(1) NOT NULL DEFAULT 1,
    weight DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
    origin ENUM('manual', 'extracted') NOT NULL DEFAULT 'manual',
    source_document_id INT UNSIGNED NULL,
    source_page INT UNSIGNED NULL,
    source_excerpt TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_licitation_requirements_licitation (licitation_id),
    CONSTRAINT fk_licitation_requirements_licitation FOREIGN KEY (licitation_id) REFERENCES licitations (id) ON DELETE CASCADE,
    CONSTRAINT fk_licitation_requirements_document FOREIGN KEY (source_document_id) REFERENCES licitation_documents (id) ON DELETE SET NULL,
    CONSTRAINT fk_licitation_requirements_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
