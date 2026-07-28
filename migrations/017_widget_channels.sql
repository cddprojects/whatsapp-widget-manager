-- Create channel-neutral widget_channels and backfill existing widgets as WhatsApp-only.

CREATE TABLE IF NOT EXISTS widget_channels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    widget_id INT UNSIGNED NOT NULL,
    channel VARCHAR(32) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    destination_selection_method VARCHAR(30) NOT NULL DEFAULT 'random',
    round_robin_next_index INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_widget_channels_widget_channel (widget_id, channel),
    KEY idx_widget_channels_widget_enabled (widget_id, is_enabled),
    CONSTRAINT fk_widget_channels_widget
        FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO widget_channels (
    widget_id,
    channel,
    is_enabled,
    is_default,
    display_order,
    destination_selection_method,
    round_robin_next_index,
    created_at,
    updated_at
)
SELECT
    w.id,
    'whatsapp',
    1,
    1,
    1,
    CASE
        WHEN w.destination_selection_method IN ('random', 'round_robin', 'single')
            THEN w.destination_selection_method
        WHEN w.use_random_numbers = 1 THEN 'random'
        ELSE 'single'
    END,
    COALESCE(w.round_robin_next_index, 0),
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM widgets AS w
WHERE NOT EXISTS (
    SELECT 1
    FROM widget_channels AS wc
    WHERE wc.widget_id = w.id
      AND wc.channel = 'whatsapp'
);

INSERT INTO widget_channels (
    widget_id,
    channel,
    is_enabled,
    is_default,
    display_order,
    destination_selection_method,
    round_robin_next_index,
    created_at,
    updated_at
)
SELECT
    w.id,
    'telegram',
    0,
    0,
    2,
    'round_robin',
    0,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM widgets AS w
WHERE NOT EXISTS (
    SELECT 1
    FROM widget_channels AS wc
    WHERE wc.widget_id = w.id
      AND wc.channel = 'telegram'
);
