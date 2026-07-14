-- Idempotent migration: Client API Keys and Widget API Keys

CREATE TABLE IF NOT EXISTS api_credentials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    owner_type ENUM('client', 'widget') NOT NULL,
    owner_id INT UNSIGNED NOT NULL,
    credential_type ENUM('client_api', 'widget_api') NOT NULL,
    key_prefix VARCHAR(64) NOT NULL,
    key_last_four VARCHAR(8) NOT NULL,
    key_hash CHAR(64) NOT NULL,
    key_ciphertext TEXT NOT NULL,
    key_nonce VARCHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL DEFAULT NULL,
    revoked_at DATETIME NULL DEFAULT NULL,
    created_by INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_credentials_owner_type (owner_type, owner_id, credential_type),
    UNIQUE KEY uq_api_credentials_hash (key_hash),
    KEY idx_api_credentials_owner (owner_type, owner_id),
    KEY idx_api_credentials_active (is_active, revoked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_request_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_credential_id BIGINT UNSIGNED NULL DEFAULT NULL,
    widget_credential_id BIGINT UNSIGNED NULL DEFAULT NULL,
    client_id INT UNSIGNED NULL DEFAULT NULL,
    widget_id INT UNSIGNED NULL DEFAULT NULL,
    endpoint VARCHAR(191) NOT NULL,
    http_method VARCHAR(16) NOT NULL,
    response_status SMALLINT UNSIGNED NOT NULL,
    period_type VARCHAR(64) NULL DEFAULT NULL,
    requester_ip VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(512) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_api_request_logs_created (created_at),
    KEY idx_api_request_logs_client (client_id),
    KEY idx_api_request_logs_widget (widget_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_rate_limits (
    bucket_key CHAR(64) NOT NULL,
    window_started_at DATETIME NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (bucket_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
