<?php
/**
 * Tests the revision keep list used when blocking external revision deletion.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revision keep list tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revision_Keep_List extends WP_UnitTestCase {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private static $document_id;

	/**
	 * Revision IDs, newest first.
	 *
	 * @var int[]
	 */
	private static $revision_ids = array();

	/**
	 * Create a document with four revisions of increasing age.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$document_id = $factory->post->create(
			array(
				'post_title'  => 'Keep List Document',
				'post_type'   => 'document',
				'post_status' => 'private',
			)
		);

		for ( $i = 1; $i <= 4; $i++ ) {
			self::$revision_ids[] = $factory->post->create(
				array(
					'post_type'   => 'revision',
					'post_status' => 'inherit',
					'post_parent' => self::$document_id,
					'post_name'   => self::$document_id . '-revision-v' . $i,
					'post_date'   => gmdate( 'Y-m-d H:i:s', time() - ( $i * HOUR_IN_SECONDS ) ),
				)
			);
		}
	}

	/**
	 * Limit documents to three revisions (the document itself plus the two newest revisions).
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'document_revisions_limit', array( $this, 'three' ) );
		wp_cache_delete( self::$document_id, 'document_revisions' );
	}

	/**
	 * Remove the limit.
	 */
	public function tear_down() {
		remove_filter( 'document_revisions_limit', array( $this, 'three' ) );
		parent::tear_down();
	}

	/**
	 * Revision limit.
	 *
	 * @return int
	 */
	public function three() {
		return 3;
	}

	/**
	 * Revisions inside the keep list are protected; older ones may be deleted.
	 *
	 * Both the first call (which builds the keep list) and later calls (which
	 * read the cached keep list) are exercised.
	 */
	public function test_keep_list_protects_newest_revisions() {
		global $wpdr;

		list( $newest, $second, $third, $oldest ) = array_map( 'get_post', self::$revision_ids );

		$this->assertFalse( $wpdr->possibly_delete_revision( null, $newest, true ), 'Newest revision should be kept' );
		$this->assertFalse( $wpdr->possibly_delete_revision( null, $second, true ), 'Second revision should be kept' );
		$this->assertNull( $wpdr->possibly_delete_revision( null, $third, true ), 'Revision beyond the limit may be deleted' );
		$this->assertNull( $wpdr->possibly_delete_revision( null, $oldest, true ), 'Oldest revision may be deleted' );
	}
}
