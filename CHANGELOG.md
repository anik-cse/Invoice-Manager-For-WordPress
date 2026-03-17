# Changelog

All notable changes to **Mir Invoice Manager** will be documented in this file.

---

## [1.1.0] — 2026-03-17

### Added
- Full WordPress Admin Dashboard support via new `class-admin.php`
- Custom admin columns on the Invoice list screen: Client, Amount, Status, Due Date
- Admin metabox on the invoice edit screen with all fields: client/sender info, payment info, logo URL, line items, notes, status
- Live invoice total recalculation inside the admin metabox (via `admin.js`)
- Add/remove item rows dynamically in admin metabox
- `admin.css` and `admin.js` enqueued only on invoice screens
- `MIM_Admin` only initialised when `is_admin()` is true (no frontend overhead)

---

## [1.0.0] — 2026-03-17

### Added
- Initial release of Mir Invoice Manager
- Frontend invoice dashboard with Create, Edit, Delete, and PDF actions
- Multi-currency support: USD, EUR, GBP, BDT, INR, AUD, CAD
- Fixed & hourly billing types per line item
- Real-time invoice total calculation (JavaScript)
- Downloadable & printable PDF invoices (browser print dialog)
- Status management (Draft, Sent, Paid, Overdue) via inline dropdown
- CSRF protection via WordPress nonces on all actions (Create, Edit, AJAX)
- `uninstall.php` — cleans up all invoice data on plugin deletion
- Auto-creates `/invoice-manager/` page on activation
- Flushes rewrite rules on activation and deactivation
- `.gitignore` to exclude `.DS_Store`, `.bak`, and log files
- `README.md` with full documentation and author bio

### Security
- Nonce verification on all AJAX endpoints (`save`, `delete`, `update_status`)
- Nonce verification on `?mim_action=create` and `?mim_edit=ID` URL actions
- All user input sanitized via `sanitize_text_field`, `sanitize_email`, `esc_url_raw`, `sanitize_textarea_field`
- All output escaped via `esc_html`, `esc_attr`, `esc_url`
- User ownership check on all invoice operations

### Fixed
- Edit Invoice links were pointing to the invoice CPT URL instead of the shortcode page — fixed by saving `get_permalink()` before the WP_Query loop

### UI
- Aligned "Create Invoice" button and "Back to Dashboard" button with the invoice table
- Both title and button displayed on the same line using flexbox
- Removed underlines from all `.mim-btn` elements (theme override)
- Theme page header (`entry-header`) aligned to match the 950px plugin layout
- Invoice PDF logo size increased by 30%

---

*Changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format.*
