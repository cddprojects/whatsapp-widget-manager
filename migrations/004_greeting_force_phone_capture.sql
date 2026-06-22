USE click_to_chat_manager;

ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS greeting_force_phone_capture TINYINT(1) NOT NULL DEFAULT 0 AFTER greeting_phone_required;
