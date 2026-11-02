Legacy SQL migrations (archived 2025-11-01)
============================================

These files were used for incremental upgrades of the older SDC codebase.
Their SCHEMA changes are now:

  1. Merged into database/schema.sql (fresh phpMyAdmin installs)
  2. Applied automatically by includes/schema-migrate.php (existing databases)

Do NOT import these on a new HouGarden deploy.

DATA-only scripts (optional, manual, Colorado demo):
  migrations_agent_photos.sql   — agent portrait paths
  migrations_seed_images.sql    — demo property image rows

Optional product line (not HouGarden AU):
  migrations_parent_company_properties.sql — Century Communities import

Keep this folder in git for history and emergency manual reference only.
