<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

function wpcbranch_compat_register_integrations() {
    add_filter( 'wpcfe_is_user_shipment', 'wpcbranch_compat_access_shipment', 20, 2 );
    add_filter( 'can_wpcfe_access_dashboard', 'wpcbranch_compat_dashboard_access', 20, 1 );
    add_filter( 'can_wpcfe_update_shipment', 'wpcbranch_compat_update_shipment_access', 20, 1 );
    add_filter( 'wpcfe_dashboard_meta_query', 'wpcbranch_compat_dashboard_meta_query', 20, 1 );
    add_filter( 'wpcfe_get_report_count_sql', 'wpcbranch_compat_replace_assignment_meta_key', 20, 3 );
    add_filter( 'wpcfe_get_all_shipment_count_sql', 'wpcbranch_compat_replace_assignment_meta_key', 20, 2 );
    add_filter( 'wpcfe_get_shipment_status_count_sql', 'wpcbranch_compat_replace_assignment_meta_key', 20, 2 );
    add_filter( 'wpcfe_get_user_unseen_shipments_sql', 'wpcbranch_compat_replace_assignment_meta_key', 20, 1 );
    add_filter( 'wpcumanage_assignment_fields', 'wpcbranch_compat_assignment_fields', 20, 1 );
    add_action( 'after_wpcfe_save_shipment', 'wpcbranch_compat_assign_shipment_branch', 5, 2 );
    if ( ! has_action( 'wp_ajax_branch_options' ) ) {
        add_action( 'wp_ajax_branch_options', 'wpcbranch_compat_branch_options_callback' );
    }
}
add_action( 'plugins_loaded', 'wpcbranch_compat_register_integrations', 30 );

function wpcbranch_compat_is_branch_manager( $user_id = 0 ) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }

    $user = get_userdata( $user_id );

    return $user && in_array( 'wpcargo_branch_manager', (array) $user->roles, true );
}

function wpcbranch_compat_get_role_column_map() {
    return array(
        'wpcargo_branch_manager' => 'branch_manager',
        'wpcargo_employee'       => 'branch_employee',
        'cargo_agent'            => 'branch_agent',
        'wpcargo_client'         => 'branch_client',
        'wpcargo_driver'         => 'branch_driver',
    );
}

function wpcbranch_compat_extract_branch_user_ids( $branch ) {
    $branch  = (array) $branch;
    $columns = array_values( wpcbranch_compat_get_role_column_map() );
    $users   = array();

    foreach ( $columns as $column ) {
        if ( empty( $branch[ $column ] ) ) {
            continue;
        }

        $branch_users = maybe_unserialize( $branch[ $column ] );
        if ( ! is_array( $branch_users ) || empty( $branch_users ) ) {
            continue;
        }

        foreach ( $branch_users as $user_id ) {
            $user_id = (int) $user_id;
            if ( $user_id ) {
                $users[] = $user_id;
            }
        }
    }

    return array_values( array_unique( $users ) );
}

function wpcbranch_compat_find_user_branch_id( $user_id = 0, $ignore_meta = false ) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if ( ! $user_id ) {
        return 0;
    }

    if ( ! $ignore_meta ) {
        $branch_id = (int) get_user_meta( $user_id, 'wpc_user_branch', true );
        if ( $branch_id ) {
            return $branch_id;
        }
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return 0;
    }

    foreach ( wpcbranch_compat_get_role_column_map() as $role => $column ) {
        if ( ! in_array( $role, (array) $user->roles, true ) ) {
            continue;
        }

        $branch = wpcbranch_get_branch_by_role_id( $role, $user_id );
        if ( $branch && ! empty( $branch->id ) ) {
            return (int) $branch->id;
        }
    }

    return 0;
}

function wpcbranch_compat_sync_branch_user_meta( $branch_id = 0, $previous_members = array() ) {
    $branch_id        = (int) $branch_id;
    $previous_members = array_map( 'intval', (array) $previous_members );
    $branch           = $branch_id ? wpcdm_get_branch( $branch_id ) : array();
    $current_members  = ! empty( $branch ) ? wpcbranch_compat_extract_branch_user_ids( $branch ) : array();
    $affected_users   = array_unique( array_merge( $previous_members, $current_members ) );

    foreach ( $affected_users as $user_id ) {
        if ( ! $user_id ) {
            continue;
        }

        $resolved_branch = wpcbranch_compat_find_user_branch_id( $user_id, true );
        if ( $resolved_branch ) {
            update_user_meta( $user_id, 'wpc_user_branch', $resolved_branch );
        } else {
            delete_user_meta( $user_id, 'wpc_user_branch' );
        }
    }
}

