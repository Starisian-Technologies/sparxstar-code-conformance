// @starisian/eslint-config — Starisian Technologies shared ESLint flat config
// Requires: eslint@^9, @typescript-eslint/eslint-plugin@^8, @typescript-eslint/parser@^8

import tseslint from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';

/** @type {import('eslint').Linter.FlatConfig[]} */
export default [
  {
    ignores: ['**/vendor/**', '**/node_modules/**', '**/dist/**', '**/build/**'],
  },
  {
    files: ['**/*.{js,jsx,mjs,cjs}'],
    rules: {
      'no-console': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-var': 'error',
      'prefer-const': 'error',
      eqeqeq: ['error', 'always'],
      // STD: JS-002 — wrap fetch in AbortController with 5s timeout; enforce via custom rule (not yet written)
    },
  },
  {
    files: ['**/*.{ts,tsx}'],
    plugins: { '@typescript-eslint': tseslint },
    languageOptions: { parser: tsParser },
    rules: {
      'no-console': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-var': 'error',
      'prefer-const': 'error',
      eqeqeq: ['error', 'always'],
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-floating-promises': 'error',
      '@typescript-eslint/await-thenable': 'error',
      // STD: JS-002 — wrap fetch in AbortController with 5s timeout; enforce via custom rule (not yet written)
    },
  },
];
