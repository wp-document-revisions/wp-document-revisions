<?php
/**
 * php-scoper config for WP Document Revisions.
 *
 * Production composer dependencies are scoped under WP_Document_Revisions\Vendor
 * to avoid namespace collisions with other plugins shipping the same libraries
 * (a recurring problem for wordpress.org-distributed plugins).
 *
 * Phase 2 of issue #514: the pipeline exists, but no production dependencies
 * have been added yet, so this config currently scopes nothing. Phase 3 will
 * add smalot/pdfparser as the first scoped dependency.
 *
 * Run via `composer build:scope` after a production install
 * (`composer install --no-dev --no-progress`).
 *
 * @package WP_Document_Revisions
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return array(
	'prefix'  => 'WP_Document_Revisions\\Vendor',

	'finders' => array(
		Finder::create()
			->files()
			->ignoreVCS( true )
			->name( '*.php' )
			->in( 'vendor' ),
	),

	// Patchers, exposers, and excludes will be added alongside the first
	// real dependency in phase 3 (smalot/pdfparser) — empty defaults are
	// fine for the empty-vendor smoke test that runs in Phase 2.
	'patchers' => array(),

	'exclude-namespaces' => array(),
	'exclude-classes'    => array(),
	'exclude-functions'  => array(),
	'exclude-constants'  => array(),

	'expose-global-constants' => false,
	'expose-global-classes'   => false,
	'expose-global-functions' => false,
);
