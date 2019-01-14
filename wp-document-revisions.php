<?php
/**
Plugin Name: WP Document Revisions
Plugin URI: http://ben.balter.com/2011/08/29/wp-document-revisions-document-management-version-control-wordpress/
Description: A document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.
Version: 3.2.1
Author: Ben Balter
Author URI: http://ben.balter.com
License: GPL3
Text Domain: wp-document-revisions
Domain Path: /languages
 *
@package wp-document-revisions
 */

/**
 * WP Document Revisions
 *
 *  A document management and version control plugin for WordPress that allows
 *  teams of any size to collaboratively edit files and manage their workflow.
 *
 *  Copyright (C) 2011-2017 Ben Balter  ( ben@balter.com -- http://ben.balter.com )
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
 *  @copyright 2011-2017
 *  @license GPL v3
 *  @version 3.2.1
 *  @package WP_Document_Revisions
 *  @author Ben Balter <ben@balter.com>
 */
// @codingStandardsIgnoreStart WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
set_include_path( get_include_path() . PATH_SEPARATOR . dirname( __FILE__ ) . '/includes' );
// @codingStandardsIgnoreEnd WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
require_once dirname( __FILE__ ) . '/includes/class-wp-document-revisions.php';

// $wpdr is a global reference to the class.
global $wpdr;
$wpdr = new WP_Document_Revisions();
require_once dirname( __FILE__ ) . '/includes/template-functions.php';

// Activation hooks must be relative to the main plugin file
register_activation_hook( __FILE__, array( &$wpdr, 'activation_hook' ) );