function wpcbranch_compat_sync_all_user_branch_meta() {
    $branches           = wpcbm_get_all_branch( -1 );
    $assigned_user_ids  = array();
    $existing_meta_ids  = get_users(
        array(
            'meta_key' => 'wpc_user_branch',
            'fields'   => 'ids',
        )
    );

    if ( ! empty( $branches ) ) {
        foreach ( $branches as $branch ) {
            $branch_users = wpcbranch_compat_extract_branch_user_ids( $branch );
            foreach ( $branch_users as $user_id ) {
                $assigned_user_ids[] = $user_id;
                update_user_meta( $user_id, 'wpc_user_branch', (int) $branch->id );
            }
        }
    }

    $assigned_user_ids = array_unique( array_map( 'intval', $assigned_user_ids ) );
    foreach ( (array) $existing_meta_ids as $user_id ) {
        $user_id = (int) $user_id;
        if ( in_array( $user_id, $assigned_user_ids, true ) ) {
            continue;
        }

        delete_user_meta( $user_id, 'wpc_user_branch' );
    }
}

function wpcbranch_compat_get_default_branch() {
    global $wpdb;

    $table = $wpdb->prefix . WPC_BRANCHES_TABLE;
    $sql   = apply_filters( 'wpcumanage_get_default_branch_sql', "SELECT * FROM {$table} ORDER BY `id` ASC LIMIT 1" );

    return $wpdb->get_row( $sql );
}

function wpcbranch_compat_branch_default_meta( $branch, $meta_key ) {
    $branch = (array) $branch;
    $map    = array(
        'wpcargo_branch_manager' => 'branch_manager',
        'agent_fields'           => 'branch_agent',
        'wpcargo_driver'         => 'branch_driver',
        'wpcargo_employee'       => 'branch_employee',
    );

    if ( ! array_key_exists( $meta_key, $map ) || empty( $branch[ $map[ $meta_key ] ] ) ) {
        return 0;
    }

    $users = maybe_unserialize( $branch[ $map[ $meta_key ] ] );
    if ( ! is_array( $users ) || empty( $users ) ) {
        return 0;
    }

    return (int) reset( $users );
}

function wpcbranch_compat_apply_branch_assignment( $shipment_id, $branch, $data = array(), $force_branch = false ) {
    $branch = (array) $branch;
    if ( empty( $branch['id'] ) ) {
        return false;
    }

    $shipment_id = (int) $shipment_id;
    $meta_map    = array(
        'agent_fields',
        'wpcargo_driver',
        'wpcargo_employee',
    );

    if ( $force_branch || ! (int) get_post_meta( $shipment_id, 'shipment_branch', true ) ) {
        update_post_meta( $shipment_id, 'shipment_branch', (int) $branch['id'] );
    }

    foreach ( $meta_map as $meta_key ) {
        $posted_value = isset( $data[ $meta_key ] ) ? (int) $data[ $meta_key ] : 0;
        if ( $posted_value ) {
            continue;
        }

        $current_value = (int) get_post_meta( $shipment_id, $meta_key, true );
        if ( $current_value ) {
            continue;
        }

        $default_value = wpcbranch_compat_branch_default_meta( $branch, $meta_key );
        if ( $default_value ) {
            update_post_meta( $shipment_id, $meta_key, $default_value );
        }
    }

    return true;
}

function wpcbranch_compat_assign_shipment_branch( $shipment_id, $data ) {
    if ( ! $shipment_id || ! defined( 'WPC_BRANCHES_VERSION' ) ) {
        return false;
    }

    $branch_id = (int) get_post_meta( $shipment_id, 'shipment_branch', true );
    if ( ! $branch_id && ! empty( $data['shipment_branch'] ) ) {
        $branch_id = (int) $data['shipment_branch'];
    }

    if ( ! $branch_id ) {
        $branch_id = wpcbranch_compat_find_user_branch_id();
    }

    if ( ! $branch_id ) {
        $branch = wpcbranch_compat_get_default_branch();
    } else {
        $branch = wpcdm_get_branch( $branch_id );
    }

    if ( empty( $branch ) ) {
        return false;
    }

    return wpcbranch_compat_apply_branch_assignment( $shipment_id, $branch, (array) $data, ! (int) get_post_meta( $shipment_id, 'shipment_branch', true ) );
}

function wpcbranch_compat_dashboard_access( $result ) {
    if ( $result ) {
        return $result;
    }

    return wpcbranch_compat_is_branch_manager() ? true : $result;
}

function wpcbranch_compat_update_shipment_access( $result ) {
    if ( $result ) {
        return $result;
    }

    return wpcbranch_compat_is_branch_manager() ? true : $result;
}

function wpcbranch_compat_access_shipment( $result, $shipment_id ) {
    if ( $result || ! wpcbranch_compat_is_branch_manager() ) {
        return $result;
    }

    $user_branch     = wpcbranch_compat_find_user_branch_id();
    $shipment_branch = (int) get_post_meta( $shipment_id, 'shipment_branch', true );

    return $user_branch && $shipment_branch === $user_branch;
}

function wpcbranch_compat_dashboard_meta_query( $meta_query ) {
    if ( ! wpcbranch_compat_is_branch_manager() ) {
        return $meta_query;
    }

    $user_branch = wpcbranch_compat_find_user_branch_id();
    if ( ! $user_branch ) {
        return $meta_query;
    }

    $meta_query['__assignment'] = array(
        'key'     => 'shipment_branch',
        'value'   => $user_branch,
        'compare' => '=',
    );

    return $meta_query;
}

