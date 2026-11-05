<?php
/**
 * Tests that featured images and description images uploaded on a document are not attached to it,
 * and that Validate Structure detaches those attached by earlier versions (code 17).
 *
 * @package WP_Document_Revisions
 */

/**
 * Media parent tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Media_Parent extends Test_Common_WPDR {

	/**
	 * Editor user id.
	 *
	 * @var integer
	 */
	private static $editor;

	/**
	 * Document id.
	 *
	 * @var integer
	 */
	private static $doc;

	// phpcs:disable
	/**
	 * Set up common data before tests.
	 *
	 * @param WP_UnitTest_Factory $factory.
	 * @return void.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// phpcs:enable
		global $wpdr;
		$wpdr->register_cpt();
		$wpdr->add_caps();

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		$wpdr->add_caps();

		self::$doc = $factory->post->create(
			array(
				'post_title'   => 'Media parent',
				'post_status'  => 'private',
				'post_author'  => self::$editor,
				'post_type'    => 'document',
				'post_content' => '',
			)
		);

		self::add_document_attachment_new( $factory, self::$doc, self::$test_file );
	}

	/**
	 * Delete the posts.
	 */
	public static function wpTearDownAfterClass() {
		global $wpdr;
		if ( ! class_exists( 'WP_Document_Revisions_Admin' ) ) {
			$wpdr->admin_init();
		}
		add_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10, 1 );
		wp_delete_post( self::$doc, true );
		remove_action( 'delete_post', array( $wpdr->admin, 'delete_attachments_with_document' ), 10 );
	}

	/**
	 * Reset the per-request upload state.
	 */
	public function set_up() {
		parent::set_up();
		$this->set_static( 'document_upload', false );
		$this->set_static( 'media_upload_url', '' );
	}

	/**
	 * Reset the per-request upload state.
	 */
	public function tear_down() {
		$this->set_static( 'document_upload', false );
		$this->set_static( 'media_upload_url', '' );
		parent::tear_down();
	}

	/**
	 * Sets a private static of WP_Document_Revisions.
	 *
	 * @param string $name  property name.
	 * @param mixed  $value value.
	 */
	private function set_static( $name, $value ) {
		$prop = new ReflectionProperty( 'WP_Document_Revisions', $name );
		$prop->setAccessible( true );
		$prop->setValue( null, $value );
	}

	/**
	 * Calls a private static method of the Validate Structure class.
	 *
	 * @param string $name method name.
	 * @param mixed  ...$args arguments.
	 * @return mixed
	 */
	private function validate_call( $name, ...$args ) {
		$method = new ReflectionMethod( 'WP_Document_Revisions_Validate_Structure', $name );
		$method->setAccessible( true );
		return $method->invoke( null, ...$args );
	}

	/**
	 * Simulates wp_handle_upload() having moved a file into place.
	 *
	 * @param string $url the file URL.
	 */
	private function uploaded( $url ) {
		global $wpdr;
		$wpdr->note_media_upload(
			array(
				'file' => '/tmp/' . basename( $url ),
				'url'  => $url,
				'type' => 'image/jpeg',
			)
		);
	}

	/**
	 * Creates an attachment as media_handle_upload() does.
	 *
	 * @param int    $parent post parent.
	 * @param string $url    guid.
	 * @return int
	 */
	private function attachment( $parent, $url ) {
		return self::factory()->attachment->create(
			array(
				'guid'           => $url,
				'post_mime_type' => 'image/jpeg',
				'post_parent'    => $parent,
			)
		);
	}

	/**
	 * A featured / description image uploaded on a document is not attached to it.
	 */
	public function test_media_upload_not_attached() {
		$url = 'http://example.org/wp-content/uploads/2026/11/cover.jpg';
		$this->uploaded( $url );
		$id = $this->attachment( self::$doc, $url );

		self::assertSame( 0, (int) get_post_field( 'post_parent', $id ), 'Media upload attached to document' );

		// Used once: a further attachment for the same URL is left alone.
		$id2 = $this->attachment( self::$doc, $url );
		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $id2 ), 'Pending upload not cleared' );
	}

	/**
	 * Media uploaded on another post type stays attached to it.
	 */
	public function test_media_upload_on_post_kept() {
		$post = self::factory()->post->create();
		$url  = 'http://example.org/wp-content/uploads/2026/11/post.jpg';
		$this->uploaded( $url );
		$id = $this->attachment( $post, $url );

		self::assertSame( $post, (int) get_post_field( 'post_parent', $id ) );
	}

	/**
	 * Attachments created other than by the pending upload (imports, code) keep their parent.
	 */
	public function test_other_attachment_kept() {
		$this->uploaded( 'http://example.org/wp-content/uploads/2026/11/a.jpg' );
		$id = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/b.jpg' );

		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $id ) );
	}

	/**
	 * A document file upload is attached to the document.
	 */
	public function test_document_upload_kept() {
		$url = 'http://example.org/wp-content/uploads/2026/11/' . md5( 'doc' ) . '.pdf';
		$this->set_static( 'document_upload', true );
		$this->uploaded( $url );
		$id = $this->attachment( self::$doc, $url );

		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $id ) );
	}

	/**
	 * The document_keep_media_parent filter keeps the parent.
	 */
	public function test_keep_filter() {
		$url = 'http://example.org/wp-content/uploads/2026/11/kept.jpg';
		add_filter( 'document_keep_media_parent', '__return_true' );
		$this->uploaded( $url );
		$id = $this->attachment( self::$doc, $url );
		remove_filter( 'document_keep_media_parent', '__return_true' );

		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $id ) );
	}

	/**
	 * Validate Structure identifies media attached to a document and detaches it (code 17).
	 */
	public function test_validate_media_children() {
		global $wpdb, $wpdr;
		wp_set_current_user( self::$editor );

		$doc_file = $wpdr->get_document( self::$doc )->ID;

		// Child images, as attached by earlier versions.
		$featured = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/featured.jpg' );
		update_post_meta( $featured, '_wp_attached_file', '2026/11/featured.jpg' );
		update_post_meta( self::$doc, '_thumbnail_id', $featured );

		$by_class = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/by-class.jpg' );
		update_post_meta( $by_class, '_wp_attached_file', '2026/11/by-class.jpg' );

		$by_url = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/by-url.jpg' );
		update_post_meta( $by_url, '_wp_attached_file', '2026/11/by-url.jpg' );
		update_post_meta(
			$by_url,
			'_wp_attachment_metadata',
			array(
				'file'  => '2026/11/by-url.jpg',
				'sizes' => array(
					'medium' => array( 'file' => 'by-url-300x200.jpg' ),
				),
			)
		);

		$unused = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/unused.jpg' );
		update_post_meta( $unused, '_wp_attached_file', '2026/11/unused.jpg' );

		// A similar name that is not referenced must not match.
		$similar = $this->attachment( self::$doc, 'http://example.org/wp-content/uploads/2026/11/url.jpg' );
		update_post_meta( $similar, '_wp_attached_file', '2026/11/url.jpg' );

		// Description referencing the images (no revision created).
		$content = $wpdr->format_doc_id( $doc_file ) .
			'<p><img class="alignnone size-full wp-image-' . $by_class . '" src="http://example.org/wp-content/uploads/2026/11/by-class.jpg" /></p>' .
			'<p><img src="http://example.org/wp-content/uploads/2026/11/by-url-300x200.jpg" alt="" /></p>';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => self::$doc ) );
		clean_post_cache( self::$doc );

		$media = $this->validate_call( 'identify_media_children', self::$doc );
		ksort( $media );
		$expected = array( $featured, $by_class, $by_url );
		sort( $expected );
		self::assertSame( $expected, array_values( $media ), 'Media children not identified' );
		self::assertArrayNotHasKey( $doc_file, $media, 'Document file identified as media' );

		// Media are never orphans (which would be deleted).
		$orphans = (array) $this->validate_call( 'identify_orphans', $doc_file, self::$doc );
		foreach ( $expected as $id ) {
			self::assertArrayNotHasKey( $id, $orphans, 'Media listed as orphan' );
		}
		self::assertArrayHasKey( $unused, $orphans, 'Unreferenced attachment not an orphan' );

		// Reported as code 17 (ahead of 14).
		wp_cache_flush();
		ob_start();
		WP_Document_Revisions_Validate_Structure::page_validate();
		$output = ob_get_clean();
		self::assertSame( 1, substr_count( $output, 'wpdr_valid_fix(' . self::$doc . ',17,' . self::$doc . ')' ), 'Fix button not offered' );

		// Inconsistent parm refused.
		self::assertWPError( WP_Document_Revisions_Validate_Structure::fix_problem( self::$doc, 17, 1 ) );

		// Fix.
		self::assertTrue( WP_Document_Revisions_Validate_Structure::fix_problem( self::$doc, 17, self::$doc ) );
		foreach ( $expected as $id ) {
			self::assertSame( 0, (int) get_post_field( 'post_parent', $id ), 'Media not detached' );
		}
		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $doc_file ), 'Document file detached' );
		self::assertSame( self::$doc, (int) get_post_field( 'post_parent', $unused ), 'Unreferenced attachment detached' );
		self::assertSame( array(), $this->validate_call( 'identify_media_children', self::$doc ), 'Media still found after fix' );

		// Nothing left to fix.
		self::assertWPError( WP_Document_Revisions_Validate_Structure::fix_problem( self::$doc, 17, self::$doc ) );

		// tidy up the remaining test children.
		wp_delete_attachment( $unused, true );
		wp_delete_attachment( $similar, true );
	}
}
