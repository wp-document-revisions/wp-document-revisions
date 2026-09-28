<?php
/**
 * Tests that document versions show who saved them, not the owner (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revision author tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revision_Author extends WP_UnitTestCase {

	/**
	 * Document owner.
	 *
	 * @var int
	 */
	private static $owner;

	/**
	 * Editor who saved the latest version.
	 *
	 * @var int
	 */
	private static $uploader;

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Create a document owned by one user and last saved by another.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$owner    = $factory->user->create(
			array(
				'role'         => 'editor',
				'display_name' => 'Owner Person',
			)
		);
		self::$uploader = $factory->user->create(
			array(
				'role'         => 'editor',
				'display_name' => 'Uploader Person',
			)
		);

		wp_set_current_user( self::$owner );
		self::$doc = $factory->post->create(
			array(
				'post_title'  => 'Revision Author Doc',
				'post_type'   => 'document',
				'post_status' => 'publish',
				'post_author' => self::$owner,
			)
		);
		wp_update_post(
			array(
				'ID'           => self::$doc,
				'post_excerpt' => 'First version',
			)
		);

		wp_set_current_user( self::$uploader );
		wp_update_post(
			array(
				'ID'           => self::$doc,
				'post_excerpt' => 'Second version',
			)
		);
		wp_set_current_user( 0 );
	}

	/**
	 * Clear cached revisions between tests.
	 */
	public function set_up() {
		parent::set_up();
		wp_cache_delete( self::$doc, 'document_revisions' );
	}

	/**
	 * The document row reports the latest saver; the owner is unchanged.
	 */
	public function test_get_revision_author() {
		global $wpdr;

		$revisions = $wpdr->get_revisions( self::$doc );
		self::assertSame( self::$owner, (int) $revisions[0]->post_author, 'Row 0 is still the document, owned by the owner.' );
		self::assertSame( self::$uploader, $wpdr->get_revision_author( self::$doc ) );
		self::assertSame( self::$uploader, $wpdr->get_revision_author( $revisions[1] ) );
	}

	/**
	 * The document_revision_author filter can override the result.
	 */
	public function test_filter() {
		global $wpdr;

		$filter = function () {
			return self::$owner;
		};
		add_filter( 'document_revision_author', $filter );
		$author = $wpdr->get_revision_author( self::$doc );
		remove_filter( 'document_revision_author', $filter );

		self::assertSame( self::$owner, $author );
	}

	/**
	 * The revision log shows the saver on the current row.
	 */
	public function test_revision_metabox_shows_saver() {
		global $wpdr;

		wp_set_current_user( self::$owner );
		ob_start();
		$wpdr->admin->revision_metabox( get_post( self::$doc ) );
		$output = ob_get_clean();

		preg_match_all( '#<tr>(.*?)</tr>#s', $output, $rows );
		self::assertNotEmpty( $rows[1] );
		self::assertStringContainsString( 'Uploader Person', $rows[1][0], 'The current row shows who saved it.' );
		self::assertStringNotContainsString( 'Owner Person', $rows[1][0], 'The current row does not show the owner.' );
	}

	/**
	 * The get-document-revisions ability reports the saver for the current version.
	 */
	public function test_ability_reports_saver() {
		global $wpdr;

		wp_set_current_user( self::$owner );
		$result = $wpdr->ability_get_document_revisions( array( 'document_id' => self::$doc ) );

		self::assertIsArray( $result );
		self::assertSame( 'Uploader Person', $result['revisions'][0]['author'] );
	}

	/**
	 * A save by someone other than the last revision's author keeps a separate revision.
	 */
	public function test_merge_heuristic_compares_savers() {
		global $wpdr;

		$revisions     = $wpdr->get_revisions( self::$doc );
		$last_revision = $revisions[1];
		$post          = get_post( self::$doc );

		wp_set_current_user( self::$owner );
		self::assertTrue( $wpdr->admin->identify_last_but_one( true, $last_revision, $post ), 'A different saver keeps the revision.' );
	}
}
