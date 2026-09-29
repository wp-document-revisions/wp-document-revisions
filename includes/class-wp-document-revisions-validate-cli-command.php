<?php
/**
 * WP-CLI command: validate document structures.
 *
 * `wp document-revisions validate [--fix] [--format=<format>]`
 *
 * Runs the same checks as the Validate Structure screen across every document
 * and optionally applies the same fixes, so large libraries can be checked and
 * repaired from the command line or a cron job.
 *
 * @since 5.6.0
 * @package WP_Document_Revisions
 */

// direct file access protection.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates, and optionally fixes, document structures.
 */
class WP_Document_Revisions_Validate_CLI_Command {

	/**
	 * Checks every document's internal structure and permalink, like Documents › Validate Structure.
	 *
	 * ## OPTIONS
	 *
	 * [--fix]
	 * : Apply the automatic fix for each problem that has one. Run again afterwards:
	 * fixing one problem can reveal another.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp document-revisions validate
	 *     wp document-revisions validate --fix
	 *     wp document-revisions validate --format=json
	 *
	 * @param string[]             $args       positional arguments (unused).
	 * @param array<string, mixed> $assoc_args named arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		$fix    = ! empty( $assoc_args['fix'] );
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';

		$rows = self::run( $fix );

		if ( empty( $rows ) ) {
			WP_CLI::success( 'No document structure problems found.' );
			return;
		}

		$fields = array( 'id', 'title', 'code', 'severity', 'problem', 'fixable' );
		if ( $fix ) {
			$fields[] = 'result';
		}
		WP_CLI\Utils\format_items( $format, $rows, $fields );

		if ( $fix ) {
			$failed = count(
				array_filter(
					$rows,
					static function ( $row ) {
						return 0 === strpos( (string) $row['result'], 'error' );
					}
				)
			);
			if ( $failed ) {
				WP_CLI::warning( sprintf( '%d fix(es) failed.', $failed ) );
			}
			WP_CLI::log( 'Run the command again to check for problems revealed by the fixes.' );
		}
	}

	/**
	 * Finds (and optionally fixes) problems. Separate from __invoke() so it can be tested
	 * without WP-CLI.
	 *
	 * @param bool $fix whether to apply the available fixes.
	 * @return array<int, array<string, mixed>> one row per problem.
	 */
	public static function run( bool $fix = false ): array {
		$rows = array();
		foreach ( WP_Document_Revisions_Validate_Structure::find_problems( false ) as $problem ) {
			$code    = (int) $problem['code'];
			$fixable = (bool) $problem['fix'] && in_array( $code, WP_Document_Revisions_Validate_Structure::FIXABLE_CODES, true );
			$row     = array(
				'id'       => (int) $problem['ID'],
				'title'    => (string) $problem['post_title'],
				'code'     => $code,
				'severity' => $problem['error'] ? 'error' : 'warning',
				'problem'  => wp_strip_all_tags( (string) $problem['msg'] ),
				'fixable'  => $fixable ? 'yes' : 'no',
			);

			if ( $fix ) {
				if ( ! $fixable ) {
					$row['result'] = 'skipped';
				} else {
					$result        = WP_Document_Revisions_Validate_Structure::fix_problem( $row['id'], $code, (int) $problem['parm'] );
					$row['result'] = is_wp_error( $result ) ? 'error: ' . $result->get_error_message() : 'fixed';
				}
			}

			$rows[] = $row;
		}

		return $rows;
	}
}
