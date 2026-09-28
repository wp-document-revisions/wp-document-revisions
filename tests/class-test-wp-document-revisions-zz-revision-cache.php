<?php
/**
 * Tests the document revisions cache (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Revision cache tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Revision_Cache extends WP_UnitTestCase {

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private $doc;

	/**
	 * Create a document with two revisions, without the plugin's revision merging.
	 */
	public function set_up() {
		parent::set_up();

		$this->doc = self::factory()->post->create(
			array(
				'post_title'   => 'Revision Cache Doc',
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		for ( $i = 1; $i <= 2; $i++ ) {
			self::factory()->post->create(
				array(
					'post_type'   => 'revision',
					'post_status' => 'inherit',
					'post_parent' => $this->doc,
					'post_name'   => $this->doc . '-revision-v1',
					'post_date'   => gmdate( 'Y-m-d H:i:s', time() - ( $i * HOUR_IN_SECONDS ) ),
				)
			);
		}
	}

	/**
	 * Changing a returned revision doesn't change the cache.
	 */
	public function test_returned_revisions_are_copies() {
		global $wpdr;

		$first                  = $wpdr->get_revisions( $this->doc );
		$first[0]->post_content = 'changed by a caller';

		$second = $wpdr->get_revisions( $this->doc );
		self::assertSame( '', $second[0]->post_content );
	}

	/**
	 * Deleting a revision clears the cache.
	 */
	public function test_deleting_revision_clears_cache() {
		global $wpdr;

		$before = $wpdr->get_revisions( $this->doc );

		// The plugin protects revisions from deletion; lift that, as its own deletes do.
		remove_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999 );
		$deleted = wp_delete_post_revision( $before[1]->ID );
		add_filter( 'pre_delete_post', array( $wpdr, 'possibly_delete_revision' ), 9999, 3 );
		self::assertNotEmpty( $deleted );

		$after = $wpdr->get_revisions( $this->doc );

		self::assertCount( count( $before ) - 1, $after );
		self::assertNotContains( $before[1]->ID, wp_list_pluck( $after, 'ID' ) );
	}

	/**
	 * A new revision clears the cache, even without saving the document.
	 */
	public function test_new_revision_clears_cache() {
		global $wpdr;

		$before = $wpdr->get_revisions( $this->doc );
		self::factory()->post->create(
			array(
				'post_type'   => 'revision',
				'post_status' => 'inherit',
				'post_parent' => $this->doc,
				'post_name'   => $this->doc . '-revision-v1',
			)
		);
		$after = $wpdr->get_revisions( $this->doc );

		self::assertGreaterThan( count( $before ), count( $after ) );
	}

	/**
	 * Adding an attachment to the document clears the revision indices cache too.
	 */
	public function test_attachment_clears_cache() {
		global $wpdr;

		$wpdr->get_revision_indices( $this->doc );
		self::assertNotFalse( wp_cache_get( $this->doc, 'document_revision_indices' ) );

		self::factory()->attachment->create(
			array(
				'post_parent'    => $this->doc,
				'post_mime_type' => 'text/plain',
			)
		);

		self::assertFalse( wp_cache_get( $this->doc, 'document_revision_indices' ) );
		self::assertFalse( wp_cache_get( $this->doc, 'document_revisions' ) );
	}
}
