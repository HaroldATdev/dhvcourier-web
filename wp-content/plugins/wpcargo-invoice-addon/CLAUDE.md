# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WPCargo Invoice Addon is a WordPress plugin that creates and manages invoices for shipments in the WPCargo ecosystem. It integrates with WPCargo, WooCommerce, and several WPCargo add-ons to provide comprehensive invoice management functionality.

**Version:** 4.0.0
**Text Domain:** wpcargo-invoice
**PHP Compatibility:** 8.3+

## Dependencies

This plugin requires the following plugins to be active:
- **WPCargo** (core plugin)
- **WPCargo Custom Field Add-ons** (required)
- **WPCargo Frontend Manager** (required)
- **WPTaskForce License Helper** (for license activation)

Optional integrations with:
- WooCommerce (for order integration)
- WPCargo Parcel Quotation
- WPCargo Shipping Rate
- WPCargo Vehicle Rate (Delivery)
- WPCargo Shipment Consolidation

## Architecture

### Core Files Structure

**Main Plugin File:**
- `wpcargo-invoice-addon.php` - Plugin bootstrap, defines constants and loads all includes

**Constants Defined:**
- `WPC_INVOICE_URL` - Plugin directory URL
- `WPC_INVOICE_PATH` - Plugin directory path
- `WPC_INVOICE_VERSION` - Current version (4.0.0)
- `WPC_INVOICE_BASENAME` - Plugin basename
- `WPC_INVOICE_UPDATE_REMOTE` - Update source identifier

**Includes Directory (`includes/`):**
- `post_type.php` - Registers `wpcargo_invoice` custom post type (non-public, internal use only)
- `functions.php` - Core helper functions (permissions, invoice operations, formatting, data retrieval)
- `intl.php` - Internationalization and label functions with filter support
- `pages.php` - Auto-creates invoice dashboard page with `[wpcargo_invoices]` shortcode
- `invoice.php` - Main invoice generation and AJAX handling (large file ~26k tokens)
- `scripts.php` - Enqueues CSS/JS assets
- `hooks.php` - WordPress/WPCargo hooks integration and third-party addon integrations
- `shortcode.php` - `[wpcargo_invoices]` shortcode implementation
- `settings.php` - Admin settings registration and navigation
- `print-hooks.php` - Print template customization hooks
- `autoupdate.php` - Plugin auto-update mechanism

### Template System

Templates are located in `templates/` and can be overridden by themes in `{theme}/wpcargo/invoice/{template-name}.php`

**Key Templates:**
- `dashboard.php` - Main invoice listing page
- `update-invoice.tpl.php` - Invoice editing interface with shipment details
- `invoice.tpl.php` - Printable invoice PDF template
- `multiple-package.tpl.php` - Package fields management
- `invoice-export.tpl.php` - Export interface
- `restriction.php` - Access denied template
- `no-fm-addon-error.tpl.php` - Error when Frontend Manager is not active

**Template Loading:** Use `wpcinvoice_locate_template( $file_name )` function to respect theme overrides.

### Data Model

**Custom Post Types:**
- `wpcargo_invoice` - Invoice records (non-public, linked to shipments via meta)
- Uses `wpcargo_shipment` from WPCargo core

**Key Post Meta:**
- `__wpcinvoice_id` - Links shipment to invoice post ID
- `__wpcinvoice_status` - Invoice status (wpci-paid, wpci-unpaid, wpci-cancelled, wpci-return, wpci-refund)
- `__wpcinvoice_number` - Global option that auto-increments (12-digit padded)
- `__wpcinvoice_history` - Serialized array of invoice changes
- `wpc-multiple-package` - Serialized package data with pricing

**Invoice Statuses:**
Defined in `wpcinvoice_status_list()`:
- wpci-paid
- wpci-unpaid (default)
- wpci-cancelled
- wpci-return
- wpci-refund

### Permission System

**User Role Access:**
- Default roles with access: `administrator`, `wpcargo_employee`
- Check with `can_wpcinvoice_access()` for general access
- Check with `can_wpcinvoice_access_package()` for package field access
- Use filters `can_wpcinvoice_access` and `can_wpcinvoice_access_package` to customize

### Invoice Number Generation

Auto-incremented via global option `__wpcinvoice_number`:
```php
wpcinvoice_generate_invoice_number() // Returns 12-digit padded number
```

### Integration Architecture

The plugin dynamically integrates with different shipment types through hooks and filters:

**Shipment Types Supported:**
- Default (standard WPCargo)
- Parcel Quotation - Adds additional charges fields
- Shipping Rate - Calculates insurance and pickup rates
- Delivery (Vehicle Rate) - Uses delivery charge calculations
- Shipment Consolidation - Aggregates multiple shipment costs

**Integration Points:**
- `wpcinvoice_total_fields_callback()` - Merges addon-specific cost fields
- `wpcinvoice_get_total_value_ccallback()` - Calculates totals per shipment type
- `wpcinvoice_after_invoice_tax_ccallback()` - Renders addon costs in printable invoice
- `wpcinv_delivery_data_template()` - Renders delivery/consolidation details

### Package Fields & Pricing

Package data structure:
```php
[
    'wpc-pm-qty' => quantity,
    'unit-price' => per-unit cost (shipping cost),
    'unit-amount' => total cost (qty × unit-price)
]
```

**Calculation Flow:**
1. Retrieve package data via `wpcargo_get_package_data( $shipment_id )`
2. Calculate subtotal from packages
3. Apply tax from global `$wpcargo->tax`
4. Add shipment-type-specific charges
5. Store via `wpcinvoice_get_total_value( $shipment_id )`

