# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**WPCargo Proof of Delivery Add-ons** (v5.0.0) is a WordPress plugin that extends WPCargo with proof of delivery functionality. It allows drivers to capture signatures, images, and delivery information using computers, tablets, or mobile devices (Android/iOS).

**Dependencies:**
- WPCargo (core plugin)
- WPCargo Frontend Manager (WPCFE)
- WPTaskForce License Helper
- PHP 8.3 compatible

## Code Architecture

### Main Entry Point
- `wpcargo-pod.php` - Plugin bootstrap file that:
  - Defines constants (WPCARGO_POD_URL, WPCARGO_POD_PATH, WPCARGO_POD_VERSION)
  - Registers custom user role: `wpcargo_driver` with upload capabilities
  - Loads core classes and templates

### Directory Structure

**admin/** - Backend administration
- `admin/admin.php` - Loads all admin classes and includes
- `admin/classes/` - Core admin functionality classes
  - `class-pod.php` - Main POD class (ionCube encrypted)
  - `class-pod-pickup.php` - Pickup route handling (ionCube encrypted)
  - `class-api.php` - REST API endpoints for mobile app integration
  - `class-pod-settings.php` - Admin settings interface
  - `wpc-pod-admin-scripts.php` - Admin script/style enqueuing
  - `wpc-pod-media-upload-restriction.php` - Media upload controls for drivers
- `admin/includes/` - Shared functions and utilities
  - `dashboard.php` - Dashboard UI components and AJAX handlers
  - `functions.php` - Helper functions for routes, reports, API data formatting
  - `api.php` - API integration hooks
- `admin/templates/` - Admin template files (.tpl.php)

**classes/** - Frontend functionality
- `wpc-pod-results.php` - Results display logic
- `wpc-pod-scripts.php` - Frontend script/style enqueuing
- `wpc-pod-function-ajax.php` - AJAX handlers for frontend

**templates/** - Frontend templates (.tpl.php)
- Signature capture forms
- Driver route planners (delivery and pickup)
- Dashboard components
- Report generation interfaces

**assets/** - CSS, JavaScript, and images

**languages/** - Translation files

**export-storage/** - Temporary storage for generated reports (auto-cleaned after 5 minutes)

### Key Architectural Patterns

1. **Encrypted Core Classes**: `class-pod.php` and `class-pod-pickup.php` are ionCube encrypted - cannot be modified directly
2. **Template Override System**: Templates can be overridden in theme directory at `theme/wpcargo/wpcargo-pod-addons/`
3. **Filter/Hook-Based Extensions**: Heavy use of WordPress filters for customization
4. **REST API Architecture**: Extends `WPCARGO_API` parent class for mobile app endpoints

## REST API Structure

All API routes are registered in `admin/classes/class-api.php` under namespace `wpcargo/v1`:

**Endpoint Pattern:** `/api/{apikey}/pod/{action}`

Key endpoints:
- `/pod/settings` - Get POD configuration
- `/pod/search/` - Search shipments (POST)
- `/pod/track/` - Track specific shipment
- `/pod/route/` - Get delivery route data
- `/pod/pickup_route/` - Get pickup route data
- `/pod/status` - Get all statuses
- `/pod/status/{status}` - Get shipments by status
- `/pod/shipment/{ID}/` - Get specific shipment details
- `/pod/login` - Driver authentication (POST)

Authentication: Uses API key from user meta `wpcargo_api`

## Database Schema

Uses WordPress post meta for all shipment data:
- `wpcargo_driver` - Assigned driver user ID
- `wpcargo_status` - Current shipment status
- `wpcargo-pod-signature` - Signature attachment ID
- `wpcargo-pod-image` - Comma-separated image attachment IDs
- `wpcargo_shipments_update` - Serialized history array

Custom fields stored via WPCargo Custom Fields addon in `{prefix}wpcargo_custom_fields` table.

## Key Functional Areas

### 1. Signature Capture
- Canvas-based signature pad (JavaScript)
- Saves as attachment in WordPress media library
- Linked to shipment via post meta
- Can be deleted by administrators (role-based permission)

### 2. Driver Route Planner
Two types: Delivery routes and Pickup routes

**Configuration (stored in wp_options):**
- `wpcpod_route_origin` / `wpcpod_pickup_route_origin` - Starting point (lat/lng/address)
- `wpcpod_route_status` / `wpcpod_pickup_route_status` - Which statuses to include
- `wpcpod_route_field` / `wpcpod_pickup_route_field` - Meta fields to build addresses
- `wpcpod_route_segment_info` / `wpcpod_pickup_route_segment_info` - Additional info per stop

**Route Generation:**
- Uses Google Distance Matrix API to calculate optimal order
- Requires Google Maps API key in option `shmap_api`
- Sorts waypoints by distance from origin
- Returns JSON with origin, destination, waypoints, shipment data

### 3. Driver Report Generation
- AJAX-based report generation (`wpcpod_generate_report`)
- Filters: Driver, Status, Date Range
- Exports to CSV/XLS format
- Files stored temporarily in `export-storage/` with timestamp
- Auto-cleanup after 5 minutes via `wpcpod_clean_dir()`

### 4. Image Upload
- Multiple images per shipment
- Custom image size: `wpcargo-pod-images` (290x250 cropped)
- Stored as comma-separated attachment IDs
- AJAX upload via `wpcpod_save_attachment`

## Important Functions Reference

### Route Functions
- `wpcpod_route_allowed_user($user_id)` - Check if user can access routes
- `wpcpod_route_shipments($user_id)` - Get shipments for delivery route
- `wpcpod_pickup_route_shipments($user_id)` - Get shipments for pickup route
- `wpcpod_get_route_address_order($user_id)` - Calculate delivery route order
- `wpcpod_get_pickup_route_address_order($user_id)` - Calculate pickup route order

### API Helper Functions
- `wpcpod_api_shipment_status()` - Get status list for API (slug => label)
- `wpcpod_api_fields_status()` - Get enabled POD fields (shipper/receiver)
- `wpcpod_api_delican_status()` - Get statuses to exclude from "all"

### Template Functions
- `wpcpod_include_admin_template($file_name)` - Load admin template with override support
- `wpcpod_include_template($file_name)` - Load frontend template with override support

### Permission Functions
- `wpcargo_pod_is_driver()` - Check if current user is driver
- `wpcpod_can_delete_signature()` - Check if user can delete signatures (admin only)
- `can_export_wpcpod_report()` - Check if user can export reports

### Data Functions
- `wpcpod_signature_field_list()` - Get fields to save with signature
- `wpcpod_report_headers()` - Get column headers for reports
- `wpcargo_pod_user_roles($user_id)` - Get user roles by ID

## Custom Hooks (Filters & Actions)

### Key Filters
- `wpcargo_pod_status` - Modify available statuses for POD
- `wpcpod_route_shipments_query` - Modify SQL for route shipments
- `wpcpod_route_shipment_data` - Add data to route shipment info
- `wpcpod_signature_field_list` - Modify signature form fields
- `wpcpod_report_headers` - Modify report column headers
- `pod_shipment_details_reponse` - Modify API shipment response
- `wpcargo_pod_current_history` - Modify history entry before save

### Key Actions
- `wpcargo_extra_pod_saving` - Triggered after POD save
- `wpcargo_extra_send_email_notification` - After email notification
- `wpcpod_after_sign_modal` - After signature modal HTML
- `wpcpod_after_route_planner` - After route planner content
- `wpcpod_pickup_after_route_planner` - After pickup route planner

## AJAX Actions

All AJAX actions support both logged-in and logged-out users (driver role):

- `wpcpod_signature-nonce` - Signature save handler
- `wpcpod_delete_image` - Remove POD image
- `wpcpod_save_attachment` - Save POD images
- `wpcpod_generate_report` - Generate driver report
- `wpcpod_remove_signature` - Remove signature (admin only)
- `wpcpod_generate_route_address` - Generate delivery route
- `wpcpod_generate_pickup_route_address` - Generate pickup route

## Shortcodes

- `[wpc_driver_accounts]` - Driver account interface
- `[wpcpod_report]` - Driver report generation form
- `[wpcpod_route]` - Delivery route planner map
- `[wpcpod_pickup_route]` - Pickup route planner map

## Auto-Generated Pages

Created on plugin activation (if not exist):
- "Driver Report" - `wpcpod-report-order` slug
- "Driver Route Planner" - `wpcpo-route` slug
- "Pickup Driver Route Planner" - `wpcpo-pickup-route` slug

Stored in options: `wpcpod_page_report`, `wpcpod_route_page`, `wpcpod_pickup_route_page`

## Code Formatting Standards

When modifying code, always adhere to:
- **GUIA_ESTILOS.md** - Code style guide
- **INSTRUCCIONES_FORMATEO_CODIGO.md** - Code formatting instructions

These files are referenced in the global user configuration and must be followed for all code changes.

## Common Development Scenarios

### Adding a New POD Field
1. Register field via filter `wpcpod_signature_field_list`
2. Update template `admin/templates/wpc-pod-sign.tpl.php`
3. Handle save in `wpcargo_pod_signed_load_action()` (dashboard.php:90)

### Modifying Route Logic
- Cannot modify encrypted classes directly
- Use filters: `wpcpod_route_shipments_query`, `wpcpod_route_shipment_data`
- Override templates in theme directory

### Adding API Endpoint
1. Register route in `WPCARGO_POD_API::pod_routes()`
2. Add callback method in same class
3. Use `wpcapi_get_apikey_user()` for authentication
4. Return data as array (auto-converted to JSON)

### Debugging
- Error logs in encrypted files cannot be added
- Use `do_action()` hooks to tap into encrypted class workflow
- Check AJAX responses via browser dev tools
- Verify Google Maps API key in option `shmap_api`

## Important Notes

- Core POD classes are ionCube encrypted - modifications require source access
- Plugin requires license activation via WPTaskForce License Helper
- Google Maps API key required for route planning features
- Driver role (`wpcargo_driver`) auto-created on activation
- Temporary export files auto-delete after 5 minutes
- All driver access requires WPCFE plugin active
