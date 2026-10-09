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
 * Loads the demo: an editor account and sample documents with revision history
 * and workflow states.
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

	// Sample documents: title, workflow state, then successive revisions.
	$samples = array(
		array(
			'Q3 Marketing Plan',
			'Under Review',
			array(
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email.', 'Initial draft' ),
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email. Budget: 50k.', 'Added budget figures' ),
				array( 'q3-marketing-plan.txt', 'Q3 Marketing Plan. Channels: search, social, email. Budget: 50k. Timeline finalized.', 'Incorporated review feedback' ),
			),
		),
		array(
			'Employee Handbook',
			'Final',
			array(
				array( 'employee-handbook.txt', 'Employee Handbook. Section 1: Welcome.', 'First draft' ),
				array( 'employee-handbook.txt', 'Employee Handbook. Section 1: Welcome. Section 2: Benefits.', 'Added benefits section' ),
			),
		),
		array(
			'Product Requirements',
			'In Progress',
			array(
				array( 'product-requirements.txt', 'Product Requirements. Goal: ship v1.', 'Initial outline' ),
				array( 'product-requirements.txt', 'Product Requirements. Goal: ship v1. User stories added.', 'Added user stories' ),
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
		foreach ( $sample[2] as $revision ) {
			wpdr_demo_add_revision( $doc_id, $revision[0], $revision[1], $revision[2] );
		}
	}
}
