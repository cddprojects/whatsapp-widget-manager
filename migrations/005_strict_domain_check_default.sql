USE click_to_chat_manager;

ALTER TABLE widgets
    MODIFY strict_domain_check TINYINT(1) NOT NULL DEFAULT 0;
