CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(60) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'running', 'done', 'failed') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    run_after TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_jobs_status_type (status, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
