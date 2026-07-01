<?php

declare(strict_types=1);

/**
 * Advanced Custom Fields (ACF) PHPStan stubs.
 *
 * Provides type definitions for ACF functions so PHPStan can analyze projects
 * that use ACF without ACF being loaded in the analysis environment.
 *
 * These stubs cover the most common ACF API surface. They are intentionally
 * minimal — only types that are necessary for PHPStan to resolve return types
 * and parameter types correctly. Stubs do not contain implementation.
 *
 * Ref: config/phpstan/phpstan-wordpress.neon (stubFiles entry).
 */

// @phpstan-ignore-file — stub file; implementations intentionally missing.

if ( ! function_exists( 'get_field' ) ) {
    /**
     * Get a custom field value.
     *
     * @param string          $selector   Field name or key.
     * @param int|string|bool $post_id    Post ID, option page, etc.
     * @param bool            $format_value Apply formatting.
     * @return mixed
     */
    function get_field( string $selector, int|string|bool $post_id = false, bool $format_value = true ): mixed
    {
        // Stub — implementation provided by ACF plugin at runtime.
    }
}

if ( ! function_exists( 'the_field' ) ) {
    /**
     * Echo a custom field value.
     *
     * @param string          $selector Field name or key.
     * @param int|string|bool $post_id  Post ID, option page, etc.
     */
    function the_field( string $selector, int|string|bool $post_id = false ): void
    {
        // Stub.
    }
}

if ( ! function_exists( 'get_fields' ) ) {
    /**
     * Get all field values for a post.
     *
     * @param int|string|bool $post_id Post ID or option page identifier.
     * @param bool            $format_value Apply formatting.
     * @return array<string, mixed>|false
     */
    function get_fields( int|string|bool $post_id = false, bool $format_value = true ): array|false
    {
        // Stub.
    }
}

if ( ! function_exists( 'have_rows' ) ) {
    /**
     * Check if a repeater / flexible content field has rows.
     *
     * @param string          $selector Field name or key.
     * @param int|string|bool $post_id  Post ID.
     */
    function have_rows( string $selector, int|string|bool $post_id = false ): bool
    {
        // Stub.
    }
}

if ( ! function_exists( 'the_row' ) ) {
    /**
     * Move to the next row in a repeater / flexible content loop.
     */
    function the_row(): void
    {
        // Stub.
    }
}

if ( ! function_exists( 'get_row_index' ) ) {
    /**
     * Get the current row index in a repeater / flexible content loop.
     */
    function get_row_index(): int
    {
        // Stub.
    }
}

if ( ! function_exists( 'get_sub_field' ) ) {
    /**
     * Get a sub-field value inside a repeater or flexible content row.
     *
     * @param string $selector   Sub-field name or key.
     * @param bool   $format_value Apply formatting.
     * @return mixed
     */
    function get_sub_field( string $selector, bool $format_value = true ): mixed
    {
        // Stub.
    }
}

if ( ! function_exists( 'acf_add_options_page' ) ) {
    /**
     * Register an ACF options page.
     *
     * @param array<string, mixed> $args Options page configuration.
     * @return array<string, mixed>|false
     */
    function acf_add_options_page( array $args = [] ): array|false
    {
        // Stub.
    }
}

if ( ! function_exists( 'acf_register_block_type' ) ) {
    /**
     * Register an ACF block type.
     *
     * @param array<string, mixed> $settings Block settings.
     */
    function acf_register_block_type( array $settings ): void
    {
        // Stub.
    }
}

if ( ! function_exists( 'update_field' ) ) {
    /**
     * Update a custom field value.
     *
     * @param string          $selector Field name or key.
     * @param mixed           $value    New value.
     * @param int|string|bool $post_id  Post ID.
     */
    function update_field( string $selector, mixed $value, int|string|bool $post_id = false ): bool
    {
        // Stub.
    }
}
