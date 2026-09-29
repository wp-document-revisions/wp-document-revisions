<?php
/**
 * Tests reading the document_link_date setting (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Link date option tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Link_Date_Option extends WP_UnitTestCase {

	/**
	 * Remove both copies of the option.
	 */
	public function tear_down() {
		delete_option( 'document_link_date' );
		delete_site_option( 'document_link_date' );
		parent::tear_down();
	}

	/**
	 * On a single site the option is read as saved.
	 */
	public function test_reads_option() {
		global $wpdr;

		self::assertFalse( $wpdr->document_link_date() );
		update_option( 'document_link_date', true );
		self::assertTrue( $wpdr->document_link_date() );
	}

	/**
	 * On multisite the network value saved from Network Settings wins, and permalinks follow it.
	 */
	public function test_network_value_wins_on_multisite() {
		global $wpdr;

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		update_option( 'document_link_date', false );
		update_site_option( 'document_link_date', true );
		self::assertTrue( $wpdr->document_link_date() );

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );
		$doc  = self::factory()->post->create(
			array(
				'post_title'  => 'Link Date Doc',
				'post_type'   => 'document',
				'post_status' => 'publish',
				'post_date'   => '2020-05-01 00:00:00',
			)
		);
		$link = get_permalink( $doc );
		$wp_rewrite->set_permalink_structure( '' );

		self::assertStringNotContainsString( '/2020/05/', $link );
	}

	/**
	 * On multisite a per-site value is used when the network value was never saved.
	 */
	public function test_falls_back_to_site_value_on_multisite() {
		global $wpdr;

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		delete_site_option( 'document_link_date' );
		update_option( 'document_link_date', true );
		self::assertTrue( $wpdr->document_link_date() );
	}
}
