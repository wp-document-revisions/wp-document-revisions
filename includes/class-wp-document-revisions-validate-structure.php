<?php
/**
 * WP Document Revisions Validate Structure Functionality
 *
 * @author  Neil W. James <neil@familyjames.com>
 * @package WP Document Revisions
 */

/**
 * Design notes
 * ============
 *
 * Underlying data structure
 *
 * This is based on a custom post type - document - that supports revisions.
 * A document makes use of WP media upload to upload an underlying file (that is the actual document).
 * This underlying document is held as an attachment WP post type.
 * Each new version of the document will be uploaded as an attachment to the original document post.
 * Therefore all attachments and document revisions will have their post_parent set to the original document post.
 * [Note. If a featured image is loaded then it would also have its attachment post with post_parent. This is explicitly set to 0 on loading.]
 * The attachment record contains the link to actual data file.
 * The actual attachment to use is held in the post_content field.
 * Whilst it is normal that the latest loaded attachment, this is not necessarily so if a revision is reverted.
 * However the latest one will be used if the correct one cannot be found.
 *
 * So that direct file access is difficult, the actual file name is not as loaded, but is an MD5-hash of it.
 * Measures are taken to hide this name from being seen by the user. However the attachment post does contain it.
 *
 * This code is based on the following:
 * Every live document will have post_content that will contain the ID of an attachment that has the document as its parent.
 * If it does not, then it will try to see if there is such an attachment.
 * It will then try to validate that attachment record.
 * This should be an MD5-format name (32 hexaecimal name). If not it will change the name.
 * The file that it points to should exist there.
 * That file should be readable.
 * [If the document directory is different to the media directory, it will also check that it is in the media one, and if found propose to move it.]
 *
 * This code uses direct SQL calls to identify the document posts and to choose a potential attachment. .
 * This is a deliberate design decision in the analysis phase to NOT have any issues with caching and/or loading memory with data that is not going to be used.
 *
 * It also does so during data correction. Here this is to avoid creating a revision (which would have, by definition, invalid data).
 *
 * Error/Warning conditions detected.
 * ===================================
 *
 * Note. There is no significance to the code numbering, Just the order that they were thought of.
 *
 * If the note has Fixable No, that means no automatic repair is possible.
 * Resolution can ALWAYS be achieved by sending the document to trash or by loading a new version of the document.
 * These options are available to those who can edit the document - which is the scope of the tests.
 *
 * Of course the document may have been loaded but the MD5 hash has beome "lost". It may be necessary to search within the directory using file timestamps to find the document/
 *
 * Code     1
 * Type     Error
 * Message  There is no attachment record held for document
 * Fixable  No
 * Cause    Post_contrent does not contain an attachment id and there is no attachment post for the document.
 *
 * Code     2
 * Type     Error
 * Message  Document links to an invalid attachment record
 * Fixable  No
 * Cause    Post_content contains an id but it is not an attachment post belonging the document.
 *
 * Code     3
 * Type     Error
 * Message  Document attachment exists but related file not found
 * Fixable  No
 * Cause    Post_content contains an attaclment post belonging to the document, but there is no file there.
 *
 * Code     4
 * Type     Error
 * Message  Attachment found for document, but not currently linked
 * Fixable  Yes
 * Cause    Post_content does not contain an attaclment post belonging to the document, but there is one there so we could link to it.
 *
 * Code     5
 * Type     Error
 * Message  Document links to an invalid attachment record
 * Fixable  Yes
 * Cause    Post_content contains an id but it is not an attachment post belonging to the document. However a valid attachment exists that can be linked to.
 *
 * Code     6
 * Type     Warning
 * Message  Document attachment does not appear to be md5 encoded
 * Fixable  Yes
 * Cause    Post_content contain an attachment post belonging the document, but the attached file does not appear to have an MD5-coded name.
 *          Can rename it to ensure that it is.
 *
 * Code     7
 * Type     Error
 * Message  Document attachment exists but related file not in document location
 * Fixable  Yes
 * Cause    Post_content contain an attachment post belonging the document, but the attached file is in the media library, not the document one.
 *          Hence moving the file will make it available.
 *
 * Code     8
 * Type     Error
 * Message  Document attachment file exists but is not readable
 * Fixable  Yes
 * Cause    Post_content contain an attachment post belonging the document, and the attached file exists but is not readable.
 *          Changing the permissions to the file will make it available. But this needs to be done at the OS level.
 *
 * Code     9
 * Type     Warning
 * Message  The guid is not the expected "ugly" permalink
 * Fixable  Yes
 * Cause    The guid of a pending or draft document or when there is no permalink rewrite is expected to be able to access the document.
 *          Changing it to be in the form "site_url/?post_type=document&p=nnnn" will make it useable.
 *
 * Code     10
 * Type     Warning
 * Message  The guid does not contain the correct date.
 * Fixable  Yes
 * Cause    The document permalink should contain the post_date year and month.
 *          The guid cannot be used to successfully access the document,
 *          The "ugly" form "site_url/?post_type=document&p=nnnn" is a unique identifier and if set to this value, this test is not applied.
 *
 * Code     11
 * Type     Warning
 * Message  The guid does not contain the document name.
 * Fixable  Yes
 * Cause    The document permalink should contain the post_date year and month.
 *          The guid cannot be used to successfully access the document,
 *          The "ugly" form "site_url/?post_type=document&p=nnnn" is a unique identifier and if set to this value, this test is not applied.
 *
 * Code     12
 * Type     Warning
 * Message  The guid does not reflect the complete document permalink
 * Fixable  Yes
 * Cause    The document permalink should contain the post_date year and month.
 *          This is only a completeness check. Normally access is possible, and normally indicates that the attachment extension has been changed.
 *          The "ugly" form "site_url/?post_type=document&p=nnnn" is a unique identifier and if set to this value, this test is not applied.
 */

