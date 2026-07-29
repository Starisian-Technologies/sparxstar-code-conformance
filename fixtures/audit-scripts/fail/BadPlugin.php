<?php
// Intentionally missing declare(strict_types=1) — triggers PHP-001 (strict_types check).
// Contains isset() call — triggers PHP-006 (no-isset audit).
// Contains SELECT * query — triggers PHP-002 (no-select-star audit).
// Contains dbDelta() call — triggers PHP-007 (no-dbdelta audit).
// No @governed-mutation gate call — triggers AUTH-001 warning.

namespace FixturePlugin;

class BadPlugin {

	public function get_all_posts() {
		global $wpdb;

		// SELECT * violation (PHP-002 / PHP-SQL-002).
		$results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->posts} WHERE post_status = 'publish'"
		);

		// isset() violation (PHP-006).
		if ( isset( $results ) ) {
			return $results;
		}

		return [];
	}

	public function run_migration() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$wpdb->prefix}example (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			PRIMARY KEY  (id)
		) {$charset_collate};";
		// dbDelta() violation (PHP-007).
		dbDelta( $sql );
	}
}
