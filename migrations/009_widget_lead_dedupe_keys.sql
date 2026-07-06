USE click_to_chat_manager;

CREATE TABLE IF NOT EXISTS widget_lead_dedupe_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id INT UNSIGNED NOT NULL,
    visitor_full_phone VARCHAR(50) NOT NULL,
    first_lead_id INT UNSIGNED NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_widget_lead_dedupe (widget_id, visitor_full_phone),
    INDEX idx_widget_lead_dedupe_expires (expires_at),
    CONSTRAINT fk_widget_lead_dedupe_widget FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE,
    CONSTRAINT fk_widget_lead_dedupe_lead FOREIGN KEY (first_lead_id) REFERENCES widget_leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
