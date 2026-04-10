<?php
/**
 * Enable REST API and block editor support for the document post type in E2E tests.
 *
 * The document post type defaults to show_in_rest=false and uses the classic editor.
 * E2E tests need REST API access and block editor support for testing.
 *
 * @package WP_Document_Revisions
 */

// Expose documents via REST API.
add_filter( 'document_show_in_rest', '__return_true' );

// Allow REST write operations (POST, PUT, DELETE) for documents.
add_filter( 'document_use_block_editor', '__return_true' );

// Enable block editor for documents (override plugin's classic editor preference).
add_filter( 'use_block_editor_for_post', '__return_true', 100 );
