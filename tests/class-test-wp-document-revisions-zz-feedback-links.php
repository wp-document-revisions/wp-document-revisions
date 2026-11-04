<?php
/**
 * Tests the documentation, support, ideas and review links in the admin.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Feedback link tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Feedback_Links extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Create an editor and enough documents for the review prompt.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		for ( $i = 0; $i < WP_Document_Revisions_Admin::REVIEW_MIN_DOCS; $i++ ) {
			$factory->post->create(
				array(
					'post_type'   => 'document',
					'post_status' => 'publish',
					'post_author' => self::$editor,
				)
			);
		}
	}

	/**
	 * Show the documents list screen as the editor.
	 */
	public function set_up() {
		parent::set_up();
		wp_cache_flush();
		wp_set_current_user( self::$editor );
		set_current_screen( 'edit-document' );
	}

	/**
	 * Reset the screen.
	 */
	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Blanks one feedback link through the filter.
	 *
	 * @param string $key link to blank.
	 * @return callable the filter, for removal.
	 */
	private function blank( $key ) {
		$filter = function ( $urls ) use ( $key ) {
			$urls[ $key ] = '';
			return $urls;
		};
		add_filter( 'document_feedback_urls', $filter );
		return $filter;
	}

	/**
	 * Render an admin notice callback.
	 *
	 * @param string $method admin method name.
	 * @return string
	 */
	private function capture( $method ) {
		global $wpdr;
		ob_start();
		$wpdr->admin->$method();
		return (string) ob_get_clean();
	}

	/**
	 * Problems go to the WordPress.org forum and ideas to GitHub Discussions; the filter can change or drop them.
	 */
	public function test_feedback_urls() {
		$urls = WP_Document_Revisions::feedback_urls();
		self::assertSame( array( 'docs', 'support', 'ideas', 'review' ), array_keys( $urls ) );
		self::assertStringContainsString( 'wordpress.org/support/plugin/wp-document-revisions', $urls['support'] );
		self::assertStringContainsString( 'github.com/wp-document-revisions/wp-document-revisions/discussions', $urls['ideas'] );

		$filter = function () {
			return array(
				'docs'  => 'https://help.example.com/',
				'ideas' => array( 'not a string' ),
			);
		};
		add_filter( 'document_feedback_urls', $filter );
		$urls = WP_Document_Revisions::feedback_urls();
		remove_filter( 'document_feedback_urls', $filter );

		self::assertSame( 'https://help.example.com/', $urls['docs'] );
		self::assertSame( '', $urls['ideas'], 'Invalid values are dropped.' );
		self::assertSame( '', $urls['review'], 'Missing keys are dropped.' );
	}

	/**
	 * The links are added under this plugin only on the Plugins screen.
	 */
	public function test_plugin_row_meta() {
		global $wpdr;
		$file  = plugin_basename( dirname( __DIR__ ) . '/wp-document-revisions.php' );
		$links = $wpdr->admin->plugin_row_meta( array( 'Version 1' ), $file );

		self::assertCount( 5, $links );
		self::assertSame( 'Version 1', $links[0] );
		self::assertStringContainsString( 'Suggest an idea', implode( '', $links ) );

		self::assertSame( array( 'Version 1' ), $wpdr->admin->plugin_row_meta( array( 'Version 1' ), 'akismet/akismet.php' ) );

		$filter = $this->blank( 'review' );
		$links  = $wpdr->admin->plugin_row_meta( array(), $file );
		remove_filter( 'document_feedback_urls', $filter );
		self::assertCount( 3, $links, 'A blanked link is left out.' );
	}

	/**
	 * The Help tab sidebar on document screens lists the links.
	 */
	public function test_help_sidebar() {
		global $wpdr;
		$wpdr->admin->add_help_tab();
		$sidebar = get_current_screen()->get_help_sidebar();

		self::assertStringContainsString( 'For more information:', $sidebar );
		self::assertStringContainsString( 'Get support', $sidebar );
		self::assertStringContainsString( 'Suggest an idea', $sidebar );
	}

	/**
	 * The review prompt also offers the ideas and support links, so unhappy users have somewhere to go.
	 */
	public function test_review_prompt_offers_other_routes() {
		$html = $this->capture( 'review_prompt' );

		self::assertStringContainsString( 'Leave a review', $html );
		self::assertStringContainsString( 'Something missing or not working?', $html );
		self::assertStringContainsString( 'discussions/categories/ideas', $html );
		self::assertStringContainsString( 'wordpress.org/support/plugin/wp-document-revisions/"', $html );
	}

	/**
	 * Without a review link there's nothing to prompt for.
	 */
	public function test_review_prompt_hidden_without_review_url() {
		$filter = $this->blank( 'review' );
		$html   = $this->capture( 'review_prompt' );
		remove_filter( 'document_feedback_urls', $filter );

		self::assertSame( '', $html );
	}

	/**
	 * Choosing to leave a review goes to the filtered review link.
	 */
	public function test_review_redirect_uses_filtered_url() {
		global $wpdr;
		$filter = function ( $urls ) {
			$urls['review'] = 'https://reviews.example.com/';
			return $urls;
		};
		add_filter( 'document_feedback_urls', $filter );
		$redirect = function ( $location ) {
			throw new Exception( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		};
		add_filter( 'wp_redirect', $redirect );

		$_GET['wpdr_review_dismiss'] = '1';
		$_GET['wpdr_review_go']      = '1';
		$_GET['wpdr_review_nonce']   = wp_create_nonce( 'wpdr_review_dismiss' );

		$location = '';
		try {
			$wpdr->admin->handle_review_dismissal();
		} catch ( Exception $e ) {
			$location = $e->getMessage();
		} finally {
			unset( $_GET['wpdr_review_dismiss'], $_GET['wpdr_review_go'], $_GET['wpdr_review_nonce'] );
			remove_filter( 'wp_redirect', $redirect );
			remove_filter( 'document_feedback_urls', $filter );
			delete_user_meta( self::$editor, WP_Document_Revisions_Admin::REVIEW_DISMISSED_META );
		}

		self::assertSame( 'https://reviews.example.com/', $location );
	}
}
