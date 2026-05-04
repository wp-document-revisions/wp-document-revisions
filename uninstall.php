<?php
/**
 * WP Document Revisions Uninstall
 *
 * Fired when the plugin is uninstalled (deleted).
 * Cleans up plugin options, user meta, and custom capabilities.
 *
 * @package WP_Document_Revisions
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'document_upload_directory' );
delete_option( 'document_slug' );
delete_option( 'document_link_date' );
delete_option( 'wpdr_db_version' );

// Remove site options (multisite).
delete_site_option( 'document_upload_directory' );
delete_site_option( 'document_slug' );
delete_site_option( 'document_link_date' );

// Remove user meta (feed keys).
delete_metadata( 'user', 0, 'document_revisions_feed_key', '', true );

// Remove custom capabilities from all roles.
global $wp_roles;
if ( ! is_object( $wp_roles ) ) {
	$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$caps = array(
	'edit_documents',
	'edit_others_documents',
	'edit_private_documents',
	'edit_published_documents',
	'read_documents',
	'read_document_revisions',
	'read_private_documents',
	'delete_documents',
	'delete_others_documents',
	'delete_private_documents',
	'delete_published_documents',
	'publish_documents',
	'override_document_lock',
);

foreach ( $wp_roles->role_names as $role_name => $label ) {
	$role_obj = $wp_roles->get_role( $role_name );
	if ( $role_obj ) {
		foreach ( $caps as $cap ) {
			$role_obj->remove_cap( $cap );
		}
	}
}
