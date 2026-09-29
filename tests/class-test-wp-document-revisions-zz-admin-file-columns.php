<?php
/**
 * Tests the documents list Revisions/File columns, sorting and "Missing file" filter (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Admin list file column tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Admin_File_Columns extends Test_Common_WPDR {

	/**
	 * Document with a file and two extra revisions.
	 *
	 * @var int
	 */
	private static $with_file;

	/**
	 * Document without a file.
	 *
	 * @var int
	 */
	private static $without_file;

	/**
	 * Create the documents.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$with_file = $factory->post->create(
			array(
				'post_type'    => 'document',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		self::add_document_attachment( $factory, self::$with_file, self::$test_file );
		for ( $i = 1; $i <= 2; $i++ ) {
			$factory->post->create(
				array(
					'post_type'   => 'revision',
					'post_status' => 'inherit',
					'post_parent' => self::$with_file,
					'post_name'   => self::$with_file . '-revision-v1',
				)
			);
		}

		self::$without_file = $factory->post->create(
			array(
				'post_type'   => 'document',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Behave as the documents list screen.
	 */
	public function set_up() {
		parent::set_up();
		set_current_screen( 'edit-document' );
		wp_cache_delete( self::$with_file, 'document_revisions' );
	}

	/**
	 * Reset the screen.
	 */
	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Render a column cell.
	 *
	 * @param string $column column name.
	 * @param int    $doc    document ID.
	 * @return string
	 */
	private function cell( $column, $doc ) {
		global $wpdr;
		ob_start();
		$wpdr->admin->file_columns_cb( $column, $doc );
		return (string) ob_get_clean();
	}

	/**
	 * Run a documents query and return the IDs of the two test documents, in order.
	 *
	 * @param array<string, mixed> $args WP_Query args.
	 * @return int[]
	 */
	private function ids( array $args ) {
		$query = new WP_Query(
			array_merge(
				array(
					'post_type'      => 'document',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post__in'       => array( self::$with_file, self::$without_file ),
				),
				$args
			)
		);
		return array_map( 'intval', $query->posts );
	}

	/**
	 * The columns are added before the date and are sortable.
	 */
	public function test_columns_registered() {
		global $wpdr;

		$columns = $wpdr->admin->add_file_columns(
			array(
				'cb'    => '',
				'title' => 'Title',
				'date'  => 'Date',
			)
		);
		self::assertSame( array( 'cb', 'title', 'wpdr_file', 'wpdr_revisions', 'date' ), array_keys( $columns ) );

		$sortable = $wpdr->admin->file_sortable_columns( array() );
		self::assertSame( 'wpdr_revisions', $sortable['wpdr_revisions'] );
		self::assertSame( 'wpdr_file', $sortable['wpdr_file'] );
	}

	/**
	 * Cell output.
	 */
	public function test_cells() {
		self::assertSame( '3', $this->cell( 'wpdr_revisions', self::$with_file ), 'Two test revisions plus the one from the upload.' );
		self::assertStringStartsWith( 'TXT, ', $this->cell( 'wpdr_file', self::$with_file ) );
		self::assertStringContainsString( 'Missing', $this->cell( 'wpdr_file', self::$without_file ) );
	}

	/**
	 * Sorting by revisions and by file type.
	 */
	public function test_sorting() {
		self::assertSame(
			array( self::$with_file, self::$without_file ),
			$this->ids(
				array(
					'orderby' => 'wpdr_revisions',
					'order'   => 'DESC',
				)
			)
		);
		self::assertSame(
			array( self::$without_file, self::$with_file ),
			$this->ids(
				array(
					'orderby' => 'wpdr_revisions',
					'order'   => 'ASC',
				)
			)
		);
		self::assertSame(
			array( self::$without_file, self::$with_file ),
			$this->ids(
				array(
					'orderby' => 'wpdr_file',
					'order'   => 'ASC',
				)
			),
			'No file sorts before text/plain.'
		);
	}

	/**
	 * The "Missing file" filter.
	 */
	public function test_missing_file_filter() {
		self::assertSame( array( self::$without_file ), $this->ids( array( 'wpdr_file' => 'missing' ) ) );
	}
}
