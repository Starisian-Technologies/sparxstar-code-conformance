<?php

declare(strict_types=1);

/**
 * WordPress extra PHPStan stubs.
 *
 * Provides type definitions for WordPress functions that szepeviktor/phpstan-wordpress
 * does not yet cover, or where the shipped signatures produce false positives at
 * PHPStan level 5+.
 *
 * Stubs do not contain implementation — they exist only to inform PHPStan's
 * type inference. Keep this file minimal; prefer upstreaming fixes to
 * szepeviktor/phpstan-wordpress where possible.
 *
 * Ref: config/phpstan/phpstan-wordpress.neon (stubFiles entry).
 */

// @phpstan-ignore-file — stub file; implementations intentionally missing.

if ( ! function_exists( 'wp_remote_get' ) ) {
    /**
     * Perform an HTTP GET request.
     *
     * @param string               $url  Request URL.
     * @param array<string, mixed> $args Request arguments.
     * @return array<string, mixed>|\WP_Error
     */
    function wp_remote_get( string $url, array $args = [] ): array|\WP_Error
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_remote_post' ) ) {
    /**
     * Perform an HTTP POST request.
     *
     * @param string               $url  Request URL.
     * @param array<string, mixed> $args Request arguments.
     * @return array<string, mixed>|\WP_Error
     */
    function wp_remote_post( string $url, array $args = [] ): array|\WP_Error
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    /**
     * Retrieve the body from a remote response.
     *
     * @param array<string, mixed>|\WP_Error $response Remote response.
     */
    function wp_remote_retrieve_body( array|\WP_Error $response ): string
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    /**
     * Retrieve the HTTP status code from a remote response.
     *
     * @param array<string, mixed>|\WP_Error $response Remote response.
     * @return int|string
     */
    function wp_remote_retrieve_response_code( array|\WP_Error $response ): int|string
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    /**
     * Schedule a recurring event.
     *
     * @param int    $timestamp  Unix timestamp of the first occurrence.
     * @param string $recurrence How often the event should subsequently recur.
     * @param string $hook       Action hook to execute when event is run.
     * @param array<int, mixed> $args Optional arguments to pass to the hook.
     */
    function wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = [] ): bool|\WP_Error
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    /**
     * Retrieve the next timestamp for a scheduled event.
     *
     * @param string            $hook Action hook.
     * @param array<int, mixed> $args Optional arguments.
     * @return int|false
     */
    function wp_next_scheduled( string $hook, array $args = [] ): int|false
    {
        // Stub.
    }
}

if ( ! function_exists( 'register_rest_route' ) ) {
    /**
     * Register a REST API route.
     *
     * @param string               $namespace  Route namespace.
     * @param string               $route      Route path.
     * @param array<string, mixed> $args       Route arguments.
     * @param bool                 $override   Override an existing route.
     */
    function register_rest_route( string $namespace, string $route, array $args = [], bool $override = false ): bool
    {
        // Stub.
    }
}

if ( ! function_exists( 'wp_set_object_terms' ) ) {
    /**
     * Create term and taxonomy relationships.
     *
     * @param int                  $object_id Object ID.
     * @param string|int|array<int, string|int> $terms Terms to set.
     * @param string               $taxonomy  Taxonomy name.
     * @param bool                 $append    Whether to append or replace.
     * @return array<int, int>|false|\WP_Error
     */
    function wp_set_object_terms( int $object_id, string|int|array $terms, string $taxonomy, bool $append = false ): array|false|\WP_Error
    {
        // Stub.
    }
}
