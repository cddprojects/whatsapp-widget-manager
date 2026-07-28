-- Rollback 019: remove channel lead columns. Does not touch WhatsApp legacy lead fields.

ALTER TABLE widget_leads
    DROP INDEX IF EXISTS idx_widget_leads_client_channel_created,
    DROP INDEX IF EXISTS idx_widget_leads_channel_created;

ALTER TABLE widget_leads
    DROP COLUMN IF EXISTS fallback_type,
    DROP COLUMN IF EXISTS redirect_attempted_at,
    DROP COLUMN IF EXISTS destination_resolved_at,
    DROP COLUMN IF EXISTS channel_selected_at,
    DROP COLUMN IF EXISTS destination_snapshot,
    DROP COLUMN IF EXISTS destination_name,
    DROP COLUMN IF EXISTS destination_type,
    DROP COLUMN IF EXISTS channel_destination_id,
    DROP COLUMN IF EXISTS channel;
