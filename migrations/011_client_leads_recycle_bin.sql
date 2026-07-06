USE click_to_chat_manager;

ALTER TABLE widget_leads
    ADD COLUMN IF NOT EXISTS client_id INT UNSIGNED NULL AFTER widget_id,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS deleted_by_user_id INT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS deleted_by_role VARCHAR(30) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS restored_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS restored_by_user_id INT UNSIGNED NULL DEFAULT NULL;

UPDATE widget_leads AS wl
INNER JOIN widgets AS w ON w.id = wl.widget_id
SET wl.client_id = w.user_id
WHERE wl.client_id IS NULL;

CREATE INDEX IF NOT EXISTS idx_widget_leads_client_active_created
    ON widget_leads (client_id, deleted_at, created_at);

CREATE INDEX IF NOT EXISTS idx_widget_leads_client_widget
    ON widget_leads (client_id, widget_id);

CREATE INDEX IF NOT EXISTS idx_widget_leads_deleted_at
    ON widget_leads (deleted_at);

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value)
VALUES
    ('lead_recycle_bin_auto_purge_enabled', '1'),
    ('lead_recycle_bin_retention_days', '30')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
