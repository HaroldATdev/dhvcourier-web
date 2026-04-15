# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WPCargo User Management is a WordPress plugin addon that manages user access and permissions for the WPCargo system. It provides user management functionality including user groups, access controls, role management, and default user assignments.

**Version**: 2.0.3
**Text Domain**: wpcargo-umanagement
**PHP Compatibility**: PHP 8.3+

## Core Architecture

### Plugin Entry Point
- **Main file**: `wpcargo-user-management.php` - Defines constants, loads all includes
- **Constants defined**:
  - `WPCU_MANAGEMENT_FILE`, `WPCU_MANAGEMENT_VERSION`, `WPCU_MANAGEMENT_DB_VERSION`
  - `WPCU_MANAGEMENT_DB_USER_GROUP` - Database table name for user groups
  - `WPCU_MANAGEMENT_URL`, `WPCU_MANAGEMENT_PATH`, `WPCU_MANAGEMENT_BASENAME`

### Includes Structure
The plugin loads files in this specific order:
1. `includes/intl.php` - Internationalization labels and text functions
2. `includes/pages.php` - Page creation and management
3. `includes/functions.php` - Core helper functions
4. `includes/scripts.php` - Asset loading (CSS/JS)
5. `includes/hooks.php` - WordPress hooks and filter callbacks
6. `includes/users.php` - **ionCube encoded** - Core user management logic
7. `includes/install-db.php` - Database table creation
8. `includes/ajax.php` - AJAX handlers for frontend operations

### Database Schema

**User Group Table**: `{prefix}wpcsr_user_group`
- `user_group_id` (int, primary key, auto-increment)
- `label` (varchar 100)
- `description` (varchar 255)
- `users` (varchar 100, serialized array)

### Template System

Templates are located in two directories:
- `templates/` - Frontend templates
- `admin/templates/` - Backend admin templates

**Template Loading**: Uses `wpcumanage_locate_template()` and `wpcumanage_admin_locate_template()` functions that support theme overrides in `{theme}/wpcargo/wpcargo-user-management/`

**Frontend Templates**:
- `dashboard.php` - Main dashboard layout
- `user-form.php` - Add/edit user form
- `user-search.php` - User search interface
- `access-form.php` - User access permissions form
- `assignment-form.php` - Default user assignments
- `role-editor.php` - Role editor interface
- `user-password.php` - Password management
- `user-error.php` - Error display
- `restriction.php` - Access restriction messages
- `users-group.tpl.php` - User groups table

## Key Features

### 1. User Management
- Add, update, delete users with custom fields (personal info, billing address)
- User approval system for pending clients
- Username and email editing (role-restricted)
- User groups assignment
- User ID generation with country prefix

### 2. Access Control System
Available access permissions (defined in `wpcumanage_access_list()`):
- `add`, `update`, `delete` - Shipment CRUD operations
- `invoice`, `label`, `waybill`, `bol` - Document printing
- `assign_client`, `assign_agent`, `assign_employee` - User assignments

### 3. User Assignment System
Default users can be assigned for automatic population:
- Default Client (`__default_client`)
- Default Agent (`__default_agent`)
- Default Employee (`__default_employee`)
- Default Driver (`__default_driver`) - if POD Signature addon active
- Default Branch (`__default_branch`) - if Branch Manager addon active
- Default Branch Manager (`__default_branch_manager`)

### 4. User Groups
- Create custom user groups
- Assign multiple users to groups
- Groups stored in custom database table
- User meta `user_groups` stores user's group memberships (serialized array)

### 5. Branch Integration
If WPC_Branch_Manager is active:
- Auto-assigns shipments to branches based on user role
- Supports branch manager, agent, employee, driver, client assignments
- Falls back to default branch if user has no branch assignment

## Role-Based Permissions

### Registered Roles
Defined in `wpcumanage_registered_roles()`:
- `wpc_shipment_manager`
- `wpcargo_employee`
- `wpcargo_branch_manager`
- `cargo_agent`
- `wpcargo_driver`
- `wpc_cashier`
- `wpcargo_client`
- `wpcargo_pending_client`

### Permission Functions
- `can_wpcumanage_access()` - Check if user can access user management module
- `can_wpcumanage_add()` - Check if user can add new users (default: administrator only)
- `can_wpcumanage_update()` - Check if user can update users
- `can_wpcumanage_delete()` - Check if user can delete users (default: administrator only)

Default access/update roles: `administrator`, `wpcargo_employee` (configurable via settings)

## AJAX Endpoints

All AJAX actions use both `wp_ajax_` and `wp_ajax_nopriv_` hooks:

