USE click_to_chat_manager;

ALTER TABLE widgets
    MODIFY public_key VARCHAR(100) NOT NULL,
    ADD COLUMN IF NOT EXISTS allow_www TINYINT(1) NOT NULL DEFAULT 1 AFTER website_domain,
    ADD COLUMN IF NOT EXISTS allow_subdomains TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_www,
    ADD COLUMN IF NOT EXISTS domain_lock_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_subdomains,
    ADD COLUMN IF NOT EXISTS strict_domain_check TINYINT(1) NOT NULL DEFAULT 1 AFTER domain_lock_enabled;
