<?php

declare(strict_types=1);

namespace ExamplePlugin;

// Triggers PHP-SQL-002 (SELECT *) and PHP-WP-001 (hardcoded wp_ prefix)
class SelectStar {

    public function get_all_posts(): array {
        global $wpdb;

        // Bad: SELECT * — should enumerate columns
        $results = $wpdb->get_results( "SELECT * FROM wp_posts WHERE post_status = 'publish' LIMIT 10" );

        return $results ?? [];
    }
}
