USE click_to_chat_manager;

ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS greeting_capture_phone TINYINT(1) NOT NULL DEFAULT 0 AFTER greeting_delay_seconds,
    ADD COLUMN IF NOT EXISTS greeting_phone_required TINYINT(1) NOT NULL DEFAULT 1 AFTER greeting_capture_phone,
    ADD COLUMN IF NOT EXISTS greeting_phone_placeholder VARCHAR(100) NOT NULL DEFAULT 'Enter your phone number' AFTER greeting_phone_required,
    ADD COLUMN IF NOT EXISTS greeting_submit_text VARCHAR(100) NOT NULL DEFAULT 'Continue to WhatsApp' AFTER greeting_phone_placeholder,
    ADD COLUMN IF NOT EXISTS greeting_lead_success_message VARCHAR(255) NOT NULL DEFAULT 'Redirecting to WhatsApp...' AFTER greeting_submit_text;

CREATE TABLE IF NOT EXISTS widget_leads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    visitor_phone VARCHAR(50) NOT NULL,
    visitor_country_code VARCHAR(10) NULL,
    visitor_full_phone VARCHAR(50) NOT NULL,
    source_domain VARCHAR(255) NULL,
    source_url TEXT NULL,
    page_title VARCHAR(255) NULL,
    whatsapp_redirect_url TEXT NULL,
    ip_address VARCHAR(100) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_widget_leads_widget_id (widget_id),
    INDEX idx_widget_leads_user_id (user_id),
    INDEX idx_widget_leads_visitor_phone (visitor_full_phone),
    INDEX idx_widget_leads_created_at (created_at),
    CONSTRAINT fk_widget_leads_widget FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE,
    CONSTRAINT fk_widget_leads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
