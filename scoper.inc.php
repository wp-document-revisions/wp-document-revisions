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

	'patchers' => array(),

	'exclude-namespaces' => array(
		// Leave Composer's own autoload machinery unscoped. If we let
		// scoper rewrite it, vendor-prefixed/composer/autoload_real.php
		// ends up referencing `WP_Document_Revisions\Vendor\Composer\
		// Autoload\ClassLoader` but the ClassLoader.php file scoper
		// produces is loaded by file path before that name resolves,
		// and the result is "class not found" at boot. Excluding the
		// `Composer\` namespace from scoping keeps the bootstrap
		// internally consistent — the scoped deps still get prefixed
		// via the classmap entries scoper rewrites in
		// autoload_classmap.php.
		'Composer\\',
	),
	'exclude-classes'    => array(),
	'exclude-functions'  => array(),
	'exclude-constants'  => array(),

	'expose-global-constants' => false,
	// Composer's generated `ComposerAutoloaderInit<hash>` class lives in the
	// global namespace and is referenced by string callback inside the
	// composer-generated `autoload_real.php` (passed to spl_autoload_unregister).
	// Scoper rewrites the class definition but not the string callback, which
	// leaves a "class not found" fatal at boot. Exposing global classes makes
	// scoper alias the prefixed name back to the original global one, so both
	// names resolve. The hash makes this safe — no other plugin will define
	// ComposerAutoloaderInit<this exact hash>.
	'expose-global-classes'   => true,
	'expose-global-functions' => false,
);