### Shortcodes & Templates

**Main Shortcode:** `[wpcargo_invoices]`
- Displays invoice dashboard
- Handles `?wpcinvoice=update&id={shipment_id}` for editing
- Handles `?wpcinvoice=export` for CSV/XLS export
- Implements pagination and filtering

**Shortcode Replacement Tags:**
Available in invoice templates (see `wpcinvoice_shortcodes_list()`):
- `{wpcargo_tracking_number}`
- `{invoice_number}`
- `{admin_email}`, `{site_name}`, `{site_url}`
- All WPCargo custom field keys
- Dynamic field replacement via `wpcinvoice_replace_shortcodes_list( $post_id )`

### WooCommerce Integration

**Order Linking:**
Maps WooCommerce orders to shipments via meta keys:
```php
wpcinvoice_woo_order_shipment_map() // Returns addon → meta_key mapping
wpcinvoice_get_invoice_order( $shipment_id ) // Retrieves order ID
wpcinvoice_get_order_data( $order_id ) // Fetches order details
```

### AJAX Actions

Key AJAX handlers (defined in `invoice.php`):
- Invoice status updates
- Invoice printing
- Package data synchronization
- Export functionality

## Key Functions Reference

**Access Control:**
- `can_wpcinvoice_access()` - Check if user can access invoices
- `is_wpcinvoice_shipment( $id )` - Validate shipment exists

**Invoice Operations:**
- `wpcinvoice_generate_invoice_number()` - Generate new invoice number
- `wpcinvoice_number( $shipment_id )` - Get invoice number for shipment
- `wpcinvoice_status( $invoice_id )` - Get human-readable status
- `wpcinvoice_save_history( $invoice_id, $data )` - Log invoice changes

**Data Retrieval:**
- `wpcinvoice_shipment_id( $invoice_id )` - Get shipment from invoice
- `wpcinvoice_invoice_id( $invoice_number )` - Find invoice by number
- `wpcinvoice_get_total_value( $shipment_id )` - Calculate invoice totals

**Formatting:**
- `wpcinvoice_format_value( $value, $html )` - Format numbers with currency
- `wpcinvoice_currency()` - Get WooCommerce currency symbol

**Template System:**
- `wpcinvoice_locate_template( $file_name )` - Load template with theme override support

## Hook System

**Actions:**
- `wpcinvoice_table_header` - Add columns to invoice table
- `wpcinvoice_table_data` - Add data to invoice table rows
- `after_wpcinvoice_save_shipment` - After shipment data is saved
- `before_wpcinvoice_shipment_form_fields` - Before shipment form
- `after_wpcinvoice_shipment_form_fields` - After shipment form
- `wpcinvoice_after_package_table_row` - Add content after package rows
- `wpcinvoice_before_package_table_row` - Add content before packages
- `wpcinvoice_after_invoice_tax` - Add cost rows in printable PDF
- `wpcinvoice_package_info` - Add sections to print template
- `wpcinvoice_additional_details_script` - Enqueue custom JS for calculations

**Filters:**
- `wpcinvoice_status_list` - Customize invoice statuses
- `wpcinvoice_package_fields` - Modify package field definitions
- `wpcinvoice_total_fields` - Add/modify total calculation fields
- `wpcinvoice_get_total_value` - Override total calculations
- `wpcinvoice_shipment_sections` - Customize displayed shipment sections
- `wpcinvoice_locate_template_{$file_slug}` - Override specific template paths
- All label functions have filters (e.g., `wpcinvoice_number_label`)

## Development Guidelines

### Coding Standards
- **IMPORTANT:** Always reference `GUIA_ESTILOS.md` and `INSTRUCCIONES_FORMATEO_CODIGO.md` when making code changes
- Follow WordPress coding standards
- Use proper escaping: `esc_html__()`, `esc_attr()`, etc.
- Check `ABSPATH` constant at file start
- Use WordPress database methods with `$wpdb->prepare()` for SQL
- Maintain PHP 8.3+ compatibility

### Template Customization
To override templates in a theme:
1. Create directory: `{theme}/wpcargo/invoice/`
2. Copy template from `templates/` to theme directory
3. Modify as needed - plugin will use theme version

### Adding New Invoice Fields
1. Add to `wpcinvoice_total_fields()` filter
2. Update calculation in `wpcinvoice_get_total_value()` filter
3. Add rendering in template hooks (`wpcinvoice_after_invoice_tax`)
4. Update `templates/invoice.tpl.php` if needed for PDF output

### Debugging
- Invoice number stored in global option: `__wpcinvoice_number`
- Check invoice-shipment linking via `__wpcinvoice_id` meta on shipments
- Use `wpcinvoice_save_history()` to track changes
- Verify license activation status via WPTaskForce Helper

### License System
- Plugin requires license activation via WPTaskForce License Helper
- License check: `get_option(WPC_INVOICE_BASENAME)`
- Displays activation notice if not licensed

## Common Tasks

**Create invoice for shipment:**
Invoice creation is automatic via WPCargo Frontend Manager when creating shipments.

**Update invoice status:**
Use admin dashboard or AJAX handler with status from `wpcinvoice_status_list()`.

**Export invoices:**
Navigate to `?wpcinvoice=export` on invoice page, select filters, export to CSV/XLS.

**Customize invoice template:**
Override `templates/invoice.tpl.php` in theme at `wpcargo/invoice/invoice.tpl.php`.

**Add custom total field:**
Hook into `wpcinvoice_total_fields` filter and add field definition with label, field type, readonly status.