/**
 * Main WP_Document_Revisions Validate Structure class.
 */
class WP_Document_Revisions_Validate_Structure {

	/**
	 * The parent WP Document Revisions instance
	 *
	 * @var $parent
	 */
	public static $parent;

	/**
	 * The singelton instance
	 *
	 * @var $instance
	 */
	public static $instance;

	/**
	 * Constructor
	 *
	 * @since 3.4
	 * @param object $instance class instance.
	 * @return void
	 */
	public function __construct( &$instance = null ) {
		self::$instance = &$this;

		// create or store parent instance.
		if ( null === $instance ) {
			global $wpdr;
			self::$parent = $wpdr;
		} else {
			self::$parent = &$instance;
		}

		add_action( 'admin_menu', array( &$this, 'add_menu' ), 20 );

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( &$this, 'wpdr_register_route' ) );
	}

	/**
	 * Provides support to call functions of the parent class natively.
	 *
	 * @since 3.4.0
	 *
	 * @param function $funct the function to call.
	 * @param array    $args  the arguments to pass to the function.
	 * @returns mixed the result of the function.
	 */
	public function __call( $funct, $args ) {
		return call_user_func_array( array( &self::$parent, $funct ), $args );
	}

	/**
	 * Add settings menu page.
	 *
	 * @since 3.4.0
	 **/
	public static function add_menu() {
		$slug = 'wpdr_validate';
		add_submenu_page( 'edit.php?post_type=document', __( 'Validate Structure', 'wp-document-revisions' ), __( 'Validate Structure', 'wp-document-revisions' ), 'edit_documents', $slug, array( __CLASS__, 'page_validate' ) );

		// help text.
		add_action( 'load-document_page_' . $slug, array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Register route
	 */
	public function wpdr_register_route() {
		$args = array(
			'methods'             => 'PUT',
			'callback'            => array( &$this, 'correct_document' ),
			'permission_callback' => array( &$this, 'check_permission' ),
			'args'                => array(
				'id'   => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'code' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'parm' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		);

		register_rest_route(
			'wpdr/v1',
			'correct/(?P<id>[\d]+)/type/(?P<code>[\d]+)/attach/(?P<parm>[\d]+)',
			$args
		);
	}

	/**
	 * Rest function to correct document structure data.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_REST_Request $request the arguments to pass to the function.
	 * @return WP_REST_Response
	 *
	 * @global $wpdb Database object.
	 */
	public static function correct_document( $request ) {
		global $wpdb;
		$wpdr   = self::$parent;
		$params = $request->get_params();
		$id     = $params['id'];
		$parm   = $params['parm'];
		if ( 4 === $params['code'] ) {
			// Attachment exists but post_content does not contain it.
			// revalidate input values.
			if ( get_post_field( 'post_parent', $parm, 'db' ) !== $id ) {
				return new WP_Error( 'inconsistent_parms', __( 'Inconsistent data sent to Interface', 'wp-document-revisions' ) );
			}
			$content = get_post_field( 'post_content', $id, 'db' );
			if ( empty( $content ) || is_numeric( $content ) ) {
				$content = $wpdr->format_doc_id( $parm );
			} else {
				// find if there is a document id there.
				preg_match( '/(<!-- WPDR \s*\d+ -->)/', $content, $id );
				if ( isset( $id[1] ) ) {
					// if a match return the id.
					$content = str_replace( $id[1], '', $content );
				}
				$content = $wpdr->format_doc_id( $parm ) . $content;
			}
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_content` = %s WHERE `id` = %d",
				$content,
				$id
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $id, 'posts' );
			wp_cache_delete( $id, 'document_revisions' );
		}

		if ( 5 === $params['code'] ) {
			// Attachment exists but post_content contains invalid data.
			// revalidate input values.
			$content = get_post_field( 'post_content', $id, 'db' );
			if ( false === $wpdr->extract_document_id( $content ) || get_post_field( 'post_parent', $parm, 'db' ) !== $id ) {
				return new WP_Error( 'inconsistent_parms', __( 'Inconsistent data sent to Interface', 'wp-document-revisions' ) );
			}
			$end_id = strpos( $content, '>' );
			if ( false === $end_id ) {
				// replace content.
				$content = (string) $parm;
			} else {
				// replace existing id data.
				$content = $wpdr->format_doc_id( $parm ) . substr( $content, $end_id + 1 );
			}
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `post_content` = %s WHERE `id` = %d",
				$content,
				$id
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $id, 'posts' );
			wp_cache_delete( $id, 'document_revisions' );
		}

		if ( 6 === $params['code'] ) {
			// Attachment file name not encoded.
			$title     = get_post_field( 'post_title', $id );
			$attach_id = $parm;
			$attach    = get_post( $attach_id );

			// ensure not in document image mode.
			$wpdr::$doc_image = false;

			// make sure we're looking at the document directory.
			add_filter( 'get_attached_file', array( $wpdr, 'get_attached_file_filter' ), 10, 2 );

			// get file name.
			$file = get_attached_file( $attach_id );

			$file = self::check_document_folder( $file );

			// get filename part.
			$filename = pathinfo( $file, PATHINFO_FILENAME );

			// revalidate input (late, but before any damage is done).
			if ( preg_match( '/^[a-f0-9]{32}$/', $filename ) ) {
				return new WP_Error( 'inconsistent_parms', __( 'Inconsistent data sent to Interface', 'wp-document-revisions' ) );
			}

			$new_name = md5( $title . microtime() );
			$new_file = str_replace( $filename, $new_name, $file );
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( @copy( $file, $new_file ) ) {
				$name = get_post_meta( $attach_id, '_wp_attached_file', true );
				update_post_meta( $attach_id, '_wp_attached_file', str_replace( $filename, $new_name, $name ), $name );
				wp_delete_file( $file );
			}

			// rename attachment post (if no clash).
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"SELECT COUNT(1) FROM `$post_table` WHERE `post_name` = %s",
				$new_name
			);
			$res        = $wpdb->get_var( $sql );
			if ( 0 === $res ) {
				$sql = $wpdb->prepare(
					"UPDATE `$post_table` SET `post_name` = %s, `post_title` = %s WHERE `id` = %d",
					$new_name,
					$new_name,
					$attach_id
				);
				$res = $wpdb->query( $sql );
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
				wp_cache_delete( $id, 'posts' );
				wp_cache_delete( $id, 'document_revisions' );
			}
		}

		if ( 7 === $params['code'] ) {
			// Attachment file in wrong location (media not document).
			$title     = get_post_field( 'post_title', $id );
			$attach    = $parm;
			$attach_id = get_post( $attach );

			// ensure not in document image mode.
			$wpdr::$doc_image = false;

			// get file name using the default upload directory.
			$orig = get_attached_file( $attach, true );

			// make sure we're looking at the document directory.
			add_filter( 'get_attached_file', array( $wpdr, 'get_attached_file_filter' ), 10, 2 );

			// manipulate file as in serve_file process.
			$file = get_attached_file( $attach );
			$file = self::check_document_folder( $file );

			// revalidate input (late, but before any damage is done).
			if ( $orig === $file || ! file_exists( $orig ) || file_exists( $file ) ) {
				return new WP_Error( 'inconsistent_parms', __( 'Inconsistent data sent to Interface', 'wp-document-revisions' ) );
			}
			$file_dir = dirname( $file );
			// Use copy and unlink because rename breaks streams.
			// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
			// Ensure directory exists.
			if ( ! is_dir( $file_dir ) ) {
				wp_mkdir_p( $file_dir );
			}
			if ( @copy( $orig, $file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
				chmod( $file, 0664 );
				wp_delete_file( $orig );
				// get attachment metadata.
				$meta = get_post_meta( $attach, '_wp_attachment_metadata', true );
				if ( ! is_array( $meta ) || ! isset( $meta['sizes'] ) ) {
					null;
				} else {
					// image name contains only file name and extension.
					$orig_dir = trailingslashit( dirname( $orig ) );
					$file_dir = trailingslashit( $file_dir );
					// move files.
					foreach ( $meta['sizes'] as $size => $sizeinfo ) {
						if ( file_exists( $orig_dir . $sizeinfo['file'] ) ) {
							// Use copy and unlink because rename breaks streams.
							if ( @copy( $orig_dir . $sizeinfo['file'], $file_dir . $sizeinfo['file'] ) ) {
								// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
								@chmod( $file_dir . $sizeinfo['file'], 0664 );
								wp_delete_file( $orig_dir . $sizeinfo['file'] );
							}
						}
					}
				}
			}
			// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( 9 <= $params['code'] && 12 >= $params['code'] ) {
			// guid contains invalid data.
			// revalidate input values.
			if ( $id !== $parm ) {
				return new WP_Error( 'inconsistent_parms', __( 'Inconsistent data sent to Interface', 'wp-document-revisions' ) );
			}
			$guid = get_the_permalink( $id );
			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$post_table = "{$wpdb->prefix}posts";
			$sql        = $wpdb->prepare(
				"UPDATE `$post_table` SET `guid` = %s WHERE `id` = %d",
				$guid,
				$id
			);
			$res        = $wpdb->query( $sql );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			wp_cache_delete( $id, 'posts' );
			wp_cache_delete( $id, 'document_revisions' );
		}

		$response = 'Success.';
		return rest_ensure_response( $response );
	}

	/**
	 * Rest function to check permissions to correct document structure data.
	 *
	 * @param WP_REST_Request $request the arguments to pass to the function.
	 * @return boolean
	 */
	public function check_permission( $request ) {
		// userid must be passed and able to edit the document.
		$params = $request->get_params();
		if ( ! isset( $params['userid'] ) ) {
			return false;
		}
		return user_can( $params['userid'], 'edit_document', $params['id'] );
	}

	/**
	 * Display page of documents in error.
	 *
	 * @since 3.4.0
	 */
	public static function page_validate() {
		// ensure not in document image mode.
		$wpdr             = self::$parent;
		$wpdr::$doc_image = false;

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$documents = $wpdb->get_results(
			"SELECT ID, post_title, post_content, post_date, post_name, guid, post_status, post_modified_gmt
			 FROM {$wpdb->prefix}posts 
			 WHERE post_type = 'document'
			 AND post_status not in ( 'auto-draft', 'trash' )
			 ORDER BY ID DESC
			",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

		// make sure we're looking at the document directory.
		add_filter( 'get_attached_file', array( self::$parent, 'get_attached_file_filter' ), 10, 2 );

		$num_doc = $wpdb->num_rows;
		$fails   = array();
		$guids   = array();
		foreach ( $documents as $rec => $doc ) {
			// check that user can edit the document.
			if ( current_user_can( 'edit_document', $doc['ID'] ) ) {
				// get the attachment. Note may be false if none in content.
				$attach_id = $wpdr->extract_document_id( $doc['post_content'] );
				$test      = self::validate_document( $doc['ID'], $attach_id, $doc['post_modified_gmt'] );
				if ( is_array( $test ) ) {
					// failure.
					$fails[] = array_merge( $doc, $test );
				} else {
					$test = self::validate_guid( $doc['ID'], $attach_id, $doc['post_status'], $doc['post_date'], $doc['post_name'], $doc['guid'] );
					if ( is_array( $test ) ) {
						// failure.
						$guids[] = array_merge( $doc, $test );
					}
				}
			}
		}
		// No errors found.
		if ( empty( $fails ) && empty( $guids ) ) {
			echo '<h2 class="title">' . esc_html__( 'Invalid Document Internal Structures', 'wp-document-revisions' ) . '</h2>';
			echo '<p>' . esc_html__( 'No invalid documents found.', 'wp-document-revisions' ) . '</p>';
			return;
		}
		?>
		<div class="wrap">
		<h2 class="title"><?php esc_html_e( 'Invalid Document Internal Structures', 'wp-document-revisions' ); ?></h2>
		<div>
		<h3><?php esc_html_e( 'Internal structure', 'wp-document-revisions' ); ?></h3>
		<?php
		if ( empty( $fails ) ) {
			echo '<p>' . esc_html__( 'No invalid internal linkages found.', 'wp-document-revisions' ) . '</p>';
		} else {
			self::build_table( $fails );
		}
		?>
		</div>
		<div>
		<h3><?php esc_html_e( 'Permalink / guid', 'wp-document-revisions' ); ?></h3>
		<?php
		if ( empty( $guids ) ) {
			echo '<p>' . esc_html__( 'No invalid permalinks found.', 'wp-document-revisions' ) . '</p>';
		} else {
			// these messages are repeated below.
			$msg_09 = esc_html__( 'The guid is not the expected "ugly" permalink', 'wp-document-revisions' );
			if ( get_option( 'document_link_date' ) ) {
				$msg_10 = esc_html__( 'The guid does not contain the site URL.', 'wp-document-revisions' );
			} else {
				$msg_10 = esc_html__( 'The guid does not contain the correct date.', 'wp-document-revisions' );
			}
			$msg_11 = esc_html__( 'The guid does not contain the document name.', 'wp-document-revisions' );
			$msg_12 = esc_html__( 'The guid does not reflect the complete document permalink.', 'wp-document-revisions' );
			// phpcs:disable  WordPress.Security.EscapeOutput
			?>
			<p><?php esc_html_e( 'The guid field is meant to be the unique access path.', 'wp-document-revisions' ); ?></p>
			<p><?php esc_html_e( 'It is recommended not to change it, but can be changed to a valid value.', 'wp-document-revisions' ); ?></p>
			<p><?php esc_html_e( 'They can be displayed or hidden as wished by checking the options:', 'wp-document-revisions' ); ?></p>
			<input id="wpdr_9" type="checkbox" checked onclick="hide_show('wpdr_9')" /><label for="wpdr_9"><?php echo $msg_09; ?></label>&nbsp;&nbsp;
			<input id="wpdr_10" type="checkbox" checked onclick="hide_show('wpdr_10')" /><label for="wpdr_10"><?php echo $msg_10; ?></label>&nbsp;&nbsp;
			<input id="wpdr_11" type="checkbox" checked onclick="hide_show('wpdr_11')" /><label for="wpdr_11"><?php echo $msg_11; ?></label>&nbsp;&nbsp;
			<input id="wpdr_12" type="checkbox" checked onclick="hide_show('wpdr_12')" /><label for="wpdr_12"><?php echo $msg_12; ?></label>
			<?php
			self::build_table( $guids );
			// phpcs:enable  WordPress.Security.EscapeOutput
		}
		?>
		</div>
		<p><strong><?php esc_html_e( 'See the Help pulldown for detailed Help information', 'wp-document-revisions' ); ?></strong></p>
		</div>
		<?php
	}

	/**
	 * Build table of errors.
	 *
	 * @since 3.5.0
	 *
	 * @param mixed[] $rows table of errors.
	 * @return void
	 */
	private static function build_table( $rows ) {
		?>
		<div id="col-container">
		<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'ID', 'wp-document-revisions' ); ?></th>
					<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'Type', 'wp-document-revisions' ); ?></th>
					<th scope="col" id="label" class="manage-column column-name"><?php esc_html_e( 'Title', 'wp-document-revisions' ); ?></th>
					<th scope="col" id="name"  class="manage-column column-name"><?php esc_html_e( 'Message', 'wp-document-revisions' ); ?></th>
					<th scope="col" id="slug"  class="manage-column column-name"><?php esc_html_e( 'Fix', 'wp-document-revisions' ); ?></th>
				</tr>
			</thead>

			<tbody id="the-list" class="list:failures">
			<?php
			foreach ( $rows as $rec => $row ) {
				$line = esc_attr( $row['ID'] );
				$link = '<a href="' . admin_url( 'post.php?post=' . $line . '&action=edit' ) . '" target="_blank">' . $line . '</a>';
				$beg  = esc_attr( $row['post_title'] ) . '<br/>';
				$ref  = esc_url( get_the_permalink( $row['ID'] ) );
				// Create the URL to the document. This is text if error/not fixable; link if warning; both if error and can be fixed (display one then other).
				if ( (bool) $row['fix'] ) {
					if ( (bool) $row['error'] ) {
						$ref = $beg . '<div id="on_' . $line . '" style="display: block;">' . $ref . '</div><div id="off' . $line .
							'" style="display: none;"><a href="' . $ref . '" target="_blank">' . $ref . '</a></div>';
					} else {
						$ref = $beg . '<a href="' . $ref . '" target="_blank">' . $ref . '</a>';
					}
				} else {
					$ref = $beg . $ref;
				}
				// create button if fixable.
				if ( (bool) $row['fix'] ) {
					$fix = '<button onclick="wpdr_valid_fix(' . $row['ID'] . ',' . $row['code'] . ',' . $row['parm'] . ')">' . esc_html__( 'Fix', 'wp-document-revisions' ) . '</button>';
				} else {
					$fix = '';
				}
				// phpcs:disable  WordPress.Security.EscapeOutput
				?>
				<tr id="Line<?php echo $line; ?>" class="wpdr_<?php echo $row['code']; ?>" style="display: table-row;">
					<td><?php echo $link; ?></td>
					<td><?php ( (bool) $row['error'] ? esc_html_e( 'Error', 'wp-document-revisions' ) : esc_html_e( 'Warning', 'wp-document-revisions' ) ); ?></td>
					<td><?php echo $ref; ?></td>
					<td><?php echo esc_attr( $row['msg'] ) . ( isset( $row['msg2'] ) ? '<br />' . esc_attr( $row['msg2'] ) : '' ); ?></td>
					<td><?php echo $fix; ?></td>
				</tr>
				<?php
				// phpcs:enable  WordPress.Security.EscapeOutput
			}
			?>
			</tbody>
		</table>
		</div>
		<?php
	}

	/**
	 * Enqueue javascript.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public static function enqueue_scripts() {
		$suffix = ( WP_DEBUG ) ? '.dev' : '';
		$path   = '/js/wp-document-revisions-validate' . $suffix . '.js';
		$vers   = ( WP_DEBUG ) ? filemtime( plugin_dir_path( __DIR__ ) . $path ) : self::$parent->version;

		wp_enqueue_script(
			'wpdr_validate',
			plugins_url( $path, __DIR__ ),
			array( 'jquery', 'wp-api-request' ),
			$vers,
			true
		);
		// phpcs:disable Squiz.Strings.DoubleQuoteUsage
		$script =
			"var nonce = '" . wp_create_nonce( 'wp_rest' ) . "';" . PHP_EOL .
			"var user  = '" . get_current_user_id() . "';" . PHP_EOL .
			"var processed = '" . esc_html__( 'Processed successfully.', 'wp-document-revisions' ) . "';";
		// phpcs:enable Squiz.Strings.DoubleQuoteUsage
		wp_add_inline_script( 'wpdr_validate', $script, 'before' );
	}

	/**
	 * Validate individual document.
	 *
	 * @since 3.4.0
	 *
	 * @param id     $doc_id            ID of a post object.
	 * @param string $attach_id         attachment id from post content field.
	 * @param string $post_modified_gmt post modified field.
	 * @return array||false
	 */
	private static function validate_document( $doc_id, $attach_id, $post_modified_gmt ) {
		global $wpdb;

		if ( $attach_id ) {
			$valid_att = true;
		} else {
			// post_content does not contain an attachment link. Does one exist.
			$attach_id = self::get_last_attachment( $doc_id );
			if ( false === $attach_id ) {
				// no attachment out there.
				return array(
					'code'  => 1,
					'error' => 1,
					'msg'   => __( 'There is no attachment record held for document', 'wp-document-revisions' ),
					'fix'   => 0,
				);
			}
			// go forward with this attachment.
			$valid_att = false;
		}

		// attachment found, is it valid.
		$att_error = self::check_attachment( $attach_id, $doc_id );

		// fixing the post_content structure takes precedence.
		if ( ! $valid_att ) {
			// there is an attachment out there, but not linked.
				$post_date   = get_date_from_gmt( $post_modified_gmt );
				$attach_date = get_date_from_gmt( get_post_field( 'post_modified_gmt', $attach_id, 'db' ) );
			return array(
				'code'  => 4,
				'error' => 1,
				'msg'   => __( 'Attachment found for document, but not currently linked', 'wp-document-revisions' ),
				// translators: %1$s is the document last modified date, %2$s is its attachment last modifified date.
				'msg2'  => sprintf( __( '[Modified Date: Document - %1$s, Attachment - %2$s]', 'wp-document-revisions' ), $post_date, $attach_date ),
				'fix'   => 1,
				'parm'  => $attach_id,
			);
		}

		// there was a attachment id in post_content - but did it point to an attachment.
		if ( false !== $att_error && 2 === $att_error['code'] ) {
			$last = self::get_last_attachment( $doc_id );
			if ( $last ) {
				$post_date   = get_date_from_gmt( $post_modified_gmt );
				$attach_date = get_date_from_gmt( get_post_field( 'post_modified_gmt', $last, 'db' ) );
				return array(
					'code'  => 5,
					'error' => 1,
					'msg'   => __( 'Document links to invalid attachment. An attachment exists and can replace link', 'wp-document-revisions' ),
					// translators: %1$s is the document last modified date, %2$s is its attachment last modifified date.
					'msg2'  => sprintf( __( '[Modified Date: Document - %1$s, Attachment - %2$s]', 'wp-document-revisions' ), $post_date, $attach_date ),
					'fix'   => 1,
					'parm'  => $last,
				);
			}
		}

		// return any attachment found.
		return $att_error;
	}

	/**
	 * Validate individual permalinks.
	 *
	 * @since 3.5.0
	 *
	 * @param id     $doc_id      ID of a post object.
	 * @param string $attach_id   attachment id from post content field.
	 * @param string $post_status post status field.
	 * @param string $post_date   post date field.
	 * @param string $post_name   post name field.
	 * @param string $guid        post guid field.
	 * @return array||false
	 */
	private static function validate_guid( $doc_id, $attach_id, $post_status, $post_date, $post_name, $guid ) {
		$msg_09 = esc_html__( 'The guid is not the expected "ugly" permalink', 'wp-document-revisions' );
		if ( get_option( 'document_link_date' ) ) {
			$msg_10 = esc_html__( 'The guid does not contain the site URL.', 'wp-document-revisions' );
		} else {
			$msg_10 = esc_html__( 'The guid does not contain the correct date.', 'wp-document-revisions' );
		}
		$msg_11 = esc_html__( 'The guid does not contain the document name.', 'wp-document-revisions' );
		$msg_12 = esc_html__( 'The guid does not reflect the complete document permalink.', 'wp-document-revisions' );
		global $wp_rewrite;
		$permalink1 = site_url( '?post_type=document&p=' . $doc_id );
		$permalink2 = str_replace( '/?', '?', $permalink1 );
		if ( '' === $wp_rewrite->permalink_structure || in_array( $post_status, array( 'pending', 'draft' ), true ) ) {
			$permalink1 = site_url( '?post_type=document&p=' . $doc_id );
			$permalink2 = str_replace( '/?', '?', $permalink1 );
			if ( $guid !== $permalink1 && $guid !== $permalink2 ) {
				return array(
					'code'  => 9,
					'error' => 0,
					'msg'   => $msg_09,
					'fix'   => 1,
					'parm'  => $doc_id,
				);
			}
			return true;
		}
		// find the permalink (except extension).
		$year_mth  = ( get_option( 'document_link_date' ) ? '' : '/' . str_replace( '-', '/', substr( $post_date, 0, 7 ) ) );
		$permalink = home_url( self::$parent->document_slug() . $year_mth . '/' );
		if ( str_contains( $guid, $permalink ) ) {
			// now add the post name.
			$permalink .= $post_name;
			if ( str_contains( $guid, $permalink ) ) {
				$p2 = $permalink . '/';
				// post name without extension is OK.
				if ( $guid !== $permalink && $guid !== $p2 ) {
					// get the extension.
					$file       = get_post_meta( $attach_id, '_wp_attached_file', true );
					$permalink .= self::$parent->get_extension( $file );
					$p2         = $permalink . '/';
					if ( $guid !== $permalink && $guid !== $p2 ) {
						// permalink .
						return array(
							'code'  => 12,
							'error' => 0,
							'msg'   => $msg_12,
							'fix'   => 1,
							'parm'  => $doc_id,
						);
					}
				}
			} else {
				return array(
					'code'  => 11,
					'error' => 0,
					'msg'   => $msg_11,
					'fix'   => 1,
					'parm'  => $doc_id,
				);
			}
		} elseif ( $guid !== $permalink1 && $guid !== $permalink2 ) {
			// Ugly one is accepable as it is unique.

			if ( '' !== $year_mth ) {
				// Not an ugly one, but guid does not contain the correct month.
				$msg = __( 'The guid does not contain the correct date.', 'wp-document-revisions' );
			} else {
				$msg = __( 'The guid does not contain the site URL.', 'wp-document-revisions' );
			}
			return array(
				'code'  => 10,
				'error' => 0,
				'msg'   => $msg_10,
				'fix'   => 1,
				'parm'  => $doc_id,
			);
		}
	}

	/**
	 * Get the last attachment document.
	 *
	 * @since 3.4.0
	 *
	 * @param id $doc_id ID of a post object.
	 * @return int||false
	 */
	private static function get_last_attachment( $doc_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$attach = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT MAX(ID) AS ID
				 FROM {$wpdb->prefix}posts 
				 WHERE post_type = 'attachment'
				 AND post_parent = %d
				",
				$doc_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( 0 === $wpdb->num_rows ) {
			return false;
		}
		return ( is_null( $attach['ID'] ) ? false : $attach['ID'] );
	}

	/**
	 * Returns the.validation result of a document attachment object.
	 *
	 * @since 3.4.0
	 *
	 * @param id     $attach_id id of an attachment post object.
	 * @param string $doc_id    id of the document post object.
	 * @return int||false
	 */
	private static function check_attachment( $attach_id, $doc_id ) {
		$attach = get_post( $attach_id );
		if ( ( ! is_object( $attach ) ) || 'attachment' !== $attach->post_type || (int) $doc_id !== $attach->post_parent ) {
			// post_content points to an invalid attachment.
			return array(
				'code'  => 2,
				'error' => 1,
				'msg'   => __( 'Document links to an invalid attachment record', 'wp-document-revisions' ),
				'fix'   => 0,
			);
		}

		// check that there is an file.
		$file = get_attached_file( $attach_id );

		// manipulate file as in serve_file process.
		$file = self::check_document_folder( $file );
		if ( ! file_exists( $file ) ) {
			// file does not exist. Get it from the standard media location. May be another plug-in that modified it.
			remove_filter( 'get_attached_file', array( self::$parent, 'get_attached_file_filter' ), 10, 2 );
			$media = get_attached_file( $attach_id );

			// make sure we're looking at the document directory again.
			add_filter( 'get_attached_file', array( self::$parent, 'get_attached_file_filter' ), 10, 2 );

			if ( file_exists( $media ) ) {
				// file in image folder, not document.
				return array(
					'code'  => 7,
					'error' => 1,
					'msg'   => __( 'Document attachment exists but related file not in document location', 'wp-document-revisions' ),
					'fix'   => 1,
					'parm'  => $attach_id,
				);
			}
			return array(
				'code'  => 3,
				'error' => 1,
				'msg'   => __( 'Document attachment exists but related file not found', 'wp-document-revisions' ),
				'fix'   => 0,
			);
		}

		if ( ! is_readable( $file ) ) {
			// file is not readable.
			return array(
				'code'  => 8,
				'error' => 1,
				'msg'   => __( 'Document attachment file exists but is not readable', 'wp-document-revisions' ),
				'fix'   => 0,
			);
		}

		/**
		 * Filter to Switch off md5 format attachment validation.
		 *
		 * @since 3.6
		 * @param boolean true.
		 */
		if ( apply_filters( 'document_validate_md5', true ) ) {
			// check post_title (warning only).
			$filename = pathinfo( $file, PATHINFO_FILENAME );
			if ( ! preg_match( '/^[a-f0-9]{32}$/', $filename ) ) {
				// file does not appear to be md5 encoded.
				return array(
					'code'  => 6,
					'error' => 0,
					'msg'   => __( 'Document attachment does not appear to be md5 encoded', 'wp-document-revisions' ),
					'fix'   => 1,
					'parm'  => $attach_id,
				);
			}
		}

		return false;
	}

	/**
	 * Checks attached file is in document folder.
	 *
	 * @since 3.4.0
	 *
	 * @param string $file file name as returned by get_attached_file.
	 * @return string
	 */
	private static function check_document_folder( $file ) {
		// manipulate file as in serve_file process.
		return apply_filters( 'document_path', $file );
	}

	/**
	 * Adds help tabs to help tab API.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	public static function add_help_tab() {
		$screen = get_current_screen();

		if ( 'document_page_wpdr_validate' !== $screen->id ) {
			return;
		}
		// parent key is the id of the current screen
		// child key is the title of the tab
		// value is the help text (as HTML).
		$help = array(
			__( 'Overview', 'wp-document-revisions' ) =>
				'<p>' . __( 'This tool allows you to find errors in the internal structure of documents.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'It will review all documents that you are able to edit.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'This version does not address revision data.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'Two types of issues can be identified - Errors and Warnings.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'If you have an error reported here, then you will get an "Error 404 - Document Not Found" (or equivalent) message on trying to view the document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'Some of these can be fixed using data from within the system - and a button is available to fix the issue, but others cannot.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'When they cannot be fixed, the resolution is normally either to delete the document or to load a new version of the document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'This resolution process will, of course, also address unfixed, but fixable, issues as well.', 'wp-document-revisions' ) . '</p>',
			__( 'Errors', 'wp-document-revisions' )   =>
				'<p>' . __( 'These are issues that stop you displaying your document on the front end. Problems include:', 'wp-document-revisions' ) . '</p><p>' .
				__( 'The identifier of the latest attachment (i.e. the current document) is held in the post content field.', 'wp-document-revisions' ) . '<br/>' .
				__( 'If this is missing or points to a non-existant one then the document cannot be viewed.', 'wp-document-revisions' ) . '<br/>' .
				__( 'However if there are any attachments attached to the document then we can display the latest one.', 'wp-document-revisions' ) . '<br/>' .
				__( 'This is done by updating the document record so that the content contains the attachment identifier.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'There is no attachment associated with the document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'There is an identifier in the document but either there is no attachment or it does not relate to this document.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'There is an identifier that links to a document attachment but there is no underlying file.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'As a variant of this last case, you may have set up the plugin so that the document file store is one of your choosing and not the standard Media store.', 'wp-document-revisions' ) . '<br/>' .
				__( 'In this very specific case, when it has been unable to find the file in the document store, it will look for it in the Media store.', 'wp-document-revisions' ) . '<br/>' .
				__( 'If found there it will offer you a fix which is to move the file from the Media store to the document store.', 'wp-document-revisions' ) . '</p>',
			__( 'Warnings', 'wp-document-revisions' ) =>
				'<p>' . __( 'The nature of the web technology used to deliver WordPress means that if you know the name of the file held on the server then you can access the file directly', 'wp-document-revisions' ) . '<br/>' .
				__( 'It is a design feature of this plugin that you should only be able to access a document if you go via WordPress itself.', 'wp-document-revisions' ) . '<br/>' .
				__( 'So to deliver this the plugin changes the file name to an md5-encoded file name and then hides it from you when using the application.', 'wp-document-revisions' ) . '</p><p>' .
				__( 'The fix is therefore to md5-encode the file name.', 'wp-document-revisions' ) . '</p>',
		);

		// loop through each tab in the help array and add.
		foreach ( $help as $title => $content ) {
			$screen->add_help_tab(
				array(
					'title'   => $title,
					'id'      => str_replace( ' ', '_', $title ),
					'content' => $content,
				)
			);
		}
	}
}
