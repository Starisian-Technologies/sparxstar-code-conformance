<?php

declare(strict_types=1);

namespace FixturePlugin;

/**
 * Demonstrates compliant PHP for audit-script self-tests.
 *
 * Covers:
 *   audit-no-isset.php       — no isset() calls
 *   audit-no-select-star.php — no SELECT *; uses explicit column list
 *   audit-no-dbdelta.php     — no dbDelta() call
 *   audit-required-index-files.php — this directory has index.php
 */
class GoodPlugin {

	/**
	 * Retrieve posts by user — compliant query.
	 *
	 * @param int $user_id  User ID.
	 * @param int $limit    Maximum results (bounded).
	 * @return array<int, array<string, mixed>>
	 */
	public function get_user_posts( int $user_id, int $limit = 10 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT post_id, post_title, post_date
				   FROM %i
				  WHERE post_author = %d
				    AND post_status = %s
				  LIMIT %d',
				$wpdb->posts,
				$user_id,
				'publish',
				$limit
			),
			ARRAY_A
		);

		return $results ?? [];
	}

	/**
	 * Check option presence without isset().
	 *
	 * @param string $option_name  Option key.
	 * @return bool
	 */
	public function has_option( string $option_name ): bool {
		$value = get_option( $option_name );
		return $value !== false;
	}
}
