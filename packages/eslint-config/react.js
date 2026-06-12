// Starisian Technologies — React + browser ESLint flat config.
// Extends the base config with React, React Hooks, and JSX a11y rules.

import jsxA11y from 'eslint-plugin-jsx-a11y';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

import base from './index.js';

export default [
  ...base,
  {
    files: ['**/*.{js,jsx,ts,tsx}'],
    languageOptions: {
      globals: {
        ...globals.browser,
      },
      parserOptions: {
        ecmaFeatures: { jsx: true },
      },
    },
    plugins: {
      react,
      'react-hooks': reactHooks,
      'jsx-a11y': jsxA11y,
    },
    settings: {
      react: { version: 'detect' },
    },
    rules: {
      ...react.configs.recommended.rules,
      ...reactHooks.configs.recommended.rules,
      ...jsxA11y.configs.recommended.rules,

      // Modern React no longer needs explicit React in scope.
      'react/react-in-jsx-scope': 'off',
      'react/jsx-uses-react': 'off',

      // Catalog Principle #4 (Accessible by default).
      'jsx-a11y/alt-text': 'error',
      'jsx-a11y/anchor-is-valid': 'error',
      'jsx-a11y/click-events-have-key-events': 'error',
      'jsx-a11y/no-static-element-interactions': 'error',

      // Backs DIST-005 / JS-002 (localStorage not for critical data — surfaces
      // raw usage so reviewers can confirm it's not vault-grade).
      'no-restricted-globals': ['warn', {
        name: 'localStorage',
        message: 'Use IndexedDB for any data with a TTL or persistence requirement (DIST-005 / JS-002).',
      }],
    },
  },
];
