import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import nodePlugin from 'eslint-plugin-n';

export default tseslint.config(
  js.configs.recommended,
  ...tseslint.configs.strict,
  {
    plugins: { n: nodePlugin },
    rules: {
      ...nodePlugin.configs['flat/recommended'].rules,
      'n/no-missing-import': 'error',
    },
  },
);