- `wpcumanage_save_user_group` - Save/update user group
- `wpcumanage_get_user_group_data` - Get user group for editing (admin)
- `frontend_get_user_group_data` - Get user group for editing (frontend)
- `wpcumanage_delete_user_group` - Delete single user group
- `wpcumanage_ug_bulk_delete` - Bulk delete user groups
- `branch_options` - Get branch-related options (managers, agents, employees, drivers, clients)

## WordPress Hooks & Actions

### Custom Action Hooks
**User Table Display**:
- `wpcumanage_user_table_header` - Add table header columns
- `wpcumanage_user_table_data` - Add table data cells (receives `$user` parameter)
- `wpcumanage_before_user_table` - Before user table
- `wpcumanage_before_user_form` - Before user form

**User Groups**:
- `wpcumanage_user_group_before_form` - Before group form
- `wpcumanage_user_group_after_form` - After group form

**User Saving**:
- `wpcumanage_after_save_user` - After user is saved (receives `$user_data`, `$data`)
- `um_after_save_user_data` - User meta updated (receives `$user_id`, `$data`)

**User Form**:
- `wpcumanage_user_form_middle` - In the middle of user form (receives `$user_data`, `$is_update`)

### Custom Filters
- `wpcumanage_assignment_fields` - Modify assignment field definitions
- `wpcumanage_registered_roles` - Modify registered role list
- `wpcumanage_default_users` - Modify default users array
- `wpcumanage_access_list` - Modify available access permissions
- `can_wpcumanage_access_roles` - Modify roles that can access module
- `can_wpcumanage_add_roles` - Modify roles that can add users
- `can_wpcumanage_delete_roles` - Modify roles that can delete users
- `wpcumanage_locate_template_{$file_slug}` - Modify template path
- `wpcfe_registered_styles` - Add plugin styles
- `wpcfe_registered_scripts` - Add plugin scripts

## Integration Points

### WPCargo Frontend Manager Integration
- Uses `wpcfe_personal_info_fields()` and `wpcfe_billing_address_fields()` for user fields
- Integrates with `wpcfe_get_clients()` for client lists
- Uses `wpcargo->user_fullname()` for display names

### WPCargo Branch Manager Integration
- Checks `class_exists('WPC_Branch_Manager')`
- Uses `wpcbm_get_all_branch()` to get branches
- Uses `wpcdm_get_branch()` to get specific branch
- Auto-assigns shipments to branches on creation

### WPCargo POD Signature Integration
- Checks `class_exists('WPC_POD_Signature_Metabox')`
- Adds driver assignment field when active

### WooCommerce Integration
- Uses `WC()->countries->get_countries()` for country code lookup
- Function `wc_get_country_code_by_name()` maps country names to codes for user ID prefixes

## Internationalization

All translatable strings use text domain `wpcargo-umanagement`

Label functions in `includes/intl.php`:
- `wpcumanage_*_label()` functions - All filterable for customization

## Important Notes

### Encoded Files
- `includes/users.php` is **ionCube encoded** - cannot be modified directly
- Contains core user CRUD operations and business logic

### User Meta Keys
- `user_groups` - Serialized array of group IDs
- `_wpcargo_access` - Array of access permissions
- `__default_client`, `__default_agent`, etc. - Default user assignments
- `billing_*` - WooCommerce billing fields
- Personal info fields: `first_name`, `last_name`, `billing_email`, `billing_phone`

### Page Creation
Plugin automatically creates a "Users" page with shortcode `[wpcargo_users]`
- Page ID stored in option `wpcumanage_users_page`
- Uses `dashboard.php` template

### Settings Integration
Settings stored in `wpcargo_option_settings` option array:
- `acces_um_role` - Roles that can access user management
- `update_um_role` - Roles that can update users

## Working with This Codebase

### Adding New Access Permissions
1. Filter `wpcumanage_access_list` to add new permission keys and labels
2. Update frontend form in `templates/access-form.php`
3. Handle permission checking in shipment operations

### Adding New Assignment Fields
1. Filter `wpcumanage_assignment_fields` with field definition
2. Include `target_name` (meta key) and `target_role` (user role)
3. Field will automatically appear in assignment form

### Extending User Table Columns
1. Hook into `wpcumanage_user_table_header` to add header
2. Hook into `wpcumanage_user_table_data` to add data cell
3. Both hooks receive `$user` object in data callback

### Customizing Templates
Place custom template in theme directory:
`{theme}/wpcargo/wpcargo-user-management/{template-name}.php`

### User Group Auto-Sync
The plugin syncs user groups on every page load (`wp_head` hook):
- Reads all group memberships from database table
- Updates each user's `user_groups` meta if not exists
- Ensures consistency between group table and user meta
