-- Extend widget_leads with channel-aware destination and event columns.
-- Existing NULL channel values are treated as WhatsApp at runtime.

ALTER TABLE widget_leads
    ADD COLUMN IF NOT EXISTS channel VARCHAR(32) NULL DEFAULT NULL AFTER whatsapp_redirect_url,
    ADD COLUMN IF NOT EXISTS channel_destination_id INT UNSIGNED NULL DEFAULT NULL AFTER channel,
    ADD COLUMN IF NOT EXISTS destination_type VARCHAR(32) NULL DEFAULT NULL AFTER channel_destination_id,
    ADD COLUMN IF NOT EXISTS destination_name VARCHAR(120) NULL DEFAULT NULL AFTER destination_type,
    ADD COLUMN IF NOT EXISTS destination_snapshot VARCHAR(512) NULL DEFAULT NULL AFTER destination_name,
    ADD COLUMN IF NOT EXISTS channel_selected_at DATETIME NULL DEFAULT NULL AFTER destination_snapshot,
    ADD COLUMN IF NOT EXISTS destination_resolved_at DATETIME NULL DEFAULT NULL AFTER channel_selected_at,
    ADD COLUMN IF NOT EXISTS redirect_attempted_at DATETIME NULL DEFAULT NULL AFTER destination_resolved_at,
    ADD COLUMN IF NOT EXISTS fallback_type VARCHAR(64) NULL DEFAULT NULL AFTER redirect_attempted_at;

CREATE INDEX IF NOT EXISTS idx_widget_leads_channel_created
    ON widget_leads (channel, created_at);

CREATE INDEX IF NOT EXISTS idx_widget_leads_client_channel_created
    ON widget_leads (client_id, channel, created_at);
