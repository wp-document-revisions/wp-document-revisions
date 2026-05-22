<?php
/**
Plugin Name: WP Document Revisions
Plugin URI: http://ben.balter.com/2011/08/29/wp-document-revisions-document-management-version-control-wordpress/
Description: A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.
Version: 4.0.7
Requires at least: 5.0
Requires PHP: 7.4
Author: Ben Balter
Author URI: https://ben.balter.com
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wp-document-revisions
Domain Path: /languages
 *
@package wp-document-revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Document Revisions
 *
 *  A document management and version control plugin for WordPress that allows
 *  teams of any size to collaboratively edit files and manage their workflow.
 *
 *  Copyright (C) 2011-2025 Ben Balter  ( ben@balter.com -- http://ben.balter.com )
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright 2011-2025
 *  @license GPL-3.0-or-later
 *  @version 4.0.7
 *  @package WP_Document_Revisions
 *  @author Ben Balter <ben@balter.com>
 */
// Single source of truth for the plugin version. The "Version:" header above is
// parsed by WordPress from the file header itself and must remain literal; this
// constant is the canonical value for runtime PHP code (cache busters, etc.).
if ( ! defined( 'WPDR_VERSION' ) ) {
	define( 'WPDR_VERSION', '4.0.7' );
}

// Composer autoloader for production / scoped dependencies.
//
// Release builds ship `vendor-prefixed/` (produced by `composer build:scope`).
// Dev environments that have only run `composer install` keep the unprefixed
// `vendor/` tree. The shim below picks whichever is present, and then registers
// an autoloader that maps unscoped `Smalot\PdfParser\*` (and any future scoped
// vendor namespace) onto the prefixed `WP_Document_Revisions\Vendor\*` names
// the plugin uses internally, so the same plugin source works in both modes.
//
// php-scoper writes `scoper-autoload.php` from 0.19 onward; 0.18 and earlier
// only wrote a scoped `autoload.php`. Check both so version bumps don't break
// the bootstrap.
if ( file_exists( __DIR__ . '/vendor-prefixed/scoper-autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/scoper-autoload.php';
} elseif ( file_exists( __DIR__ . '/vendor-prefixed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor-prefixed/autoload.php';
} elseif ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';

	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'WP_Document_Revisions\\Vendor\\';
			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}
			$unscoped = substr( $class_name, strlen( $prefix ) );
			if ( class_exists( $unscoped ) || interface_exists( $unscoped ) || trait_exists( $unscoped ) ) {
				class_alias( $unscoped, $class_name );
			}
		}
	);
}

require_once __DIR__ . '/includes/trait-wp-document-revisions-rewrites.php';
require_once __DIR__ . '/includes/trait-wp-document-revisions-file-handler.php';
require_once __DIR__ . '/includes/trait-wp-document-revisions-revisions.php';
require_once __DIR__ . '/includes/trait-wp-document-revisions-query.php';
require_once __DIR__ . '/includes/interface-wp-document-revisions-text-extractor.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-text-extraction-exception.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-text-extractor-registry.php';
require_once __DIR__ . '/includes/class-wp-document-revisions.php';

// $wpdr is a global reference to the class.
global $wpdr;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$wpdr = new WP_Document_Revisions();
require_once __DIR__ . '/includes/template-functions.php';

// Activation and deactivation hooks must be relative to the main plugin file.
register_activation_hook( __FILE__, array( $wpdr, 'activation_hook' ) );
register_deactivation_hook( __FILE__, array( $wpdr, 'deactivation_hook' ) );

// polyfill for str_contains.
if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Provides str_contains function.
	 *
	 * @since 3.5.0
	 *
	 * @param string $haystack the text to be searched.
	 * @param string $needle   the text to search.
	 * @return bool
	 */
	function str_contains( string $haystack, string $needle ): bool {
		return empty( $needle ) || strpos( $haystack, $needle ) !== false;
	}
}
