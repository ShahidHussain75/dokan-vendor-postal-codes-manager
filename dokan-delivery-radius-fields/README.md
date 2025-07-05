# Vendor Postal Codes Manager

## Plugin Overview

**Vendor Postal Codes Manager** is a WordPress plugin that allows administrators to manage postal codes and lets vendors select their delivery areas and set delivery prices/minimum order values for each area. It also provides a cuisine taxonomy for products, with image support for each cuisine.

---

## Features

- **Admin Postal Code Management:**
  - Add, edit, delete postal codes manually or via CSV import (see lines 201–400, 401–700).
  - View and delete imported CSV batches (lines 401–500).
- **Vendor Delivery Area Selection:**
  - Vendors can select delivery areas (postal codes) and set delivery prices/minimum order values for each (lines 700–900).
  - Validation for delivery price and minimum order value (lines 900–1000).
- **Cuisine Taxonomy:**
  - Register and manage cuisines as a taxonomy for products (lines 1000–1100).
  - Add images to cuisines (lines 1000–1100).
- **Admin UI:**
  - Custom admin pages and forms styled for usability (lines 201–700).
- **Data Persistence:**
  - Data is stored in a custom database table (`wp_vendor_postal_codes`).
  - User meta is used for vendor selections.
- **Clean Uninstall:**
  - Removes all plugin data on uninstall (lines 100–120).

---

## File Structure

- `delivery-radius-fields.php` — Main plugin file, contains all logic.
- `css/admin.css` — Custom admin styles.
- `js/admin.js` — Custom admin scripts.

---

## Line-by-Line Functionality

- **Lines 1–20:** Plugin header, security check.
- **Lines 21–60:** Database table creation for postal codes on activation.
- **Lines 61–100:** Default cuisines are added on activation.
- **Lines 101–120:** Uninstall and deactivation logic.
- **Lines 121–200:** Admin menu registration.
- **Lines 201–400:** Admin page for managing postal codes, including CSV import, batch deletion, and manual entry.
- **Lines 401–700:** Vendor settings UI for selecting delivery areas and setting prices/minimums.
- **Lines 701–1000:** Saving vendor selections, validation, and error display.
- **Lines 1001–1100:** Cuisine taxonomy registration, image field, and meta box for products.

---

## Requirements

- **WordPress 5.0+**
- **WooCommerce** (for product/cuisine taxonomy)
- **Dokan** (for vendor dashboard integration)
- PHP 7.2+

---

## Installation & Setup

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin via the WordPress admin.
3. Go to **Postal Codes** in the admin menu to manage postal codes.
4. Vendors can select delivery areas and set prices in their dashboard.

---

## Usage Details

- **Admin Postal Codes Page:**
  - Import CSV: Format should be `Area Name;Postal Code;State` (see lines 401–500).
  - Add/Edit/Delete postal codes manually.
  - Delete entire CSV import batches.
- **Vendor Dashboard:**
  - Select multiple delivery areas (postal codes).
  - Set delivery price and minimum order for each area.
  - Validation ensures all values are positive.
- **Cuisine Management:**
  - Admins can add/edit cuisines and assign images.
  - Vendors can assign cuisines to products.

---

## Further Understanding

- **Extensibility:**
  - The plugin can be extended to support more fields for postal codes or vendor settings.
  - Hooks and filters can be added for custom logic.
- **Security:**
  - Nonces are used for all forms to prevent CSRF.
  - Data is sanitized before database operations.
- **UI/UX:**
  - Uses Select2 for better multi-select experience.
  - Custom CSS/JS for improved admin and vendor UI.

---

## Support

For questions or support, contact the plugin author or open an issue on the repository.

---

## Author

Shahid Hussain
