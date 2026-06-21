<?php

declare(strict_types=1);

namespace ExamplePlugin;

/**
 * Example class demonstrating valid PHP for STD-TOOLCHAIN-001 pass fixture.
 *
 * @package ExamplePlugin
 */
class Example {

    /**
     * Get posts by user with a bounded query.
     *
     * @param int $user_id The user ID.
     * @param int $limit   Maximum number of results.
     * @return array<int, array<string, mixed>>
     */
    public function get_user_posts( int $user_id, int $limit = 10 ): array {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, post_title, post_date FROM {$wpdb->prefix}posts
                 WHERE post_author = %d AND post_status = 'publish'
                 LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        return $results ?? [];
    }
}
