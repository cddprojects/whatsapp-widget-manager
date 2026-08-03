-- Rollback 018: drop channel_destinations.
-- Does NOT modify widgets WhatsApp phone columns.

DROP TABLE IF EXISTS channel_destinations;
