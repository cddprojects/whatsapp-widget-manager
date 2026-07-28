-- Create channel_destinations and backfill existing single WhatsApp phone destinations.
-- Multi-number JSON lists are synced by PHP (sync_whatsapp_destinations_from_legacy).

CREATE TABLE IF NOT EXISTS channel_destinations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    widget_id INT UNSIGNED NOT NULL,
    channel VARCHAR(32) NOT NULL,
    destination_type VARCHAR(32) NOT NULL,
    destination_value VARCHAR(512) NOT NULL,
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    bot_start_parameter VARCHAR(64) NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    distribution_weight INT UNSIGNED NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_channel_destinations_lookup (widget_id, channel, deleted_at, is_active),
    KEY idx_channel_destinations_value (widget_id, channel, destination_type, destination_value(191)),
    CONSTRAINT fk_channel_destinations_widget
        FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO channel_destinations (
    widget_id,
    channel,
    destination_type,
    destination_value,
    display_name,
    bot_start_parameter,
    is_active,
    distribution_weight,
    sort_order,
    deleted_at,
    created_at,
    updated_at
)
SELECT
    w.id,
    'whatsapp',
    'phone',
    CONCAT(
        REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_country_code, '')), '[^0-9]', ''),
        REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_number, '')), '[^0-9]', '')
    ),
    TRIM(CONCAT(COALESCE(w.whatsapp_country_code, ''), ' ', COALESCE(w.whatsapp_number, ''))),
    NULL,
    1,
    1,
    0,
    NULL,
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM widgets AS w
WHERE TRIM(COALESCE(w.whatsapp_number, '')) <> ''
  AND (
        w.use_random_numbers = 0
        OR w.random_numbers_json IS NULL
        OR TRIM(w.random_numbers_json) IN ('', '[]')
      )
  AND CONCAT(
        REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_country_code, '')), '[^0-9]', ''),
        REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_number, '')), '[^0-9]', '')
      ) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM channel_destinations AS cd
      WHERE cd.widget_id = w.id
        AND cd.channel = 'whatsapp'
        AND cd.destination_type = 'phone'
        AND cd.deleted_at IS NULL
        AND cd.destination_value = CONCAT(
            REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_country_code, '')), '[^0-9]', ''),
            REGEXP_REPLACE(TRIM(COALESCE(w.whatsapp_number, '')), '[^0-9]', '')
        )
  );
