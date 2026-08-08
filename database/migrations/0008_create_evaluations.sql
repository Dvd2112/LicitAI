CREATE TABLE IF NOT EXISTS evaluations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT UNSIGNED NOT NULL,
    licitation_requirement_id INT UNSIGNED NOT NULL,
    classification ENUM(
        'atende', 'atende_parcialmente', 'nao_atende',
        'evidencia_insuficiente', 'nao_identificado', 'requer_revisao'
    ) NOT NULL,
    justification TEXT NOT NULL,
    confidence DECIMAL(4, 3) NULL,
    ai_model VARCHAR(60) NULL,
    human_reviewed TINYINT(1) NOT NULL DEFAULT 0,
    human_classification ENUM(
        'atende', 'atende_parcialmente', 'nao_atende',
        'evidencia_insuficiente', 'nao_identificado', 'requer_revisao'
    ) NULL,
    human_justification TEXT NULL,
    human_reviewed_by INT UNSIGNED NULL,
    human_reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_evaluations_proposal_requirement (proposal_id, licitation_requirement_id),
    KEY idx_evaluations_requirement (licitation_requirement_id),
    CONSTRAINT fk_evaluations_proposal FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluations_requirement FOREIGN KEY (licitation_requirement_id) REFERENCES licitation_requirements (id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluations_reviewer FOREIGN KEY (human_reviewed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluation_evidences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT UNSIGNED NOT NULL,
    proposal_document_id INT UNSIGNED NULL,
    page_number SMALLINT UNSIGNED NULL,
    excerpt TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evaluation_evidences_evaluation (evaluation_id),
    CONSTRAINT fk_evaluation_evidences_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations (id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluation_evidences_document FOREIGN KEY (proposal_document_id) REFERENCES proposal_documents (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT UNSIGNED NOT NULL,
    total_requirements SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    attended SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    partially_attended SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    not_attended SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    insufficient_evidence SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    adherence_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_proposal_scores_proposal (proposal_id),
    CONSTRAINT fk_proposal_scores_proposal FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
