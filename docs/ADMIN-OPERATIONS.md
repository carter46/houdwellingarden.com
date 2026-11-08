# Admin operations checklist

Run locally: `php scripts/audit-database.php`

## Homepage locations (Regions)

**Admin → Regions**

| Action | How |
|--------|-----|
| Change location **name** | Edit region → Name |
| Change **homepage tile image** | Edit region → **Homepage location image** (file upload) |
| Show on homepage | **Show on homepage (Locations section)** |
| Link to listings | **Filter value** = listing `city` (e.g. `Austin`) |

Uploads are stored under `uploads/regions/` (or `assets/img/regions/` if set manually).

## Properties

| Action | Where |
|--------|--------|
| **Create** | Admin → Properties → **New Listing** → Save |
| **Edit** | ⋯ menu → Edit (or open property form) |
| **Archive** | ⋯ menu → Archive (hides from public; keeps data) |
| **Delete permanently** | Archive first → ⋯ on archived row → Delete permanently (type `DELETE`) |

Images: managed on the property form (cover + gallery). Deletes are applied when you save.

## Database schema

- Version tracked in `settings.schema_version`
- Target: `app_schema_target_version()` in `includes/schema-migrate.php`
- Migrations run on first public or admin page load after deploy
