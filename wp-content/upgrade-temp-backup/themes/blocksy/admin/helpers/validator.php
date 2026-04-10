<?php
/**
 * Sanitization helpers for admin inputs.
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package   Blocksy
 */

if (! function_exists('blocksy_is_value_suspicious')) {
	/**
	 * Check if a value has suspicious patterns.
	 *
	 * @param string $value The value to check.
	 * @return bool True if suspicious, false otherwise.
	 */
	function blocksy_is_value_suspicious($value) {
		if (! is_string($value)) {
			return false;
		}

		$value = trim($value);

		$dangerous = ['<', '>', '{', '}', ';', '"', "'", '\\', '/', '*'];

		foreach ($dangerous as $char) {
			if (strpos($value, $char) !== false) {
				return true;
			}
		}

		return false;
	}
}

if (! function_exists('blocksy_sanitize_css_gradient')) {
	/**
	 * Sanitize CSS gradient value.
	 *
	 * @param string $gradient The gradient value to sanitize.
	 * @return string Sanitized gradient or default if suspicious.
	 */
	function blocksy_sanitize_css_gradient($gradient) {
		if (! is_string($gradient) || empty($gradient)) {
			$defaults = blocksy_background_default_value();
			return $defaults['gradient'];
		}

		if (blocksy_is_value_suspicious($gradient)) {
			$defaults = blocksy_background_default_value();
			return $defaults['gradient'];
		}

		return $gradient;
	}
}

if (! function_exists('blocksy_sanitize_typography')) {
	/**
	 * Sanitize typography value.
	 *
	 * @param array $typography The typography array to sanitize.
	 * @return array Sanitized typography with defaults applied for bad values.
	 */
	function blocksy_sanitize_typography($typography) {
		if (! is_array($typography)) {
			return blocksy_typography_default_values();
		}

		$defaults = blocksy_typography_default_values();

		$fields_to_check = [
			'family',
			'variation',
			'text-transform',
			'text-decoration',
			'size',
			'line-height',
			'letter-spacing',
		];

		foreach ($fields_to_check as $field) {
			if (isset($typography[$field])) {
				if (blocksy_is_value_suspicious($typography[$field])) {
					$typography[$field] = $defaults[$field];
				}
			}
		}

		return $typography;
	}
}

if (! function_exists('blocksy_sanitize_post_meta_options')) {
	/**
	 * Sanitize post meta options.
	 *
	 * @param array $value The meta options array to sanitize.
	 * @return array Sanitized meta options.
	 */
	function blocksy_sanitize_post_meta_options($value) {
		if (! is_array($value)) {
			return $value;
		}

		// Background fields that need sanitization.
		// Keys like 'background', 'pageTitleBackground', 'pageTitleOverlay'
		// are defined in inc/options/general/page-title.php.
		if (isset($value['background']) && is_array($value['background'])) {
			if (isset($value['background']['gradient'])) {
				$value['background']['gradient'] = blocksy_sanitize_css_gradient(
					$value['background']['gradient']
				);
			}
		}

		// Typography fields that need sanitization.
		// These keys are defined in inc/options/general/page-title.php
		// and used in meta options (inc/options/meta/*.php).
		$typography_fields = [
			'pageTitleFont',
			'pageMetaFont',
			'pageExcerptFont',
		];

		foreach ($typography_fields as $field) {
			if (isset($value[$field])) {
				$value[$field] = blocksy_sanitize_typography($value[$field]);
			}
		}

		return $value;
	}
}
