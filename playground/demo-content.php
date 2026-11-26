<?php
/**
 * Demo content for the WordPress Playground blueprints.
 *
 * Every blueprint (the WordPress.org Live Preview, the latest-main preview, the
 * per-PR previews, and the local one) is generated from this file by
 * script/build-blueprints, which inlines it into a `writeFile` step and then
 * calls wpdr_demo_load() from a `runPHP` step. Edit this file, not the
 * generated blueprints.
 *
 * The WordPress.org Live Preview installs the *released* plugin, and changes to
 * its blueprint go live as soon as they reach main. So this file must only use
 * WordPress core APIs and the plugin's documented data structure (the
 * `document` post type, `workflow_state` terms, `_document_attachment_id`
 * meta, and the `<!-- WPDR {id} -->` content marker), never plugin functions
 * that may not be released yet. CI runs it against the released plugin.
 *
 * Documents are filed in a hierarchical "Folders" taxonomy defined with
 * Simple Taxonomy Refreshed (installed by the blueprints), the way the FAQ
 * suggests. Some documents sit in several folders at once: taxonomies work as
 * virtual folders, not a single location. Without that plugin the documents
 * are still created, just unfiled.
 *
 * @package WP_Document_Revisions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds one revision to a document: uploads a file version and bumps the content.
 *
 * @param int    $doc_id   document post ID.
 * @param string $filename file name of the version.
 * @param string $body     file contents.
 * @param string $summary  revision summary.
 * @return void
 */
function wpdr_demo_add_revision( $doc_id, $filename, $body, $summary ) {
	$upload = wp_upload_bits( $filename, null, $body );
	if ( ! empty( $upload['error'] ) ) {
		return;
	}
	$type      = wp_check_filetype( $filename );
	$attach_id = wp_insert_attachment(
		array(
			'guid'           => $upload['url'],
			'post_mime_type' => $type['type'] ? $type['type'] : 'text/plain',
			'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
			'post_parent'    => $doc_id,
		),
		$upload['file'],
		$doc_id
	);
	if ( ! $attach_id || is_wp_error( $attach_id ) ) {
		return;
	}
	update_post_meta( $doc_id, '_document_attachment_id', $attach_id );
	wp_update_post(
		array(
			'ID'           => $doc_id,
			'post_content' => '<!-- WPDR ' . $attach_id . ' --> ' . $summary,
		)
	);
}

/**
 * Defines a hierarchical "Folders" taxonomy for documents with Simple Taxonomy
 * Refreshed, and registers it for this request (normally done on init) so
 * terms can be added straight away.
 *
 * @return bool whether the taxonomy is available.
 */
function wpdr_demo_register_folders() {
	if ( ! class_exists( 'SimpleTaxonomyRefreshed_Client' ) || ! defined( 'OPTION_STAXO' ) ) {
		return false;
	}

	SimpleTaxonomyRefreshed_Client::get_wp_default_labels();

	// The default labels speak of categories: use the taxonomy's own names.
	$labels = SimpleTaxonomyRefreshed_Client::get_taxonomy_default_labels( 1 );
	foreach ( $labels as $key => $label ) {
		if ( is_string( $label ) ) {
			$labels[ $key ] = str_replace(
				array( 'Categories', 'categories', 'Category', 'category' ),
				array( 'Folders', 'folders', 'Folder', 'folder' ),
				$label
			);
		}
	}
	$labels['name']          = 'Folders';
	$labels['singular_name'] = 'Folder';
	$labels['menu_name']     = 'Folders';

	$options                         = get_option( OPTION_STAXO, array() );
	$options                         = is_array( $options ) ? $options : array();
	$options['taxonomies']['folder'] = array_merge(
		SimpleTaxonomyRefreshed_Client::get_taxonomy_default_fields(),
		array(
			'name'         => 'folder',
			'labels'       => $labels,
			'capabilities' => SimpleTaxonomyRefreshed_Client::get_taxonomy_default_capabilities(),
			'query_var'    => 'folder',
			'hierarchical' => 1,
			'objects'      => array( 'document' ),
			// Filter the Documents list by folder, with document counts.
			'st_adm_types' => array( 'document' ),
			'st_adm_hier'  => 1,
			'st_adm_count' => 1,
		)
	);
	update_option( OPTION_STAXO, $options, true );

	SimpleTaxonomyRefreshed_Client::init();
	SimpleTaxonomyRefreshed_Client::init_2();

	return taxonomy_exists( 'folder' );
}

/**
 * Creates a tree of folders.
 *
 * @param array $tree      folder name => child tree (array) for each folder.
 * @param int   $parent_id parent term ID.
 * @return void
 */
function wpdr_demo_folders( $tree, $parent_id = 0 ) {
	foreach ( $tree as $name => $children ) {
		$term = wp_insert_term( $name, 'folder', array( 'parent' => $parent_id ) );
		if ( is_array( $term ) && $children ) {
			wpdr_demo_folders( $children, (int) $term['term_id'] );
		}
	}
}

/**
 * Files a document in folders given as paths, e.g. "Marketing/Campaigns".
 *
 * @param int      $doc_id document post ID.
 * @param string[] $paths  folder paths.
 * @return void
 */
function wpdr_demo_file( $doc_id, $paths ) {
	$ids = array();
	foreach ( $paths as $folder_path ) {
		$parent = 0;
		foreach ( explode( '/', $folder_path ) as $name ) {
			$term = term_exists( $name, 'folder', $parent );
			if ( ! is_array( $term ) ) {
				continue 2;
			}
			$parent = (int) $term['term_id'];
		}
		$ids[] = $parent;
	}
	wp_set_object_terms( $doc_id, $ids, 'folder' );
}

