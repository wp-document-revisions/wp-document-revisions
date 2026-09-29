<?php
/**
 * Tests that document lists only show documents the viewer can read.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document list read-check tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_List_Read_Checks extends WP_UnitTestCase {

	/**
	 * Document IDs keyed by status.
	 *
	 * @var array<string, int>
	 */
	private static $docs = array();

	/**
	 * Editor who owns the documents.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Contributor who can't read them.
	 *
	 * @var int
	 */
	private static $contributor;

	/**
	 * Create the users and documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$editor      = $factory->user->create( array( 'role' => 'editor' ) );
		self::$contributor = $factory->user->create( array( 'role' => 'contributor' ) );

		$statuses = array(
			'publish'  => array( 'post_status' => 'publish' ),
			'private'  => array( 'post_status' => 'private' ),
			'draft'    => array( 'post_status' => 'draft' ),
			'pending'  => array( 'post_status' => 'pending' ),
			'password' => array(
				'post_status'   => 'publish',
				'post_password' => 'secret',
			),
		);
		foreach ( $statuses as $key => $args ) {
			$doc    = $factory->post->create(
				array_merge(
					$args,
					array(
						'post_type'   => 'document',
						'post_title'  => 'Doc ' . $key,
						'post_author' => self::$editor,
					)
				)
			);
			$attach = $factory->attachment->create( array( 'post_parent' => $doc ) );
			// The description sits after the attachment marker.
			$content = $wpdr->format_doc_id( $attach ) . 'Description ' . $key;
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_content' => $content ), array( 'ID' => $doc ) );
			clean_post_cache( $doc );
			self::$docs[ $key ] = $doc;
		}
	}

	/**
	 * Reset caches between tests.
	 */
	public function set_up() {
		parent::set_up();
		wp_cache_flush();
		// Earlier test classes can leave the workflow taxonomy key pointing at an unregistered taxonomy.
		add_filter( 'document_block_taxonomies', '__return_empty_array' );
	}

	/**
	 * Remove the taxonomy filter.
	 */
	public function tear_down() {
		remove_filter( 'document_block_taxonomies', '__return_empty_array' );
		parent::tear_down();
	}

	/**
	 * IDs from get_documents() as a user.
	 *
	 * @param int                  $user_id user.
	 * @param array<string, mixed> $args    query args.
	 * @return int[]
	 */
	private function list_as( $user_id, array $args ) {
		global $wpdr;
		wp_set_current_user( $user_id );
		return array_map(
			function ( $doc ) {
				return (int) $doc->ID;
			},
			$wpdr->get_documents( $args )
		);
	}

	/**
	 * Data provider: statuses a contributor mustn't list.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function hidden_statuses() {
		return array(
			'any'     => array( 'any' ),
			'private' => array( 'private' ),
			'draft'   => array( 'draft' ),
			'pending' => array( 'pending' ),
			'array'   => array( array( 'publish', 'private', 'draft', 'pending' ) ),
		);
	}

	/**
	 * Another user's unpublished documents aren't listed.
	 *
	 * @dataProvider hidden_statuses
	 * @param mixed $status post_status query var.
	 */
	public function test_get_documents_hides_unreadable( $status ) {
		$ids = $this->list_as(
			self::$contributor,
			array(
				'post_status' => $status,
				'numberposts' => -1,
			)
		);

		foreach ( array( 'private', 'draft', 'pending' ) as $key ) {
			self::assertNotContains( self::$docs[ $key ], $ids, $key . ' document listed' );
		}
	}

	/**
	 * The owner still sees them, and visitors see published documents.
	 */
	public function test_get_documents_keeps_readable() {
		$ids = $this->list_as(
			self::$editor,
			array(
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);
		foreach ( array( 'publish', 'private', 'draft', 'pending' ) as $key ) {
			self::assertContains( self::$docs[ $key ], $ids, $key . ' document missing for owner' );
		}

		$ids = $this->list_as( 0, array( 'numberposts' => -1 ) );
		self::assertContains( self::$docs['publish'], $ids, 'published document missing for visitor' );

		// A logged-in user without a role on this site, like a network user on another subsite.
		$ids = $this->list_as( self::factory()->user->create( array( 'role' => '' ) ), array( 'numberposts' => -1 ) );
		self::assertContains( self::$docs['publish'], $ids, 'published document missing for user without a role' );
	}

	/**
	 * The shortcode doesn't show unreadable documents or protected descriptions.
	 */
	public function test_shortcode_output() {
		wp_set_current_user( self::$contributor );
		$output = do_shortcode( '[documents post_status="any" show_descr="1" numberposts="-1"]' );

		self::assertStringContainsString( 'Doc publish', $output );
		self::assertStringContainsString( 'Description publish', $output );
		self::assertStringNotContainsString( 'Doc private', $output );
		self::assertStringNotContainsString( 'Doc draft', $output );
		self::assertStringNotContainsString( 'Description draft', $output );
		self::assertStringNotContainsString( 'Description password', $output );
	}

	/**
	 * The password attribute isn't accepted.
	 */
	public function test_shortcode_ignores_post_password() {
		wp_set_current_user( 0 );
		$output = do_shortcode( '[documents post_password="secret"]' );
		self::assertStringContainsString( 'Doc publish', $output, 'post_password attribute still filters the list' );
	}

	/**
	 * The recently revised block doesn't show others' drafts or protected descriptions.
	 */
	public function test_widget_block_output() {
		global $wpdr_widget;
		wp_set_current_user( self::$contributor );
		$output = $wpdr_widget->wpdr_documents_widget_display(
			array(
				'numberposts'       => 20,
				'post_stat_publish' => true,
				'post_stat_private' => true,
				'post_stat_draft'   => true,
				'show_descr'        => true,
			)
		);

		self::assertStringContainsString( 'Doc publish', $output );
		self::assertStringNotContainsString( 'Doc private', $output );
		self::assertStringNotContainsString( 'Doc draft', $output );
		self::assertStringNotContainsString( 'Description password', $output );
	}
}
