# Invoice Manager For WordPress

A full-featured **frontend invoice management system** for WordPress. Create, edit, delete, and export invoices as PDFs — all from the frontend, with no admin access required for clients.

## Features

- 📄 Create & edit invoices from the frontend
- 🧾 Generate & download PDF invoices (print-ready)
- 💰 Multi-currency support (USD, EUR, GBP, BDT, INR, AUD, CAD)
- 🔁 Fixed & hourly billing types per line item
- 📊 Real-time invoice total calculation
- 🔒 Secure: nonce-verified, user-scoped (users only see their own invoices)
- 🌐 Fully translatable (`.pot` ready)
- 🚀 Lightweight — no external dependencies

## Installation

1. Upload the `mir-invoice-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins** in WordPress admin
3. A page called **Invoice Manager** is automatically created at `/invoice-manager/` with the shortcode `[mir_invoice_manager]`
4. Visit `/invoice-manager/` while logged in to start managing invoices

## Shortcodes

| Shortcode | Description |
|---|---|
| `[mir_invoice_manager]` | Full invoice app (dashboard + create/edit) |
| `[invoice_dashboard]` | Invoice list only |
| `[invoice_create]` | Create form only |
| `[invoice_edit id="X"]` | Edit form for invoice ID |

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Users must be logged in to use the plugin

## Author

**Mir Monoarul Alam** — [anik_cse@live.com](mailto:anik_cse@live.com)

> ✨ **This plugin was developed using 100% Vibe Coding** — designed and built entirely through AI-assisted development.

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
