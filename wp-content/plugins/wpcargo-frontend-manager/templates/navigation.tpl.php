<!--Main Navigation-->
<?php
$create_active_class = ( get_the_ID() == wpcfe_admin_page() && isset( $_GET['wpcfe']) && $_GET['wpcfe'] == 'add' ) ? 'active' : '' ; 
$shipments_active_class = ( get_the_ID() == wpcfe_admin_page() && ( ! isset( $_GET['wpcfe'] ) || ( isset( $_GET['wpcfe'] ) && ! in_array( sanitize_text_field( wp_unslash( $_GET['wpcfe'] ) ), array( 'add', 'settings', 'dashboard' ), true ) ) ) ) ? 'active' : '';
$unseen_shipments  = wpcfe_disable_unseen() ? 0 : wpcfe_get_user_unseen_shipments();
$unseen  = $unseen_shipments > 9 ? '9&#43;' : $unseen_shipments ;

if ( ! function_exists( 'wpcfe_normalize_menu_url' ) ) {
	function wpcfe_normalize_menu_url( $url ) {
		$path = wp_parse_url( (string) $url, PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';
		return $path !== '' ? $path : '/';
	}
}

if ( ! function_exists( 'wpcfe_is_active_menu_link' ) ) {
	function wpcfe_is_active_menu_link( $permalink, $page_id = 0 ) {
		$page_id = (int) $page_id;
		$target_query = wp_parse_url( (string) $permalink, PHP_URL_QUERY );
		if ( $page_id > 0 && (int) get_queried_object_id() === $page_id && empty( $target_query ) ) {
			return true;
		}

		$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$current_url = home_url( $current_uri );

		$current_path = wpcfe_normalize_menu_url( $current_url );
		$target_path  = wpcfe_normalize_menu_url( $permalink );

		if ( $current_path !== $target_path ) {
			return false;
		}

		if ( empty( $target_query ) ) {
			return true;
		}

		$current_query = wp_parse_url( $current_url, PHP_URL_QUERY );
		parse_str( (string) $target_query, $target_args );
		parse_str( (string) $current_query, $current_args );

		foreach ( $target_args as $key => $value ) {
			if ( ! array_key_exists( $key, $current_args ) ) {
				return false;
			}
			if ( (string) $current_args[ $key ] !== (string) $value ) {
				return false;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'wpcfe_prepare_sidebar_menu_tree' ) ) {
	function wpcfe_get_explicit_parent_key( $item ) {
		$parent_aliases = array( 'parent', 'parent_key', 'parent-key', 'parent_slug', 'parent-slug', 'child_of', 'child-of' );
		foreach ( $parent_aliases as $alias ) {
			if ( ! empty( $item[ $alias ] ) ) {
				return sanitize_key( (string) $item[ $alias ] );
			}
		}
		return '';
	}

	function wpcfe_get_url_parts( $url ) {
		$path = wpcfe_normalize_menu_url( $url );
		$query = wp_parse_url( (string) $url, PHP_URL_QUERY );
		$args = array();
		parse_str( (string) $query, $args );
		return array( $path, (array) $args );
	}

	function wpcfe_find_inferred_parent_key( $node_key, $node, $all_nodes ) {
		$child_url = isset( $node['permalink'] ) ? $node['permalink'] : '';
		list( $child_path, $child_args ) = wpcfe_get_url_parts( $child_url );

		if ( empty( $child_path ) || empty( $child_args ) ) {
			return '';
		}

		$candidate_key = '';
		$candidate_arg_count = -1;

		foreach ( $all_nodes as $parent_key => $parent_node ) {
			if ( $parent_key === $node_key ) {
				continue;
			}

			$parent_url = isset( $parent_node['permalink'] ) ? $parent_node['permalink'] : '';
			list( $parent_path, $parent_args ) = wpcfe_get_url_parts( $parent_url );

			if ( $parent_path !== $child_path ) {
				continue;
			}

			if ( count( $parent_args ) >= count( $child_args ) ) {
				continue;
			}

			$is_subset = true;
			foreach ( $parent_args as $arg_key => $arg_value ) {
				if ( ! array_key_exists( $arg_key, $child_args ) || (string) $child_args[ $arg_key ] !== (string) $arg_value ) {
					$is_subset = false;
					break;
				}
			}

			if ( ! $is_subset ) {
				continue;
			}

			$parent_arg_count = count( $parent_args );
			if ( $parent_arg_count > $candidate_arg_count ) {
				$candidate_arg_count = $parent_arg_count;
				$candidate_key = (string) $parent_key;
			}
		}

		return $candidate_key;
	}

	function wpcfe_prepare_sidebar_menu_tree( $menu_items ) {
		$nodes = array();
		foreach ( (array) $menu_items as $key => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item['_key'] = (string) $key;
			$item['_children'] = array();
			$item['_parent'] = wpcfe_get_explicit_parent_key( $item );
			$nodes[ (string) $key ] = $item;
		}

		// Inferencia: si no hay parent explícito, intenta asociar por misma URL base con query más específica.
		foreach ( array_keys( $nodes ) as $node_key ) {
			if ( ! empty( $nodes[ $node_key ]['_parent'] ) ) {
				continue;
			}
			$inferred_parent = wpcfe_find_inferred_parent_key( $node_key, $nodes[ $node_key ], $nodes );
			if ( ! empty( $inferred_parent ) ) {
				$nodes[ $node_key ]['_parent'] = $inferred_parent;
			}
		}

		$roots = array();
		foreach ( array_keys( $nodes ) as $node_key ) {
			$parent_key = $nodes[ $node_key ]['_parent'];
			if ( $parent_key && isset( $nodes[ $parent_key ] ) ) {
				$nodes[ $parent_key ]['_children'][ $node_key ] = &$nodes[ $node_key ];
			} else {
				$roots[ $node_key ] = &$nodes[ $node_key ];
			}
		}

		return $roots;
	}
}

if ( ! function_exists( 'wpcfe_sidebar_item_is_active' ) ) {
	function wpcfe_sidebar_item_is_active( $menu_key, $item ) {
		$page_id = isset( $item['page-id'] ) ? (int) $item['page-id'] : 0;
		$permalink = isset( $item['permalink'] ) ? $item['permalink'] : '';
		$is_active = wpcfe_is_active_menu_link( $permalink, $page_id );

		// Compatibilidad con plugins que ya inyectan "active" en la key.
		if ( ! $is_active && strpos( (string) $menu_key, ' active' ) !== false ) {
			$is_active = true;
		}

		return $is_active;
	}
}

if ( ! function_exists( 'wpcfe_render_sidebar_menu_nodes' ) ) {
	function wpcfe_render_sidebar_menu_nodes( $nodes, $base_class = 'list-group-item waves-effect', $depth = 0 ) {
		$html = '';
		$has_active = false;

		foreach ( (array) $nodes as $menu_key => $item ) {
			$children = isset( $item['_children'] ) && is_array( $item['_children'] ) ? $item['_children'] : array();
			$has_children = ! empty( $children );

			$children_html = '';
			$children_active = false;
			if ( $has_children ) {
				list( $children_html, $children_active ) = wpcfe_render_sidebar_menu_nodes( $children, $base_class, $depth + 1 );
			}

			$self_active = wpcfe_sidebar_item_is_active( $menu_key, $item );
			$is_active = $has_children ? ( $self_active && ! $children_active ) : $self_active;
			$is_open = $has_children ? ( $children_active || $self_active ) : false;
			$has_active = $has_active || $is_active;

			$item_classes = trim( $base_class . ' ' . $menu_key . ' ' . ( $is_active ? 'active' : '' ) . ' ' . ( $depth > 0 ? 'wpcfe-submenu-link' : '' ) . ' ' . ( $has_children ? 'wpcfe-has-children wpcfe-parent-link' : '' ) );
			$permalink = isset( $item['permalink'] ) ? $item['permalink'] : '#';
			$label = isset( $item['label'] ) ? $item['label'] : '';
			$icon = isset( $item['icon'] ) ? $item['icon'] : '';

			$html .= '<a href="' . esc_url( $permalink ) . '" class="' . esc_attr( $item_classes ) . '">';
			if ( ! empty( $icon ) ) {
				$html .= '<i class="fa ' . esc_attr( $icon ) . ' mr-3"></i>';
			}
			$html .= wp_kses_post( $label );

			if ( $has_children ) {
				$submenu_id = 'wpcfe-submenu-' . substr( md5( (string) $menu_key . '-' . (string) $depth ), 0, 12 );
				$html .= '<span class="wpcfe-submenu-toggle" role="button" aria-controls="' . esc_attr( $submenu_id ) . '" aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '"><i class="fa fa-angle-down"></i></span>';
			}
			$html .= '</a>';

			if ( $has_children ) {
				$submenu_classes = 'wpcfe-submenu' . ( $is_open ? ' show' : '' );
				$html .= '<div id="' . esc_attr( $submenu_id ) . '" class="' . esc_attr( $submenu_classes ) . '">' . $children_html . '</div>';
			}
		}

		return array( $html, $has_active );
	}
}

if ( ! function_exists( 'wpcfe_render_sidebar_custom_menu' ) ) {
	function wpcfe_render_sidebar_custom_menu( $menu_items, $base_class = 'list-group-item waves-effect' ) {
		$tree = wpcfe_prepare_sidebar_menu_tree( $menu_items );
		list( $html ) = wpcfe_render_sidebar_menu_nodes( $tree, $base_class, 0 );
		return $html;
	}
}

if ( ! function_exists( 'wpcfe_get_combined_sidebar_custom_items' ) ) {
	function wpcfe_get_combined_sidebar_custom_items() {
		$menu_items = function_exists( 'wpcfe_after_sidebar_menu_items' ) ? wpcfe_after_sidebar_menu_items() : array();
		$menus = function_exists( 'wpcfe_after_sidebar_menus' ) ? wpcfe_after_sidebar_menus() : array();

		$menu_items = is_array( $menu_items ) ? $menu_items : array();
		$menus = is_array( $menus ) ? $menus : array();

		return array_merge( $menu_items, $menus );
	}
}

?>
<header>
    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-light white scrolling-navbar <?php echo is_rtl() ? 'rtl' : ''; ?>">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand waves-effect d-sm-inline-block d-md-inline-block d-lg-none" href="<?php echo bloginfo('url'); ?>">
                <img src="<?php echo wpcfe_dashboard_logo_url(); ?>" class="img-fluid" alt="<?php esc_html_e( 'Site Logo', 'wpcargo-frontend-manager' ); ?>" style="width: auto; margin:0 auto" />
            </a>
            <!-- Collapse -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMobileMenuContent"
                aria-controls="navbarMobileMenuContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Links -->
            <div class="collapse navbar-collapse" id="navbarMobileMenuContent">
            	<?php if( is_user_logged_in() ): ?>
					<div class="nav-section search-nav mr-auto w-50">
						<!-- Search form -->
						<form class="form-inline md-form form-sm active-cyan-2 my-0" method="GET" action="<?php echo $page_url; ?>">
							<i class="fa fa-search" aria-hidden="true"></i>
							<input type="hidden" name="wpcfe" value="track">
							<input class="form-control form-control-sm my-0 ml-2 w-75" type="text" name="num" placeholder="<?php echo apply_filters('wpcfe_track_shipment',esc_html__('Track Shipment', 'wpcargo-frontend-manager') ); ?>"
							aria-label="<?php echo apply_filters('wpcfe_track_shipment',esc_html__('Track Shipment', 'wpcargo-frontend-manager') ); ?>">					  
						</form>
					</div>
		        <?php endif; ?>
                <?php
					$wpcfe_top_menu_args = array(
						'echo' 			 => FALSE,
						'theme_location' => 'wpcfe-dashboard-top-menu',
						'menu_class'     => 'nav navbar-nav nav-flex-icons ml-auto',
						'link_before'    => '',
						'link_after'     => '',
						'walker'        => new WPCFE_Dashboard_Top_Menu(),
						'fallback_cb'   => false,
						'container'     => ''
					);
					echo wp_nav_menu( $wpcfe_top_menu_args );
	            ?>
                <div class="nav-section mobile-sidebar-menu d-sm-inline-block d-md-inline-block d-lg-none">
					<?php
						do_action( 'wpcfe_before_add_shipment' );
						if( wpcfe_admin_page() ){
							$user_roles = wpcfe_current_user_role();
							?>
							<?php if( !wpcfe_add_shipment_deactivated() ): ?>
								<?php if( can_wpcfe_add_shipment() ): ?>
									<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>/?wpcfe=add" class="list-group-item waves-effect <?php echo $create_active_class; ?>"> <i class="fa fa-plus mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_create_shipment', esc_html__('Create Shipment', 'wpcargo-frontend-manager') ); ?> </a>
								<?php endif;  
								endif;
						if( $unseen_shipments ){ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php echo $shipments_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', sprintf( __('Shipments <span class="badge badge-pill bg-danger align-top">%s</span>', 'wpcargo-frontend-manager'), $unseen ) ); ?> </a>
					<?php } else{ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php echo $shipments_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', esc_html__('Shipments', 'wpcargo-frontend-manager') ); ?> </a>
					<?php }
						}
						do_action( 'wpcfe_after_add_shipment' );

						$combined_mobile_sidebar = wpcfe_get_combined_sidebar_custom_items();
						if( !empty( $combined_mobile_sidebar ) ){
							echo wpcfe_render_sidebar_custom_menu( $combined_mobile_sidebar, 'list-group-item waves-effect' );
						}
					?>
					<?php
						$wpcfe_sidebar_menu_args = array(
							'theme_location' => 'wpcfe-dashboard-sidebar-menu',
							'menu_class' 	 => 'list-group list-group-flush',
							'link_before'  	 => '',
							'link_after' 	 => '',
							'walker' 		=> new WPCFE_Dashboard_Sidebar_Menu(),
							'fallback_cb'   => false,
						);
						wp_nav_menu( $wpcfe_sidebar_menu_args );
						do_action( 'wpcfe_after_sidebar_custom_menu' ); 
					?>
		        </div>
		          <?php if( is_user_logged_in() ): ?>
					<div class="nav-section nav-account-dropdown <?php if( empty( wp_nav_menu( $wpcfe_top_menu_args ) ) ) { echo 'ml-auto'; } ?> <?php echo wp_is_mobile() ? 'my-4' : '' ; ?>">
						<?php
							$fullname = $wpcargo->user_fullname( get_current_user_id() );
							$user_avatar = wpcfe_user_avatar_url() ? '<img src="'.wpcfe_user_avatar_url().'" width="30" height="30">' : '<i class="fa fa-user-circle text-primary" style="font-size:30px;vertical-align: middle;"></i>' ;
						?>
						<a href="#" class="nav-wpcfe-account">
							<?php echo $user_avatar; ?>
							<span class="account-label"><?php echo $fullname; ?></span>
						</a>
						<ul class="account-dropdown">
							<li>
								<?php 
									$acount_link = get_the_permalink( wpc_profile_get_frontend_page() );
									$acount_link = apply_filters('profile_acount_link', $acount_link );
								?>
								<a href="<?php echo $acount_link; ?>"><?php esc_html_e( 'My Profile', 'wpcargo-frontend-manager' ); ?></a>
							</li>
							<!--<li><a href="#"><?php esc_html_e( 'Notifications', 'wpcargo-frontend-manager' ); ?></a></li>-->
							<?php do_action( 'wpcfe_after_profile_dropdown', get_current_user_id() ); ?>
							<li><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php esc_html_e( 'Logout', 'wpcargo-frontend-manager' ); ?></a></li>
						</ul>
					</div>
		        <?php endif; ?>
				<?php do_action('wpcfe_after_profile_icon', get_current_user_id());?>
            </div>
        </div>
    </nav>
	<style>
		/* css para scrolear el menu de navegacion en tableta*/
		.sidebar-fixed {
        max-height: 100vh !important; /* Limita al alto de la pantalla [cite: 50] */
        overflow-y: auto !important;  /* Habilita el scroll vertical [cite: 51] */
        padding-bottom: 60px !important; /* Espacio para que no se corte el último ítem */
    	}
		/* Estética del scrollbar para que sea sutil */
		.sidebar-fixed::-webkit-scrollbar {
			width: 5px;
		}
		.sidebar-fixed::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
    	}
		.wpcfe-has-children {
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		.wpcfe-submenu-toggle {
			margin-left: auto;
			padding-left: 8px;
			cursor: pointer;
		}
		.wpcfe-submenu {
			display: none;
		}
		.wpcfe-submenu.show {
			display: block;
		}
		.wpcfe-submenu-link {
			padding-left: 2.4rem !important;
			font-size: 0.95em;
		}
		.wpcfe-has-children.active .wpcfe-submenu-toggle i,
		.wpcfe-has-children.wpcfe-open .wpcfe-submenu-toggle i {
			transform: rotate(180deg);
		}
		.wpcfe-submenu-toggle i {
			transition: transform .2s ease;
		}
		@media (max-width: 991.98px) {
			#navbarMobileMenuContent {
				max-height: calc(100vh - 56px);
				overflow-y: auto;
				-webkit-overflow-scrolling: touch;
				padding-bottom: 12px;
			}
			#navbarMobileMenuContent .mobile-sidebar-menu {
				max-height: calc(100vh - 170px);
				overflow-y: auto;
				-webkit-overflow-scrolling: touch;
				padding-right: 4px;
			}
			#navbarMobileMenuContent .list-group-item {
				padding-top: .38rem;
				padding-bottom: .38rem;
				line-height: 1.1;
				font-size: .88rem;
			}
			#navbarMobileMenuContent .list-group-item i.fa {
				font-size: .88rem;
				margin-right: .35rem !important;
			}
			#navbarMobileMenuContent .wpcfe-submenu-link {
				padding-left: 1.6rem !important;
				font-size: .84rem;
			}
			#navbarMobileMenuContent .wpcfe-submenu-toggle {
				padding-left: 4px;
			}
			#navbarMobileMenuContent .badge {
				font-size: .62rem;
				padding: .15em .35em;
			}
			#navbarMobileMenuContent .mobile-sidebar-menu::-webkit-scrollbar {
				width: 5px;
			}
			#navbarMobileMenuContent .mobile-sidebar-menu::-webkit-scrollbar-thumb {
				background: rgba(0, 0, 0, 0.2);
				border-radius: 10px;
			}
		}
	</style>
    <!-- Navbar -->
    <!-- Sidebar -->
    <div class="sidebar-fixed position-fixed">
        <a class="logo-wrapper waves-effect d-block text-center" href="<?php echo bloginfo('url'); ?>">
        	<img src="<?php echo wpcfe_dashboard_logo_url(); ?>" class="img-fluid" alt="<?php esc_html_e( 'Site Logo', 'wpcargo-frontend-manager' ); ?>" style="width: auto; margin:0 auto" />
        </a>
        <div class="list-group list-group-flush">
			<?php
				if( wpcfe_admin_page() ){
					$user_roles = wpcfe_current_user_role();
					do_action( 'wpcfe_before_add_shipment' );
					if( !wpcfe_add_shipment_deactivated() ):
						if( can_wpcfe_add_shipment() ): ?>
							<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>?wpcfe=add" class="list-group-item waves-effect <?php echo $create_active_class; ?>"> 
								<i class="fa fa-plus mr-md-3 d-none d-lg-inline-block d-xl-inline-block"></i><?php echo apply_filters( 'wpcfe_create_shipment', esc_html__('Create Shipment', 'wpcargo-frontend-manager') ); ?> 
							</a>
						<?php endif;
					endif; 
					if( $unseen_shipments ){ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php echo $shipments_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', sprintf( __('Shipments <span class="badge badge-pill bg-danger align-top">%s</span>', 'wpcargo-frontend-manager'), $unseen ) ); ?> </a>
					<?php } else{ ?>
						<a href="<?php echo get_the_permalink( wpcfe_admin_page() ); ?>" class="list-group-item waves-effect <?php echo $shipments_active_class; ?>"> <i class="fa fa-cubes mr-md-3 mr-3"></i><?php echo apply_filters( 'wpcfe_shipments_menu', esc_html__('Shipments', 'wpcargo-frontend-manager') ); ?> </a>
					<?php }

					do_action( 'wpcfe_after_add_shipment' );
					$combined_desktop_sidebar = wpcfe_get_combined_sidebar_custom_items();
					if( !empty( $combined_desktop_sidebar ) ){
						echo wpcfe_render_sidebar_custom_menu( $combined_desktop_sidebar, 'list-group-item waves-effect' );
					}
				}
			?>
			<?php do_action( 'wpcfe_before_sidebar_custom_menu' ); ?>
			<?php
				$wpcfe_menu_args = array(
					'theme_location' => 'wpcfe-dashboard-sidebar-menu',
					'menu_class' 	 => 'list-group list-group-flush',
					'link_before'  	 => '',
					'link_after' 	 => '',
					'walker' 		=> new WPCFE_Dashboard_Sidebar_Menu(),
					'fallback_cb'   => false,
				);
				wp_nav_menu( $wpcfe_menu_args );
				do_action( 'wpcfe_after_sidebar_custom_menu' ); 
			?>
        </div>
    </div>
	<script>
	(function($){
		function wpcfeToggleSubmenu($trigger){
			var $submenu = $trigger.next('.wpcfe-submenu');
			if(!$submenu.length){
				return;
			}
			var $toggle = $trigger.find('.wpcfe-submenu-toggle').first();
			var willOpen = !$submenu.hasClass('show');

			$submenu.toggleClass('show', willOpen);
			$toggle.attr('aria-expanded', willOpen ? 'true' : 'false');
			$trigger.toggleClass('wpcfe-open', willOpen);
		}

		$(document).on('click', '.wpcfe-submenu-toggle', function(e){
			e.preventDefault();
			e.stopPropagation();
			wpcfeToggleSubmenu($(this).closest('.wpcfe-parent-link'));
		});
	})(jQuery);
	</script>
    <!-- Sidebar -->
</header>
<!--Main Navigation-->