/**
 * Creates a "Document Library" page listing the sample documents, so visitors
 * can see how they appear on the front end.
 *
 * @param bool $has_folders whether the Folders taxonomy is available.
 * @return void
 */
function wpdr_demo_page( $has_folders ) {
	// The library shortcode is newer than [documents]; fall back on older releases.
	$library = shortcode_exists( 'document_library' );
	$all     = $library ? '[document_library variant="table"]' : '[documents]';
	$content = "<!-- wp:paragraph -->\n<p>Every sample document, with its latest version:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n{$all}\n<!-- /wp:shortcode -->";

	if ( $has_folders ) {
		$folder   = $library ? '[document_library folder="human-resources"]' : '[documents folder="human-resources"]';
		$content .= "\n\n<!-- wp:heading -->\n<h2>Human Resources folder</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>The same list filtered to one folder (and its subfolders). The Employee Handbook also appears under Shared: a document can live in several folders at once.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n{$folder}\n<!-- /wp:shortcode -->";
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Document Library',
			'post_content' => $content,
		)
	);
}

/**
 * Loads the demo: an editor account, folders, sample documents with revision
 * history and workflow states, and a page listing them.
 *
 * @return void
 */
function wpdr_demo_load() {
	$editor_id = wp_create_user( 'editor', 'password', 'editor@localhost' );
	if ( ! is_wp_error( $editor_id ) ) {
		( new WP_User( $editor_id ) )->set_role( 'editor' );
	}

	$editor = get_user_by( 'login', 'editor' );
	$author = $editor ? (int) $editor->ID : 1;

	// The demo has enough documents to trigger the "leave a review" prompt, which
	// would be the first thing a visitor sees. Mark it as already answered.
	foreach ( array( 1, $author ) as $user_id ) {
		update_user_meta( $user_id, 'wpdr_review_dismissed', 1 );
	}

	// Ensure the default workflow states exist (admin_init has not fired in this context).
	if ( ! get_terms(
		array(
			'taxonomy'   => 'workflow_state',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	) ) {
		foreach ( array( 'In Progress', 'Initial Draft', 'Under Review', 'Final' ) as $state ) {
			wp_insert_term( $state, 'workflow_state' );
		}
	}

	$has_folders = wpdr_demo_register_folders();
	if ( $has_folders ) {
		wpdr_demo_folders(
			array(
				'Marketing'       => array(
					'Campaigns' => array(),
					'Brand'     => array(),
				),
				'Human Resources' => array(
					'Policies'   => array(),
					'Onboarding' => array(),
				),
				'Product'         => array( 'Specifications' => array() ),
				'Board'           => array( 'Pending Approval' => array() ),
				'Shared'          => array(),
			)
		);
	}

	// Sample documents: title, workflow state, folders, then successive revisions.
	$samples = array(
		array(
			'Q3 Marketing Plan',
			'Under Review',
			array( 'Marketing/Campaigns', 'Board/Pending Approval' ),
			array(
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email.', 'Initial draft' ),
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email. Budget: 50k.', 'Added budget figures' ),
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email. Budget: 50k. Timeline finalized.', 'Incorporated review feedback' ),
			),
		),
		array(
			'Employee Handbook',
			'Final',
			array( 'Human Resources/Policies', 'Human Resources/Onboarding', 'Shared' ),
			array(
				array( 'employee-handbook.txt', 'Employee Handbook. Section 1: Welcome.', 'First draft' ),
				array( 'employee-handbook.txt', 'Employee Handbook. Section 1: Welcome. Section 2: Benefits.', 'Added benefits section' ),
			),
		),
		array(
			'Product Requirements',
			'In Progress',
			array( 'Product/Specifications' ),
			array(
				array( 'product-requirements.txt', 'Product Requirements. Goal: ship v1.', 'Initial outline' ),
				array( 'product-requirements.txt', 'Product Requirements. Goal: ship v1. User stories added.', 'Added user stories' ),
			),
		),
		array(
			'Brand Guidelines',
			'Final',
			array( 'Marketing/Brand', 'Shared' ),
			array(
				array( 'brand-guidelines.txt', 'Brand Guidelines. Logo usage and colors.', 'First version' ),
				array( 'brand-guidelines.txt', 'Brand Guidelines. Logo usage, colors and typography.', 'Added typography' ),
			),
		),
		array(
			'Remote Work Policy',
			'Under Review',
			array( 'Human Resources/Policies', 'Board/Pending Approval' ),
			array(
				array( 'remote-work-policy.txt', 'Remote Work Policy. Eligibility and equipment.', 'Initial draft' ),
				array( 'remote-work-policy.txt', 'Remote Work Policy. Eligibility, equipment and core hours.', 'Added core hours after HR review' ),
			),
		),
		array(
			'Launch Checklist',
			'Initial Draft',
			array( 'Product', 'Marketing/Campaigns' ),
			array(
				array( 'launch-checklist.txt', 'Launch Checklist. QA sign-off.', 'Started the checklist' ),
				array( 'launch-checklist.txt', 'Launch Checklist. QA sign-off. Press release.', 'Added marketing tasks' ),
			),
		),
	);

	foreach ( $samples as $sample ) {
		$doc_id = wp_insert_post(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_title'   => $sample[0],
				'post_author'  => $author,
				'post_content' => '',
			)
		);
		if ( ! $doc_id || is_wp_error( $doc_id ) ) {
			continue;
		}
		wp_set_object_terms( $doc_id, $sample[1], 'workflow_state' );
		if ( $has_folders ) {
			wpdr_demo_file( $doc_id, $sample[2] );
		}
		foreach ( $sample[3] as $revision ) {
			wpdr_demo_add_revision( $doc_id, $revision[0], $revision[1], $revision[2] );
		}
	}

	wpdr_demo_page( $has_folders );
}
