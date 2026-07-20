<?php
/**
Plugin Name: WP Document Revisions
Plugin URI: http://ben.balter.com/2011/08/29/wp-document-revisions-document-management-version-control-wordpress/
Description: A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.
Version: 5.1.3
Requires at least: 5.9
Requires PHP: 8.0
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
 *  Copyright (C) 2011-2026 Ben Balter  ( ben@balter.com -- http://ben.balter.com )
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
 *  @copyright 2011-2026
 *  @license GPL-3.0-or-later
 *  @version 5.1.3
 *  @package WP_Document_Revisions
 *  @author Ben Balter <ben@balter.com>
 */
// Single source of truth for the plugin version. The "Version:" header above is
// parsed by WordPress from the file header itself and must remain literal; this
// constant is the canonical value for runtime PHP code (cache busters, etc.).
if ( ! defined( 'WPDR_VERSION' ) ) {
	define( 'WPDR_VERSION', '5.1.3' );
}

// Composer autoloader for production dependencies.
//
// Today smalot/pdfparser ships unscoped from `vendor/` — both dev and
// release. The `vendor-prefixed/` branches below stay as defensive code so
// that the future scoper-on-the-release-path switch (when the Composer-
// autoload bootstrap edge cases are worked out — see scoper.inc.php) needs
// no further changes here. php-scoper writes `scoper-autoload.php` from
// 0.19 onward and a plain `autoload.php` in 0.18, hence the two paths.
//
// In every shipping configuration today the `vendor/autoload.php` branch
// is the one that runs; the spl_autoload_register shim then registers an
// alias so plugin code referencing `WP_Document_Revisions\Vendor\*` keeps
// working if a downstream user opts into scoping locally.
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
require_once __DIR__ . '/includes/class-wp-document-revisions-text-extractor-cache.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-text-extractor-scheduler.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-text-extraction-opt-out.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-text-diff.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-ai-summary.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-ai-summary-rest.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-ai-summary-prefill.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-pdf-text-extractor.php';
require_once __DIR__ . '/includes/class-wp-document-revisions-docx-text-extractor.php';
require_once __DIR__ . '/includes/class-wp-document-revisions.php';

// Register the built-in text extractors. Lazy construction inside the filter
// callbacks avoids paying allocation cost on requests that never extract
// anything. Third parties can prepend their own extractors at a higher
// filter priority to override these defaults.
add_filter(
	'wpdr_text_extractors',
	static function ( array $extractors ): array {
		$extractors[] = new WP_Document_Revisions_PDF_Text_Extractor();
		return $extractors;
	}
);
add_filter(
	'wpdr_text_extractors',
	static function ( array $extractors ): array {
		$extractors[] = new WP_Document_Revisions_DOCX_Text_Extractor();
		return $extractors;
	}
);

// Wire up async extraction: schedule a wp-cron event on each revision
// attachment insert, and register the worker that runs extraction off the
// request thread.
WP_Document_Revisions_Text_Extractor_Scheduler::init();

// Register the per-document opt-out meta box and save handler (admin only).
WP_Document_Revisions_Text_Extraction_Opt_Out::init();

// Register the AI summary cron handler (hooks wpdr_text_extracted →
// queues a single cron event to generate the summary off the request
// thread). Generation skips silently when the WP 7.0 AI Client is not
// available; the cron event is still scheduled so a future site
// upgrade does not require re-extracting historical content.
WP_Document_Revisions_AI_Summary::init();

// Register the read + review REST endpoints. Generation is intentionally
// NOT exposed over REST — cron drives it after extraction completes.
WP_Document_Revisions_AI_Summary_REST::init();

// Register the admin-editor JS enqueue for the AI revision-log pre-fill.
// Only fires on the document edit screen; gated on the per-document and
// sitewide pre-fill opt-out so opted-out documents pay no enqueue cost.
WP_Document_Revisions_AI_Summary_Prefill::init();

// Register the WP-CLI backfill command when running under WP-CLI.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-wp-document-revisions-text-extraction-cli-command.php';
	WP_CLI::add_command(
		'document-revisions extract-text',
		array( 'WP_Document_Revisions_Text_Extraction_CLI_Command', 'extract_text' )
	);
}

// $wpdr is a global reference to the class.
global $wpdr;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$wpdr = new WP_Document_Revisions();
require_once __DIR__ . '/includes/template-functions.php';

// Activation and deactivation hooks must be relative to the main plugin file.
register_activation_hook( __FILE__, array( $wpdr, 'activation_hook' ) );
register_deactivation_hook( __FILE__, array( $wpdr, 'deactivation_hook' ) );
