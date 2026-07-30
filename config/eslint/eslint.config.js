/**
 * Starisian Technologies — ESLint config for consuming WordPress/JS projects.
 * Ref: docs/javascript-react-standard.md, CI-Enforcement-Matrix.md JS-001 – JS-003.
 *
 * Usage in a consuming project's eslint.config.js:
 *
 *   import starisian from '@starisian-technologies/eslint-config';
 *   export default [
 *     ...starisian,
 *     {
 *       // project-specific overrides
 *     },
 *   ];
 *
 * This file is the canonical consuming-project configuration entry point.
 * It re-exports the base config from the eslint-config package with WordPress
 * globals added for WordPress-coupled JavaScript.
 *
 * DUAL-INSTALL REQUIRED: this file ships inside the Composer package
 * `starisian-technologies/coding-standards` but imports two npm packages
 * (`@starisian-technologies/eslint-config` and `globals`). A repo installing
 * only via Composer will have this file physically present but unable to
 * resolve either import. You must also:
 *   pnpm add -D @starisian-technologies/eslint-config globals
 * before this config can run.
 *
 * This file resolves `@starisian-technologies/eslint-config` — the package
 * under packages/eslint-config/ in this repo, not the separate, differently
 * namespaced `@starisian/eslint-config` at the repo root. That naming split
 * is an open question on this platform, not a choice this file should be
 * read as having settled.
 */

import starisian from '@starisian-technologies/eslint-config';
import globals from 'globals';

export default [
  ...starisian,

  // WordPress global variables (wp, jQuery, ajaxurl, etc.)
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        wp: 'readonly',
        wpApiSettings: 'readonly',
        ajaxurl: 'readonly',
        pagenow: 'readonly',
        typenow: 'readonly',
        userSettings: 'readonly',
        adminpage: 'readonly',
        thousandsSeparator: 'readonly',
        decimalPoint: 'readonly',
        isRtl: 'readonly',
        // jQuery is available in WordPress but using it is discouraged for new code.
        jQuery: 'readonly',
      },
    },
    rules: {
      // JS bundle size is enforced by the full-quality-gate workflow (VITE-001 / build gate).
      // No direct provider SDK imports without the abstraction layer (SYS-007).
      // Enforce no localStorage for critical offline data — use IndexedDB (JS-002 / DIST-005).
      'no-restricted-globals': [
        'error',
        {
          name: 'localStorage',
          message: 'Use IndexedDB for critical offline data (DIST-005). localStorage is not offline-safe.',
        },
        {
          name: 'sessionStorage',
          message: 'Do not use sessionStorage for persistent state. Use IndexedDB (DIST-005).',
        },
      ],
      'no-restricted-properties': [
        'error',
        {
          object: 'window',
          property: 'localStorage',
          message: 'Use IndexedDB for critical offline data (DIST-005). localStorage is not offline-safe.',
        },
        {
          object: 'window',
          property: 'sessionStorage',
          message: 'Do not use sessionStorage for persistent state. Use IndexedDB (DIST-005).',
        },
        {
          object: 'globalThis',
          property: 'localStorage',
          message: 'Use IndexedDB for critical offline data (DIST-005). localStorage is not offline-safe.',
        },
        {
          object: 'globalThis',
          property: 'sessionStorage',
          message: 'Do not use sessionStorage for persistent state. Use IndexedDB (DIST-005).',
        },
      ],
      // Disallow alert/confirm/prompt in WordPress plugin code.
      'no-alert': 'error',
    },
  },
];
