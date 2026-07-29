<?php
/**
 * Tests for MCP exposure of the WordPress Abilities API abilities.
 *
 * Tier 0 of the AI-agent roadmap: the three read-only abilities
 * (`check-document-access`, `get-document-info`, `get-document-revisions`)
 * must be exposed to AI agents over the Model Context Protocol via the
 * WordPress MCP Adapter, while the mutating `override-document-lock`
 * ability must NOT be, keeping the agent-facing surface read-only.
 *
 * The MCP Adapter decides exposure from the ability's `meta` using
 * (WordPress/mcp-adapter, McpAbilityExposure):
 *
 *     isset( $meta['mcp']['public'] )
 *         ? (bool) $meta['mcp']['public']
 *         : ( true === ( $meta['public'] ?? false ) );
 *
 * so `meta.mcp.public` is the authoritative per-ability switch and the
 * default (neither key set) is "not exposed". These tests lock in the
 * flag values so a future change to `meta.public` — or an accidental
 * flip of a flag — can't silently widen or narrow the agent surface.
 *
 * NOTE ON FILE NAME: the `zz-` prefix forces this class to run last. The
 * WPDR suite has ordering-dependent tests (hence the sibling `z-last`
 * class); a new test class inserted earlier shifts that order and breaks
 * unrelated attachment/revision-count assertions. Running last keeps this
 * self-contained, read-only test from disturbing them.
 *
 * @package WP_Document_Revisions
 */

/**
 * Tests for the abilities' MCP exposure meta.
 */
class Test_WP_Document_Revisions_Zz_Abilities_MCP extends Test_Common_WPDR {

	/**
	 * Abilities that must be exposed over MCP (read-only surface).
	 *
	 * @var string[]
	 */
	private static $mcp_public = array(
		'wp-document-revisions/check-document-access',
		'wp-document-revisions/get-document-info',
		'wp-document-revisions/get-document-revisions',
	);

	/**
	 * Abilities that must NOT be exposed over MCP (mutating).
	 *
	 * @var string[]
	 */
	private static $mcp_private = array(
		'wp-document-revisions/override-document-lock',
	);

	/**
	 * Fetch a registered ability or skip when the Abilities API is
	 * unavailable (WordPress < 6.9).
	 *
	 * @param string $name Fully-qualified ability name.
	 * @return WP_Ability
	 */
	private function get_ability_or_skip( string $name ) {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available on this WordPress version.' );
		}

		$ability = wp_get_ability( $name );
		$this->assertNotNull( $ability, "Ability {$name} should be registered." );

		return $ability;
	}

	/**
	 * Resolve MCP exposure exactly as WordPress/mcp-adapter does.
	 *
	 * @param array<string, mixed> $meta Ability meta.
	 * @return bool Whether the adapter would expose the ability over MCP.
	 */
	private function resolve_mcp_exposure( array $meta ): bool {
		if ( isset( $meta['mcp']['public'] ) ) {
			return (bool) $meta['mcp']['public'];
		}

		return true === ( $meta['public'] ?? false );
	}

	/**
	 * The read-only abilities must be marked public to MCP.
	 */
	public function test_read_abilities_are_mcp_public() {
		foreach ( self::$mcp_public as $name ) {
			$meta = $this->get_ability_or_skip( $name )->get_meta();

			$this->assertTrue(
				isset( $meta['mcp']['public'] ) && true === $meta['mcp']['public'],
				"Read ability {$name} must set meta.mcp.public = true."
			);
			$this->assertTrue(
				$this->resolve_mcp_exposure( $meta ),
				"Read ability {$name} must resolve as exposed by the MCP Adapter."
			);
		}
	}

	/**
	 * The mutating ability must be explicitly opted out of MCP, so a
	 * future broad `meta.public` opt-in cannot expose it.
	 */
	public function test_mutating_abilities_are_not_mcp_public() {
		foreach ( self::$mcp_private as $name ) {
			$meta = $this->get_ability_or_skip( $name )->get_meta();

			$this->assertTrue(
				isset( $meta['mcp']['public'] ) && false === $meta['mcp']['public'],
				"Mutating ability {$name} must set meta.mcp.public = false."
			);
			$this->assertFalse(
				$this->resolve_mcp_exposure( $meta ),
				"Mutating ability {$name} must resolve as NOT exposed by the MCP Adapter."
			);
		}
	}

	/**
	 * All abilities remain discoverable over the REST API regardless of
	 * their MCP exposure (the MCP flag must not have dropped show_in_rest).
	 */
	public function test_abilities_still_show_in_rest() {
		foreach ( array_merge( self::$mcp_public, self::$mcp_private ) as $name ) {
			$meta = $this->get_ability_or_skip( $name )->get_meta();

			$this->assertTrue(
				isset( $meta['show_in_rest'] ) && true === $meta['show_in_rest'],
				"Ability {$name} must keep meta.show_in_rest = true."
			);
		}
	}
}
