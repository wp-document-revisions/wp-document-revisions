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

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$options = array(
	'document_upload_directory',
	'document_slug',
	'document_link_date',
	'document_notify_enabled',
	'document_notify_recipients',
	'document_notify_on_state_change',
	'document_notify_on_new_revision',
);

// On multisite, each site saves its own options and feed keys.
$site_ids = is_multisite() ? get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
) : array();

// Remove plugin options.
$site_options = array_merge( $options, array( 'wpdr_db_version' ) );
foreach ( $site_options as $option ) {
	delete_option( $option );
}
foreach ( $site_ids as $site_id ) {
	foreach ( $site_options as $option ) {
		delete_blog_option( $site_id, $option );
	}
}

// Remove site options (multisite).
foreach ( $options as $option ) {
	delete_site_option( $option );
}

// Remove user meta. Feed keys are saved with update_user_option(), so their meta key
// has the site's table prefix (e.g. wp_document_revisions_feed_key, or wp_2_... on
// multisite). The unprefixed key is also removed.
global $wpdb;
$feed_key_prefixes = array( '', $wpdb->get_blog_prefix() );
foreach ( $site_ids as $site_id ) {
	$feed_key_prefixes[] = $wpdb->get_blog_prefix( $site_id );
}
foreach ( array_unique( $feed_key_prefixes ) as $feed_key_prefix ) {
	delete_metadata( 'user', 0, $feed_key_prefix . 'document_revisions_feed_key', '', true );
}
delete_metadata( 'user', 0, 'wpdr_review_dismissed', '', true );

// Remove custom capabilities from all roles.
global $wp_roles;
if ( ! is_object( $wp_roles ) ) {
	$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

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

// Roles are saved per site, and switch_to_blog() loads each site's roles into $wp_roles.
foreach ( $site_ids ? $site_ids : array( 0 ) as $site_id ) {
	if ( $site_id ) {
		switch_to_blog( $site_id );
	}
	foreach ( $wp_roles->role_names as $role_name => $label ) {
		$role_obj = $wp_roles->get_role( $role_name );
		if ( $role_obj ) {
			foreach ( $caps as $cap ) {
				$role_obj->remove_cap( $cap );
			}
		}
	}
	if ( $site_id ) {
		restore_current_blog();
	}
}
