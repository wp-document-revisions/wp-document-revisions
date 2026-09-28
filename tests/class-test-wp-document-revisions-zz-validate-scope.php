<?php
/**
 * Tests where Validate Structure loads and who can use it (#733).
 *
 * @author Ben Balter <ben@balter.com>
 * @package WP_Document_Revisions
 */

/**
 * Validate Structure scope tests.
 *
 * Named with a zz- prefix so it runs after the order-dependent count tests.
 */
class Test_WP_Document_Revisions_Zz_Validate_Scope extends WP_UnitTestCase {

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor;

	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private static $doc;

	/**
	 * Create an editor and a document.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		global $wpdr;
		$wpdr->add_caps();

		self::$editor = $factory->user->create( array( 'role' => 'editor' ) );
		self::$doc    = $factory->post->create(
			array(
				'post_title'  => 'Validate Scope Doc',
				'post_type'   => 'document',
				'post_status' => 'publish',
				'post_author' => self::$editor,
			)
		);
	}

	/**
	 * Reset the script queue and page hook.
	 */
	public function tear_down() {
		wp_dequeue_script( 'wpdr_validate' );
		WP_Document_Revisions_Validate_Structure::$page_hook = '';
		parent::tear_down();
	}

	/**
	 * The script is only enqueued on the Validate Structure screen.
	 */
	public function test_script_only_on_validate_screen() {
		wp_set_current_user( self::$editor );
		WP_Document_Revisions_Validate_Structure::$page_hook = 'document_page_wpdr_validate';

		WP_Document_Revisions_Validate_Structure::enqueue_scripts( 'edit.php' );
		self::assertFalse( wp_script_is( 'wpdr_validate', 'enqueued' ), 'Not on other admin screens.' );

		WP_Document_Revisions_Validate_Structure::enqueue_scripts( 'document_page_wpdr_validate' );
		self::assertTrue( wp_script_is( 'wpdr_validate', 'enqueued' ), 'On the Validate Structure screen.' );
	}

	/**
	 * Without a registered page (the user can't see it), nothing is enqueued.
	 */
	public function test_no_script_without_page() {
		WP_Document_Revisions_Validate_Structure::enqueue_scripts( '' );
		self::assertFalse( wp_script_is( 'wpdr_validate', 'enqueued' ) );
	}

	/**
	 * The capability filter gates the REST fix route.
	 */
	public function test_capability_filter_gates_rest_route() {
		wp_set_current_user( self::$editor );

		$request = new WP_REST_Request( 'PUT', '/wpdr/v1/correct/' . self::$doc . '/type/7/attach/1' );
		$request->set_param( 'id', self::$doc );

		$validate = new WP_Document_Revisions_Validate_Structure();
		self::assertSame( 'edit_documents', WP_Document_Revisions_Validate_Structure::capability() );
		self::assertTrue( $validate->check_permission( $request ), 'Editors can fix by default.' );

		$filter = function () {
			return 'manage_options';
		};
		add_filter( 'document_validate_structure_capability', $filter );
		$allowed = $validate->check_permission( $request );
		remove_filter( 'document_validate_structure_capability', $filter );

		self::assertFalse( $allowed, 'Editors are blocked when the capability is raised.' );
	}
}
