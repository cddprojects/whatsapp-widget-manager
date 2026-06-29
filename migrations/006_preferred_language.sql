USE click_to_chat_manager;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS preferred_language VARCHAR(10) NOT NULL DEFAULT 'en' AFTER status;

UPDATE users
SET preferred_language = 'en'
WHERE preferred_language IS NULL OR preferred_language = '';
