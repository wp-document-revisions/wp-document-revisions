<?php
/**
 * Tests the base URL of draft and pending document permalinks (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document permalink URL tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Permalink_Urls extends Test_Common_WPDR {

	/**
	 * Draft document.
	 *
	 * @var int
	 */
	private static $draft_doc;

	/**
	 * Create the documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$draft_doc = $factory->post->create(
			array(
				'post_title'  => 'Permalink URL Draft',
				'post_status' => 'draft',
				'post_type'   => 'document',
			)
		);
	}

	/**
	 * Use a pretty permalink structure with a trailing slash.
	 */
	public function set_up() {
		parent::set_up();
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%postname%/' );
	}

	/**
	 * Restore the permalink structure. The siteurl option is rolled back with the test transaction.
	 */
	public function tear_down() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );
		parent::tear_down();
	}

	/**
	 * Draft permalinks use the home URL, not the WordPress install URL (e.g. /wp/ on Bedrock).
	 */
	public function test_draft_permalink_uses_home_url() {
		update_option( 'siteurl', home_url( '/wp' ) );

		$link = get_permalink( self::$draft_doc );
		self::assertSame( home_url( '/?post_type=document&p=' . self::$draft_doc ), $link );
		self::assertStringNotContainsString( '/wp/', $link );
	}

	/**
	 * The document_home_url filter also applies to draft permalinks.
	 */
	public function test_draft_permalink_applies_document_home_url_filter() {
		$filter = function () {
			return 'https://docs.example.com';
		};
		add_filter( 'document_home_url', $filter );
		$link = get_permalink( self::$draft_doc );
		remove_filter( 'document_home_url', $filter );

		self::assertSame( 'https://docs.example.com/?post_type=document&p=' . self::$draft_doc, $link );
	}

	/**
	 * Validate Structure accepts both the new home_url() and the older site_url() form of a draft's guid.
	 */
	public function test_validate_guid_accepts_home_and_site_url() {
		update_option( 'siteurl', home_url( '/wp' ) );

		$validate = new ReflectionMethod( 'WP_Document_Revisions_Validate_Structure', 'validate_guid' );
		$validate->setAccessible( true );

		$draft = get_post( self::$draft_doc );
		foreach ( array( home_url( '/' ), site_url( '/' ) ) as $base ) {
			$guid = $base . '?post_type=document&p=' . self::$draft_doc;
			self::assertTrue( $validate->invoke( null, self::$draft_doc, '', 'draft', $draft->post_date, $draft->post_name, $guid ), $guid );
		}
	}
}
