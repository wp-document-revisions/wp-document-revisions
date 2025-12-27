<?php
/**
 * Verifies basic CRUD operations of WP Document Revisions
 *
 * Loads of operations done in wpSetUpBeforeClass and wpTearDownAfterClass dont appear in Codecov
 * So nothing new here, just do what is in those functions.
 *
 * @author Neil James <neil@familyjames.com> extended from Benjamin J. Balter <ben@balter.com>
 * @package WP Document Revisions
 */

/**
 * Main WP Document Revisions tests
 */
class Test_WP_Document_Revisions_Other extends Test_Common_WPDR {

	/**
	 * List of users being tested.
	 *
	 * @var WP_User[]
	 */
	protected static $users = array(
		'editor' => null,
		'author' => null,
	);

	/**
	 * Author Public Post ID
	 *
	 * @var integer
	 */
	private static $editor_public_post;

	/**
	 * Editor Private Post ID
	 *
	 * @var integer
	 */
	private static $editor_private_post;

	/**
	 * Set up common data.
	 *
	 * @return void
	 */
	public function test_package() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file2 );

		// Editor Private.
		self::$editor_private_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_private_post, self::$test_file );

		// verify structure.
		self::verify_structure( self::$editor_public_post, 2, 2 );
		self::verify_structure( self::$editor_private_post, 1, 1 );

		// make sure that we have the admin set up.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$editor_public_post, true );
		wp_delete_post( self::$editor_private_post, true );

		// delete successful, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );
	}

	/**
	 * Test EF support.
	 *
	 * @return void
	 */
	public function test_ef_support() {
		// ensure EF present.
		include __DIR__ . '/class-ef-custom-status.php';
		global $edit_flow;
		$edit_flow                = new stdClass();
		$edit_flow->custom_status = new EF_Custom_Status();

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// call EF support.
		$wpdr->edit_flow_support();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file2 );

		// Editor Private.
		self::$editor_private_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_private_post, self::$test_file );

		// verify structure.
		self::verify_structure( self::$editor_public_post, 2, 2 );
		self::verify_structure( self::$editor_private_post, 1, 1 );

		// test status component.
		ob_start();
		$wpdr->post_status_column_cb( 'empty', self::$editor_private_post );
		$wpdr->post_status_column_cb( 'status', self::$editor_private_post );
		$output = ob_get_clean();
		self::assertSame( 'Private', $output, 'status private' );

		// make sure that we have the admin set up.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$editor_public_post, true );
		wp_delete_post( self::$editor_private_post, true );

		// delete successful, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// Exercise other paths.
		$edit_flow->custom_status->module->options->post_types['document'] = 'off';
		$wpdr->edit_flow_support();
		self::assertTrue( true, 'document off' );

		unset( $edit_flow->custom_status->module->options->post_types['document'] );
		$wpdr->edit_flow_support();
		self::assertTrue( true, 'document unset' );
	}

	/**
	 * Test PublishPress_Statuses support.
	 *
	 * @return void
	 */
	public function test_ps_support() {
		// ensure PS present.
		include __DIR__ . '/class-publishpress-statuses.php';
		new PublishPress_Statuses();

		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// call PS support.
		$wpdr->publishpress_statuses_support();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file2 );

		// Editor Private.
		self::$editor_private_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Private - ' . time(),
				'post_status'  => 'private',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::$editor_private_post ), 'Failed inserting document Editor Private' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_private_post, self::$test_file );

		// verify structure.
		self::verify_structure( self::$editor_public_post, 2, 2 );
		self::verify_structure( self::$editor_private_post, 1, 1 );

		// test status component.
		ob_start();
		$wpdr->post_status_column_cb( 'empty', self::$editor_private_post );
		$wpdr->post_status_column_cb( 'status', self::$editor_private_post );
		$output = ob_get_clean();
		self::assertSame( 'Private', $output, 'status private' );

		// make sure that we have the admin set up.
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}

		// add the attachment delete process.
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		wp_delete_post( self::$editor_public_post, true );
		wp_delete_post( self::$editor_private_post, true );

		// delete successful, remove the attachment delete process.
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );

		// Exercise other paths.
		PublishPress_Statuses::instance()->options->enabled = 'off';
		$wpdr->publishpress_statuses_support();
		self::assertTrue( true, 'enabled off' );

		unset( PublishPress_Statuses::instance()->options->post_types['document'] );
		$wpdr->publishpress_statuses_support();
		self::assertTrue( true, 'document unset' );
	}

	/**
	 * Test Featured Image size.
	 *
	 * @return void
	 */
	public function test_featured_image_size() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file2 );

		// test routine.
		$size = $wpdr->document_featured_image_size( 'thumbnail', self::$editor_public_post );
		self::assertSame( 'thumbnail', $size, 'thumbnail' );

		$size = $wpdr->document_featured_image_size( 'post-thumbnail', self::$editor_public_post );
		$comp = array(
			get_option( 'thumbnail_size_w' ),
			get_option( 'thumbnail_size_h' ),
		);

		self::assertSame( $comp, $size, 'post-thumbnail' );
	}

	/**
	 * Test sample permalink.
	 *
	 * @return void
	 */
	public function test_sample_permalink() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// test routine before attachment.
		$html = $wpdr->sample_permalink_html_filter( 'initial', self::$editor_public_post );
		self::assertEmpty( $html, 'pre attach' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// add second attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file2 );

		// test routine.
		$html = $wpdr->sample_permalink_html_filter( 'initial', self::$editor_public_post );
		self::assertSame( 'initial', $html, 'post-thumbnail' );
	}

	/**
	 * Test file rewrite.
	 *
	 * @return void
	 */
	public function test_file_rewrite() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		$file = array(
			'name'     => 'Document.pdf',
			'tmp_name' => 'doc.pdf',
			'size'     => 999,
			'error'    => 0,
		);

		// straight return.
		$wpdr->rewrite_file_url( $file );
		$wpdr->filename_rewrite( $file );

		$_POST['post_id'] = self::$editor_public_post;

		// possibly image.
		$_POST['type'] = 'file';
		$wpdr->rewrite_file_url( $file );
		$wpdr->filename_rewrite( $file );

		// set pagenow.
		global $pagenow;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'async-upload.php';
		$file    = $wpdr->rewrite_file_url( $file );
		self::assertTrue( str_contains( $file['url'], 'editor-public' ), 'post title' );
		self::assertTrue( str_contains( $file['url'], '.txt' ), 'file type' );

		$file = $wpdr->filename_rewrite( $file );

		self::assertTrue( true, 'file_rewrite' );
	}

	/**
	 * Test directory filter.
	 *
	 * @return void
	 */
	public function test_directory_filter() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// get the default directory.
		$dir = $wpdr::$wp_default_dir;

		$_POST['post_id'] = self::$editor_public_post;

		// straight return.
		$wpdr->document_upload_dir_filter( $dir );

		// possibly image.
		$_POST['type'] = 'file';
		$wpdr->document_upload_dir_filter( $dir );

		// set pagenow.
		global $pagenow;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$pagenow = 'async-upload.php';
		$res     = $wpdr->document_upload_dir_filter( $dir );
		self::assertSame( $dir, $res, 'directory' );

		self::assertTrue( true, 'directory_filter' );
	}

	/**
	 * Test sending email.
	 *
	 * @return void
	 */
	public function test_send_override_mail() {
		// init user roles.
		global $wpdr;
		if ( ! $wpdr ) {
			$wpdr = new WP_Document_Revisions();
		}
		$wpdr->register_cpt();
		$wpdr->add_caps();

		// create users and assign role.
		// Note that editor can do everything admin can do.
		self::$users = array(
			'editor' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Editor',
					'role'          => 'editor',
				)
			),
			'author' => self::factory()->user->create_and_get(
				array(
					'user_nicename' => 'Author',
					'role'          => 'author',
				)
			),
		);

		// flush cache for good measure.
		wp_cache_flush();

		// create posts for scenarios.
		// Editor Public.
		self::$editor_public_post = self::factory()->post->create(
			array(
				'post_title'   => 'Editor Public - ' . time(),
				'post_status'  => 'publish',
				'post_author'  => self::$users['editor']->ID,
				'post_content' => '',
				'post_excerpt' => 'Test Upload',
				'post_type'    => 'document',
			)
		);

		self::assertFalse( is_wp_error( self::factory(), self::$editor_public_post ), 'Failed inserting document Editor Public' );

		// add attachment.
		self::add_document_attachment( self::factory(), self::$editor_public_post, self::$test_file );

		// verify structure.
		self::verify_structure( self::$editor_public_post, 1, 1 );

		// cannot test with 4.9 as filter not available.
		global $wp_version;
		if ( version_compare( $wp_version, '5.7' ) < 0 ) {
			return;
		}

		// make sure we switch off mailing.
		add_filter( 'pre_wp_mail', '__return_false', 10, 3 );

		$ret = $wpdr->send_override_notice( self::$editor_public_post, self::$users['editor']->ID, self::$users['author']->ID );

		remove_filter( 'pre_wp_mail', '__return_false', 10, 3 );
		self::assertFalse( $ret, 'lock message' );
	}
}
