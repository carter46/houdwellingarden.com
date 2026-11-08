# Property data (demo vs production)

## Zillow scraping

We **do not** scrape [Zillow](https://www.zillow.com/) for listings or photos.

- Zillow’s terms prohibit automated collection of their data and images.
- Listing photos and details are **copyrighted**; reusing them on your site without a license is risky.
- Their site uses bot protection; scrapers break often and are not a maintainable data source.

**Legal ways to get real US inventory later**

- Your client’s **MLS / RETS / RESO Web API** feed (broker license).
- **Manual entry** in Admin → Properties (what this CMS is built for).
- **CSV import** you control (we can add a script when you have a file from a licensed source).
- Stock photos only for **demo** placeholders (Unsplash/Pexels), not copied Zillow media.

## US demo placeholders (current)

- **33 listings** across **5 homepage cities** (Zillow-style metros, not copied from Zillow): Austin, Phoenix, Atlanta, Denver, Chicago.
- Manifest: `database/hougarden_us_manifest.json` (regenerate: `php scripts/generate-hougarden-us-manifest.php`).
- Apply to local DB: `php scripts/apply-hougarden-manifest.php`
- Each listing description states it is **demo placeholder** data; `source_name = hougarden_us_placeholder`.
- Currency **USD**, country **United States**.

**Placeholder photos (local Pexels files)**

- **Buy / Rent / Sell** cards keep Zillow pathway illustrations under `assets/img/pathways/` (UI chrome only).
- **Explore areas**, **hero**, and **listing covers** use **real city + home photos** from Pexels, saved locally:

```bash
php scripts/seed-pexels-region-photos.php --force
php scripts/seed-pexels-property-photos.php --all
```

Upload `assets/img/regions/*.jpg`, `assets/img/hero-home.jpg`, and `uploads/properties/` with the site.

**Why not Zillow listing photos?** Zillow’s `photos.zillowstatic.com` URLs block automated downloads (403/bot protection). Pexels is safe for demos; replace with MLS media at launch.

To audit duplicate images locally:

```bash
php scripts/audit-listing-images.php
```

## Gallery images

`scripts/seed-property-galleries.php` can still reuse photos from its pool if you run it after manifest apply. Prefer unique `image_url` per row in the manifest (as generated) for covers.