function wpcbranch_compat_replace_assignment_meta_key( $sql ) {
    if ( ! wpcbranch_compat_is_branch_manager() || ! is_string( $sql ) || '' === $sql ) {
        return $sql;
    }

    $user_id     = get_current_user_id();
    $user_branch = wpcbranch_compat_find_user_branch_id();
    if ( ! $user_id || ! $user_branch ) {
        return $sql;
    }

    $sql = str_replace(
        array(
            "'registered_shipper'",
            "'agent_fields'",
            "'wpcargo_driver'",
            "'wpcargo_employee'",
        ),
        "'shipment_branch'",
        $sql
    );

    // Replace current-user comparisons with current-branch comparisons for branch managers.
    return str_replace(
        array(
            ' = ' . (int) $user_id,
            ' = ' . (string) (int) $user_id,
            " = '" . (int) $user_id . "'",
        ),
        ' = ' . (int) $user_branch,
        $sql
    );
}

function wpcbranch_compat_branch_managers_by_branch() {
    global $wpcargo;

    $managers = array();
    $branches = wpcbm_get_all_branch( -1 );

    if ( empty( $branches ) ) {
        return $managers;
    }

    foreach ( $branches as $branch ) {
        $branch_managers = maybe_unserialize( $branch->branch_manager );
        if ( ! is_array( $branch_managers ) || empty( $branch_managers ) ) {
            continue;
        }

        foreach ( $branch_managers as $manager_id ) {
            $manager_id = (int) $manager_id;
            if ( ! $manager_id ) {
                continue;
            }

            if ( $wpcargo && method_exists( $wpcargo, 'user_fullname' ) ) {
                $managers[ $branch->id ][ $manager_id ] = $wpcargo->user_fullname( $manager_id );
            } else {
                $managers[ $branch->id ][ $manager_id ] = wpcdm_get_user_displayname( $manager_id );
            }
        }
    }

    return array_filter( $managers );
}

function wpcbranch_compat_assignment_fields( $fields ) {
    $branches = wpcbm_get_all_branch( -1 );
    if ( empty( $branches ) ) {
        return $fields;
    }

    $branch_options = array();
    foreach ( $branches as $branch ) {
        $branch_options[ $branch->id ] = $branch->name;
    }

    $branch_fields = array();
    if ( ! array_key_exists( '__default_branch', $fields ) ) {
        $branch_fields['__default_branch'] = array(
            'label'       => __( 'Default Branch', 'wpcargo-umanagement' ),
            'type'        => 'select',
            'options'     => $branch_options,
            'required'    => false,
            'target_name' => 'shipment_branch',
            'target_role' => 'shipment_branch',
        );
    }

    if ( ! array_key_exists( '__default_branch_manager', $fields ) ) {
        $branch_fields['__default_branch_manager'] = array(
            'label'       => __( 'Default Branch Manager', 'wpcargo-umanagement' ),
            'type'        => 'select',
            'options'     => array(),
            'required'    => false,
            'attributes'  => 'readonly',
            'target_name' => 'wpcargo_branch_manager',
            'target_role' => 'wpcargo_branch_manager',
        );
    }

    if ( empty( $branch_fields ) ) {
        return $fields;
    }

    return $branch_fields + $fields;
}

function wpcbranch_compat_branch_options_callback() {
    global $wpcargo;

    $branch_id = isset( $_POST['branchID'] ) ? absint( $_POST['branchID'] ) : 0;
    $branch    = $branch_id ? wpcdm_get_branch( $branch_id ) : array();
    $data      = array(
        'manager'  => array(),
        'client'   => array(),
        'agent'    => array(),
        'employee' => array(),
        'driver'   => array(),
    );

    if ( empty( $branch ) ) {
        wp_send_json(
            array(
                'status'  => 'success',
                'results' => null,
                'message' => __( 'Branch not found', 'wpcargo-branches' ),
                'data'    => $data,
            )
        );
    }

    $map = array(
        'manager'  => 'branch_manager',
        'client'   => 'branch_client',
        'agent'    => 'branch_agent',
        'employee' => 'branch_employee',
        'driver'   => 'branch_driver',
    );

    foreach ( $map as $key => $column ) {
        $users = ! empty( $branch[ $column ] ) ? maybe_unserialize( $branch[ $column ] ) : array();
        if ( ! is_array( $users ) || empty( $users ) ) {
            continue;
        }

        foreach ( $users as $user_id ) {
            $user_id = (int) $user_id;
            if ( ! $user_id ) {
                continue;
            }

            if ( $wpcargo && method_exists( $wpcargo, 'user_fullname' ) ) {
                $data[ $key ][ $user_id ] = $wpcargo->user_fullname( $user_id );
            } else {
                $data[ $key ][ $user_id ] = wpcdm_get_user_displayname( $user_id );
            }
        }
    }

    wp_send_json(
        array(
            'status'  => 'success',
            'results' => 1,
            'message' => __( 'Branch options found', 'wpcargo-branches' ),
            'data'    => $data,
        )
    );
}