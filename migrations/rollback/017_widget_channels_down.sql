-- Rollback 017: drop widget_channels.
-- Does NOT modify widgets WhatsApp columns or destination_selection_method.

DROP TABLE IF EXISTS widget_channels;
