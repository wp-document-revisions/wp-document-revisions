<?php
/**
 * Tests the Document Library block and shortcode.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Document Library tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Document_Library extends WP_UnitTestCase {

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
	 * Term in the test taxonomy, assigned to the published document.
	 *
	 * @var int
	 */
	private static $topic;

	/**
	 * The library under test.
	 *
	 * @var WP_Document_Revisions_Document_Library
	 */
	private $library;

	/**
	 * Create the users, taxonomy and documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();
		self::register_topic();

		self::$editor = $factory->user->create(
			array(
				'role'         => 'editor',
				'display_name' => 'Edna Editor',
			)
		);

		$statuses = array(
			'publish'  => array( 'post_status' => 'publish' ),
			'other'    => array( 'post_status' => 'publish' ),
			'private'  => array( 'post_status' => 'private' ),
			'draft'    => array( 'post_status' => 'draft' ),
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
						'post_title'  => 'Library ' . $key,
						'post_author' => self::$editor,
					)
				)
			);
			$attach = $factory->attachment->create(
				array(
					'post_parent'    => $doc,
					'file'           => 'library-' . $key . '.pdf',
					'post_mime_type' => 'application/pdf',
				)
			);
			// The description sits after the attachment marker.
			$content = $wpdr->format_doc_id( $attach ) . 'Description ' . $key;
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_content' => $content ), array( 'ID' => $doc ) );
			clean_post_cache( $doc );
			self::$docs[ $key ] = $doc;
		}

		self::$topic = $factory->term->create(
			array(
				'taxonomy' => 'wpdr_test_topic',
				'name'     => 'Minutes',
			)
		);
		wp_set_object_terms( self::$docs['publish'], array( self::$topic ), 'wpdr_test_topic' );
	}

	/**
	 * Registers the test taxonomy on documents.
	 */
	private static function register_topic() {
		if ( ! taxonomy_exists( 'wpdr_test_topic' ) ) {
			register_taxonomy( 'wpdr_test_topic', 'document', array( 'query_var' => 'wpdr_topic' ) );
		}
	}

	/**
	 * Reset state between tests.
	 */
	public function set_up() {
		parent::set_up();
		wp_cache_flush();
		self::register_topic();
		$this->library = new WP_Document_Revisions_Document_Library();
	}

	/**
	 * Render as a user.
	 *
	 * @param int                  $user_id user.
	 * @param array<string, mixed> $atts    raw attributes.
	 * @return string
	 */
	private function render_as( $user_id, array $atts = array() ) {
		wp_set_current_user( $user_id );
		return $this->library->render( $this->library->normalize( $atts ) );
	}

	/**
	 * The list layout is the default and shows the default fields.
	 */
	public function test_list_layout_default_fields() {
		$html = $this->render_as( self::$editor );

		$this->assertStringContainsString( 'class="wpdr-library wpdr-library--list"', $html );
		$this->assertStringContainsString( '<ul class="wpdr-library__items">', $html );
		$this->assertStringContainsString( 'Library publish', $html );
		$this->assertStringContainsString( '<span class="wpdr-library__badge">PDF</span>', $html );
		$this->assertStringContainsString( 'By Edna Editor', $html );
		$this->assertStringContainsString( '<time datetime=', $html );
		$this->assertStringContainsString( 'wpdr-library__download wp-element-button', $html );
		// Not a default field.
		$this->assertStringNotContainsString( 'Description publish', $html );
	}

	/**
	 * The table layout has a header row and labels rows by title.
	 */
	public function test_table_layout() {
		$html = $this->render_as(
			self::$editor,
			array(
				'variant' => 'table',
				'fields'  => array( 'modified', 'title' ),
			)
		);

		$this->assertStringContainsString( '<div class="wpdr-library__scroll"><table class="wpdr-library__table">', $html );
		// Fields are shown in the fixed display order, whatever order they're given in.
		$this->assertMatchesRegularExpression( '#<thead><tr><th scope="col" class="wpdr-library__col--title">Title</th><th scope="col" class="wpdr-library__col--modified">Last Modified</th></tr></thead>#', $html );
		$this->assertStringContainsString( '<th scope="row" class="wpdr-library__col--title">', $html );
		$this->assertStringNotContainsString( 'By Edna Editor', $html );
	}

	/**
	 * The grid layout sets its column count.
	 */
	public function test_grid_layout_columns() {
		$html = $this->render_as(
			self::$editor,
			array(
				'variant' => 'grid',
				'columns' => 4,
			)
		);
		$this->assertStringContainsString( 'class="wpdr-library wpdr-library--grid" style="--wpdr-library-columns:4;"', $html );

		// Out of range values are clamped.
		$too_many = 5000;
		$settings = $this->library->normalize(
			array(
				'columns'     => 99,
				'numberposts' => $too_many,
				'variant'     => 'carousel',
				'orderby'     => 'rand',
			)
		);
		$this->assertSame( 6, $settings['columns'] );
		$this->assertSame( WP_Document_Revisions_Document_Library::MAX_DOCUMENTS, $settings['numberposts'] );
		$this->assertSame( 'list', $settings['variant'] );
		$this->assertSame( 'modified', $settings['orderby'] );
	}

	/**
	 * Taxonomy filters work for any document taxonomy, and other taxonomies are ignored.
	 */
	public function test_taxonomy_filter() {
		$html = $this->render_as( self::$editor, array( 'taxonomies' => array( 'wpdr_test_topic' => array( self::$topic ) ) ) );
		$this->assertStringContainsString( 'Library publish', $html );
		$this->assertStringNotContainsString( 'Library other', $html );

		$settings = $this->library->normalize( array( 'taxonomies' => array( 'post_tag' => array( 1 ) ) ) );
		$this->assertSame( array(), $settings['taxonomies'] );
	}

	/**
	 * The shortcode takes taxonomies by query var, with term slugs.
	 */
	public function test_shortcode_taxonomy_by_slug() {
		wp_set_current_user( self::$editor );
		$html = do_shortcode( '[document_library variant="table" wpdr_topic="minutes"]' );

		$this->assertStringContainsString( 'wpdr-library--table', $html );
		$this->assertStringContainsString( 'Library publish', $html );
		$this->assertStringNotContainsString( 'Library other', $html );
	}

	/**
	 * Visitors only see published documents.
	 */
	public function test_anonymous_sees_only_published() {
		$html = $this->render_as( 0 );

		$this->assertStringContainsString( 'Library publish', $html );
		$this->assertStringNotContainsString( 'Library private', $html );
		$this->assertStringNotContainsString( 'Library draft', $html );
	}

	/**
	 * Editors see private documents.
	 */
	public function test_editor_sees_private() {
		$this->assertStringContainsString( 'Library private', $this->render_as( self::$editor ) );
	}

	/**
	 * Without read via read, visitors get nothing.
	 */
	public function test_read_uses_read_off_blocks_visitors() {
		add_filter( 'document_read_uses_read', '__return_false' );
		$html = $this->render_as( 0 );
		remove_filter( 'document_read_uses_read', '__return_false' );

		$this->assertStringNotContainsString( 'Library publish', $html );
		$this->assertStringContainsString( 'not authorized', $html );
	}

	/**
	 * The download button follows serve_document_auth.
	 */
	public function test_download_follows_serve_document_auth() {
		add_filter( 'serve_document_auth', '__return_false', 99 );
		$html = $this->render_as( self::$editor );
		remove_filter( 'serve_document_auth', '__return_false', 99 );

		$this->assertStringNotContainsString( 'wpdr-library__download', $html );
		$this->assertStringContainsString( 'Library publish', $html );
	}

	/**
	 * The revision count needs read_document_revisions.
	 */
	public function test_revisions_needs_capability() {
		$atts = array( 'fields' => array( 'title', 'revisions' ) );

		$this->assertStringContainsString( 'wpdr-library__revisions', $this->render_as( self::$editor, $atts ) );
		$this->assertStringNotContainsString( 'wpdr-library__revisions', $this->render_as( 0, $atts ) );
	}

	/**
	 * Password-protected documents hide their description.
	 */
	public function test_password_protected_hides_description() {
		$html = $this->render_as( self::$editor, array( 'fields' => array( 'title', 'description' ) ) );

		$this->assertStringContainsString( 'Description publish', $html );
		$this->assertStringContainsString( 'Library password', $html );
		$this->assertStringNotContainsString( 'Description password', $html );
	}

	/**
	 * The row filter can change or remove fields.
	 */
	public function test_row_filter() {
		$filter = function ( $cells ) {
			$cells['title'] = 'Filtered';
			return $cells;
		};
		add_filter( 'document_library_row', $filter );
		$html = $this->render_as( self::$editor, array( 'fields' => array( 'title' ) ) );
		remove_filter( 'document_library_row', $filter );

		$this->assertStringContainsString( 'Filtered', $html );
		$this->assertStringNotContainsString( 'Library publish', $html );
	}

	/**
	 * Under EF/PP the workflow state field shows the post status, since they keep no post_status terms on documents.
	 */
	public function test_workflow_state_uses_post_status_under_efpp() {
		$key                                     = WP_Document_Revisions::$taxonomy_key_val;
		WP_Document_Revisions::$taxonomy_key_val = 'post_status';
		try {
			$html = $this->render_as( self::$editor, array( 'fields' => array( 'title', 'workflow_state' ) ) );
		} finally {
			WP_Document_Revisions::$taxonomy_key_val = $key;
		}

		$this->assertStringContainsString( '<span class="wpdr-library__state">Published</span>', $html );
		$this->assertStringContainsString( '<span class="wpdr-library__state">Private</span>', $html );
	}

	/**
	 * An empty result says so.
	 */
	public function test_empty_result() {
		$html = $this->render_as( self::$editor, array( 'taxonomies' => array( 'wpdr_test_topic' => array( 999999 ) ) ) );
		$this->assertStringContainsString( 'No documents found.', $html );
	}

	/**
	 * Titles are escaped.
	 */
	public function test_title_escaped() {
		wp_update_post(
			array(
				'ID'         => self::$docs['other'],
				'post_title' => 'Bad <script>alert(1)</script>',
			)
		);
		$html = $this->render_as( self::$editor );
		$this->assertStringNotContainsString( '<script>', $html );
	}
}
