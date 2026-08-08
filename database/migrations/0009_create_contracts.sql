CREATE TABLE IF NOT EXISTS contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    licitation_id INT UNSIGNED NULL,
    proposal_id INT UNSIGNED NULL,
    company_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    number VARCHAR(40) NOT NULL,
    status ENUM('em_elaboracao', 'ativo', 'suspenso', 'encerrado', 'vencido') NOT NULL DEFAULT 'em_elaboracao',
    value DECIMAL(14, 2) NOT NULL DEFAULT 0,
    physical_execution_percentage DECIMAL(5, 2) NOT NULL DEFAULT 0,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_contracts_number (number),
    KEY idx_contracts_licitation (licitation_id),
    KEY idx_contracts_company (company_id),
    CONSTRAINT fk_contracts_licitation FOREIGN KEY (licitation_id) REFERENCES licitations (id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_proposal FOREIGN KEY (proposal_id) REFERENCES proposals (id) ON DELETE SET NULL,
    CONSTRAINT fk_contracts_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contract_deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    due_date DATE NULL,
    delivered_date DATE NULL,
    status ENUM('pending', 'delivered', 'late') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_deliveries_contract (contract_id),
    CONSTRAINT fk_contract_deliveries_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contract_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(14, 2) NOT NULL DEFAULT 0,
    due_date DATE NULL,
    paid_date DATE NULL,
    status ENUM('pending', 'paid', 'late') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contract_payments_contract (contract_id),
    CONSTRAINT fk_contract_payments_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
