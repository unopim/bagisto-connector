# 1.0.2 - (22-06-2026)

## Changed
- Compatibility with UnoPim v2.1.x.

## Fixed
- Category export: coerce `position` to an integer, fall back `display_mode` to `products` when there is no description, and auto-fill the required `attributes` field from the store's filterable attributes.
- Product export: cast `weight` to a string, guard `visible_individually` for variants, and make the created/skipped counts reflect what was actually built and sent.
- Configurable export: include a configurable's variants in the export so Bagisto can create and link them.
- Support selecting multiple families in the product export filter.
- Namespace the job-filter cache per entity to avoid a product/category cache collision.
- Log a warning when a product is skipped because no channel/locale mapping matched.

# 1.0.1 - (04-05-2026)

## Changed
- Compatibility with UnoPIM v2.0.0.

# 1.0.0 - "Here We Go" (08-11-2024)

## Features  
- Export categories from Unopim as collections in Bagisto.  
- Export attributes seamlessly from Unopim to Bagisto.  
- Export families from Unopim to Bagisto.  
- Export products from Unopim to Bagisto, including both simple and configurable products.  
- Utilize a bulk API for faster product export.  
- Sync product images and videos from Unopim to Bagisto.  
- Fully compatible with AWS S3 for image storage and retrieval.  
