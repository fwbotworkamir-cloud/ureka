# Ureka Education — ureka.co.uk

WordPress site code (client). Exported 2026-07-27 via All-in-One WP Migration.

## Tracked in this repo
- `wp-content/themes/kingster` + `kingster-child` — Goodlayers theme (Envato)
- `wp-content/plugins/goodlayers-core*` — theme companion plugins
- `wp-content/plugins/revslider` — Slider Revolution (premium)
- `wp-content/plugins/envato-market`
- `wp-content/mu-plugins/`

## NOT tracked (reinstall from WordPress.org)
akismet, classic-widgets, clear-cache-for-widgets, contact-form-7, duplicator,
fileorganizer, jetpack, learnpress (+course-review, import-export,
prerequisites-courses, wishlist), newsletter, sm-page-duplicator, speedycache,
the-events-calendar, wp-file-manager, wp-google-map-plugin

Also not tracked: WP core, `wp-config.php`, `uploads/`, database.

## Environment
- PHP 8.1.34 on the live host (EOL — upgrade to 8.2+ pending)
- LMS: LearnPress; events: The Events Calendar

## Staging
Import the full `.wpress` export into LocalWP / a staging host, then
search-replace `https://ureka.co.uk` → staging URL.
