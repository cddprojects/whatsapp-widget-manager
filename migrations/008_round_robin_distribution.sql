ALTER TABLE widgets
    ADD COLUMN destination_selection_method VARCHAR(30) NOT NULL DEFAULT 'random' AFTER random_numbers_json,
    ADD COLUMN round_robin_next_index INT UNSIGNED NOT NULL DEFAULT 0 AFTER destination_selection_method;

UPDATE widgets
SET destination_selection_method = 'random'
WHERE use_random_numbers = 1;

UPDATE widgets
SET destination_selection_method = 'single'
WHERE use_random_numbers = 0
   OR random_numbers_json IS NULL
   OR random_numbers_json = ''
   OR random_numbers_json = '[]';
