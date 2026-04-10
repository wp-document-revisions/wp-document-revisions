<?php
/**
 * Enable REST API support for the document post type in E2E tests.
 *
 * The document post type defaults to show_in_rest=false.
 * E2E tests need REST API access for block editor and API testing.
 *
 * @package WP_Document_Revisions
 */

add_filter( 'document_show_in_rest', '__return_true' );
