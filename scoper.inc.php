<?php
/**
 * php-scoper config for WP Document Revisions.
 *
 * Intended to scope production composer dependencies under
 * WP_Document_Revisions\Vendor to avoid namespace collisions with other
 * plugins shipping the same libraries (a recurring problem for
 * wordpress.org-distributed plugins).
 *
 * Status: the pipeline (composer build:scope, vendor-bin/scoper, CI smoke
 * job) is in place but not on the release path yet. smalot/pdfparser (the
 * first production composer dep) currently ships unscoped from `vendor/`
 * pending more work on scoper's Composer-autoload bootstrap edge cases. See
 * issue #514 for context and `docs/CONTRIBUTING.md` for the dev workflow.
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

	'patchers' => array(),

	'exclude-namespaces' => array(),
	'exclude-classes'    => array(),
	'exclude-functions'  => array(),
	'exclude-constants'  => array(),

	'expose-global-constants' => false,
	'expose-global-classes'   => false,
	'expose-global-functions' => false,
);
