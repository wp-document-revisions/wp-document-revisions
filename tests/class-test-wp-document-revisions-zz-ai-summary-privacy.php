<?php
/**
 * Tests that AI summaries respect the extraction opt-out and skip private documents.
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * AI summary opt-out purge and private/password-protected default tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_AI_Summary_Privacy extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Temporary files to remove after each test.
	 *
	 * @var string[]
	 */
	private $files = array();

	/**
	 * Every AI summary meta key.
	 *
	 * @var string[]
	 */
	private static $meta_keys = array(
		WP_Document_Revisions_AI_Summary::META_KEY_TEXT,
		WP_Document_Revisions_AI_Summary::META_KEY_KIND,
		WP_Document_Revisions_AI_Summary::META_KEY_INPUT_HASH,
		WP_Document_Revisions_AI_Summary::META_KEY_GENERATED_AT,
		WP_Document_Revisions_AI_Summary::META_KEY_REVIEWED_BY,
		WP_Document_Revisions_AI_Summary::META_KEY_REVIEWED_AT,
	);

	/**
	 * Create an editor and load the fake extractor.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		require_once __DIR__ . '/class-wpdr-test-counting-text-extractor.php';
		$wpdr->add_caps();
		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Mock the AI provider and register a fake text/plain extractor.
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'wpdr_ai_summary_available', '__return_true' );
		add_filter( 'wpdr_ai_summary_generator', array( $this, 'canned_summary' ) );
		add_filter( 'wpdr_text_extractors', array( $this, 'add_fake_extractor' ) );
	}

	/**
	 * Remove filters, cron events, POST data and temp files.
	 */
	public function tear_down() {
		remove_all_filters( 'wpdr_ai_summary_available' );
		remove_all_filters( 'wpdr_ai_summary_generator' );
		remove_all_filters( 'wpdr_text_extractors' );
		remove_all_filters( 'document_ai_summary_allow_private' );
		_set_cron_array( array() );
		unset( $_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD ], $_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD ] );
		foreach ( $this->files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
		parent::tear_down();
	}

	/**
	 * Injected AI generator.
	 *
	 * @return string
	 */
	public function canned_summary() {
		return 'Canned summary.';
	}

	/**
	 * Put a fake text/plain extractor first in the registry.
	 *
	 * @param array $extractors registered extractors.
	 * @return array
	 */
	public function add_fake_extractor( $extractors ) {
		array_unshift( $extractors, new WPDR_Test_Counting_Text_Extractor( 'text/plain', 'Some document text.' ) );
		return $extractors;
	}

	/**
	 * Create a document with one text/plain revision attachment.
	 *
	 * @param string $status   document post status.
	 * @param string $password document post password.
	 * @return int[] [ document ID, attachment ID ].
	 */
	private function document( $status = 'publish', $password = '' ) {
		$doc = self::factory()->post->create(
			array(
				'post_type'     => 'document',
				'post_status'   => $status,
				'post_password' => $password,
				'post_author'   => self::$editor,
			)
		);
		return array( $doc, $this->attachment( $doc ) );
	}

	/**
	 * Add a text/plain attachment backed by a real temp file to a document.
	 *
	 * @param int $doc document ID.
	 * @return int attachment ID.
	 */
	private function attachment( $doc ) {
		$file = trailingslashit( get_temp_dir() ) . wp_unique_filename( get_temp_dir(), 'wpdr-ai-summary.txt' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, 'Some document text.' );
		$this->files[] = $file;
		$attachment_id = self::factory()->attachment->create_object(
			$file,
			$doc,
			array( 'post_mime_type' => 'text/plain' )
		);
		add_filter(
			'get_attached_file',
			static function ( $path, $id ) use ( $attachment_id, $file ) {
				return $id === $attachment_id ? $file : $path;
			},
			99,
			2
		);
		return $attachment_id;
	}

	/**
	 * Store a summary with review state directly, as a prior cron run would have.
	 *
	 * @param int $attachment_id revision attachment ID.
	 */
	private function seed_summary( $attachment_id ) {
		WP_Document_Revisions_AI_Summary::store( $attachment_id, 'document', 'Stored summary.', 'hash' );
		WP_Document_Revisions_AI_Summary::set_reviewed( $attachment_id, self::$editor );
	}

	/**
	 * Build a summary REST request.
	 *
	 * @param int $doc_id document ID.
	 * @param int $rev_id attachment ID.
	 * @return WP_REST_Request
	 */
	private function request( $doc_id, $rev_id ) {
		$request = new WP_REST_Request( 'GET', '/wpdr/v1/documents/' . $doc_id . '/revisions/' . $rev_id . '/summary' );
		$request->set_param( 'doc_id', $doc_id );
		$request->set_param( 'rev_id', $rev_id );
		return $request;
	}

	/**
	 * Turning on the extraction opt-out deletes every revision's AI summary meta.
	 */
	public function test_opt_out_purges_summary_meta() {
		list( $doc, $first ) = $this->document();
		$second              = $this->attachment( $doc );
		$this->seed_summary( $first );
		$this->seed_summary( $second );
		wp_schedule_single_event( time() + 60, WP_Document_Revisions_AI_Summary::CRON_ACTION, array( $second ) );

		wp_set_current_user( self::$editor );
		$_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_FIELD ] = wp_create_nonce( WP_Document_Revisions_Text_Extraction_Opt_Out::NONCE_ACTION );
		$_POST[ WP_Document_Revisions_Text_Extraction_Opt_Out::FORM_FIELD ]  = '1';
		WP_Document_Revisions_Text_Extraction_Opt_Out::save( $doc, get_post( $doc ) );

		foreach ( array( $first, $second ) as $attachment_id ) {
			foreach ( self::$meta_keys as $key ) {
				self::assertFalse( metadata_exists( 'post', $attachment_id, $key ), "$key should be purged from $attachment_id." );
			}
		}
		self::assertFalse( wp_next_scheduled( WP_Document_Revisions_AI_Summary::CRON_ACTION, array( $second ) ), 'Pending summary event should be unscheduled.' );
	}

	/**
	 * The getter, the REST route and the review action ignore a stored summary on an opted-out document.
	 */
	public function test_getter_empty_for_opted_out_document() {
		list( $doc, $attachment ) = $this->document();
		$this->seed_summary( $attachment );
		self::assertIsArray( WP_Document_Revisions_AI_Summary::get( $attachment ) );

		update_post_meta( $doc, WP_Document_Revisions_Text_Extraction_Opt_Out::META_KEY, '1' );

		self::assertNull( WP_Document_Revisions_AI_Summary::get( $attachment ) );
		self::assertFalse( WP_Document_Revisions_AI_Summary::set_reviewed( $attachment, self::$editor ) );
		$response = WP_Document_Revisions_AI_Summary_REST::get_summary( $this->request( $doc, $attachment ) );
		self::assertSame( 'unavailable', $response->get_data()['status'] );
		self::assertArrayNotHasKey( 'summary', $response->get_data() );
	}

	/**
	 * Published documents are still summarised and served.
	 */
	public function test_published_document_summarised() {
		list( $doc, $attachment ) = $this->document();

		WP_Document_Revisions_AI_Summary::maybe_schedule( $attachment );
		self::assertIsInt( wp_next_scheduled( WP_Document_Revisions_AI_Summary::CRON_ACTION, array( $attachment ) ) );

		WP_Document_Revisions_AI_Summary::run( $attachment );
		$stored = WP_Document_Revisions_AI_Summary::get( $attachment );
		self::assertIsArray( $stored );
		self::assertSame( 'Canned summary.', $stored['text'] );

		$response = WP_Document_Revisions_AI_Summary_REST::get_summary( $this->request( $doc, $attachment ) );
		self::assertSame( 'ready', $response->get_data()['status'] );
	}

	/**
	 * Private documents aren't sent to the AI provider by default.
	 */
	public function test_private_document_not_summarised_by_default() {
		$this->assert_not_summarised( $this->document( 'private' ) );
	}

	/**
	 * Password-protected documents aren't sent to the AI provider by default.
	 */
	public function test_password_protected_document_not_summarised_by_default() {
		$this->assert_not_summarised( $this->document( 'publish', 'secret' ) );
	}

	/**
	 * The filter can opt private documents back in, and receives the document.
	 */
	public function test_filter_allows_private_document() {
		list( $doc, $attachment ) = $this->document( 'private' );
		$seen                     = null;
		add_filter(
			'document_ai_summary_allow_private',
			static function ( $allow, $document ) use ( &$seen ) {
				$seen = $document;
				return true;
			},
			10,
			2
		);

		WP_Document_Revisions_AI_Summary::run( $attachment );

		$stored = WP_Document_Revisions_AI_Summary::get( $attachment );
		self::assertIsArray( $stored );
		self::assertSame( 'Canned summary.', $stored['text'] );
		self::assertInstanceOf( WP_Post::class, $seen );
		self::assertSame( $doc, $seen->ID );
	}

	/**
	 * The filter can opt password-protected documents back in.
	 */
	public function test_filter_allows_password_protected_document() {
		list( , $attachment ) = $this->document( 'publish', 'secret' );
		add_filter( 'document_ai_summary_allow_private', '__return_true' );

		WP_Document_Revisions_AI_Summary::run( $attachment );

		self::assertIsArray( WP_Document_Revisions_AI_Summary::get( $attachment ) );
	}

	/**
	 * A summary stored before a document was made private isn't returned.
	 */
	public function test_existing_summary_hidden_once_private() {
		list( $doc, $attachment ) = $this->document();
		$this->seed_summary( $attachment );

		wp_update_post(
			array(
				'ID'          => $doc,
				'post_status' => 'private',
			)
		);

		self::assertNull( WP_Document_Revisions_AI_Summary::get( $attachment ) );
	}

	/**
	 * Assert a document's revision is neither scheduled, generated nor served.
	 *
	 * @param int[] $ids [ document ID, attachment ID ].
	 */
	private function assert_not_summarised( $ids ) {
		list( $doc, $attachment ) = $ids;
		$prompts                  = 0;
		add_filter(
			'wpdr_ai_summary_generator',
			static function ( $result ) use ( &$prompts ) {
				++$prompts;
				return $result;
			},
			5
		);

		WP_Document_Revisions_AI_Summary::maybe_schedule( $attachment );
		self::assertFalse( wp_next_scheduled( WP_Document_Revisions_AI_Summary::CRON_ACTION, array( $attachment ) ), 'Should not be scheduled.' );

		WP_Document_Revisions_AI_Summary::run( $attachment );
		self::assertSame( 0, $prompts, 'The AI provider should not be called.' );
		self::assertFalse( metadata_exists( 'post', $attachment, WP_Document_Revisions_AI_Summary::META_KEY_KIND ) );

		// A summary stored by some other path still isn't served.
		WP_Document_Revisions_AI_Summary::store( $attachment, 'document', 'Stored summary.', 'hash' );
		self::assertNull( WP_Document_Revisions_AI_Summary::get( $attachment ) );
		$response = WP_Document_Revisions_AI_Summary_REST::get_summary( $this->request( $doc, $attachment ) );
		self::assertSame( 'unavailable', $response->get_data()['status'] );
	}
}
