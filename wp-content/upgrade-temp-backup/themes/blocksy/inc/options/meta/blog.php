<?php
/**
 * Blog meta options.
 *
 * Note: Meta values from these options are sanitized in blocksy_sanitize_post_meta_options()
 * located in admin/helpers/validator.php. When adding new options that accept
 * user input (typography, gradients, etc.), ensure they are handled there.
 *
 * @see blocksy_sanitize_post_meta_options()
 */

$options = [
	[
		'disable_header' => [
			'label' => __( 'Disable Header', 'blocksy' ),
			'type' => 'ct-switch',
			'value' => 'no',
		],

		'disable_footer' => [
			'label' => __( 'Disable Footer', 'blocksy' ),
			'type' => 'ct-switch',
			'value' => 'no',
		],
	],

	apply_filters(
		'blocksy_extensions_metabox_page_bottom',
		[]
	)
];

