USE click_to_chat_manager;

ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS widget_status VARCHAR(30) NOT NULL DEFAULT 'setup_required' AFTER show_global;

UPDATE widgets
SET widget_status = 'disabled'
WHERE show_global = 0;

UPDATE widgets
SET widget_status = 'active'
WHERE show_global = 1
  AND (
    (use_random_numbers = 1 AND random_numbers_json IS NOT NULL AND TRIM(random_numbers_json) NOT IN ('', '[]'))
    OR (whatsapp_number IS NOT NULL AND TRIM(whatsapp_number) <> '')
  );

UPDATE widgets
SET widget_status = 'setup_required'
WHERE show_global = 1
  AND widget_status NOT IN ('active', 'disabled', 'paused');
