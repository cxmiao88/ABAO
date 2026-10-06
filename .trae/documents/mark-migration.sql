INSERT INTO migrations (migration, batch)
SELECT '2026_10_06_161016_add_locale_to_users_table', COALESCE(MAX(batch), 1)
FROM migrations;
