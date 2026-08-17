import js from '@eslint/js'
import babelParser from '@babel/eslint-parser'
import reactHooks from 'eslint-plugin-react-hooks'
import { reactRefresh } from 'eslint-plugin-react-refresh'
import globals from 'globals'
import eslintConfigPrettier from 'eslint-config-prettier/flat'
import { defineConfig } from 'eslint/config'

export default defineConfig([
  {
    ignores: ['node_modules/**', 'dist/**', 'api/vendor/**'],
  },

  js.configs.recommended,

  {
    files: ['**/*.{js,jsx,ts,tsx}'],

    languageOptions: {
      parser: babelParser,
      ecmaVersion: 'latest',
      sourceType: 'module',

      globals: {
        ...globals.browser,
      },

      parserOptions: {
        requireConfigFile: false,

        babelOptions: {
          presets: ['@babel/preset-typescript', ['@babel/preset-react', { runtime: 'automatic' }]],
        },
      },
    },
  },

  {
    files: ['**/*.{ts,tsx}'],

    rules: {
      // TypeScript compiler가 담당
      'no-undef': 'off',
      'no-unused-vars': 'off',
      'no-redeclare': 'off',

      'no-var': 'error',
      'prefer-const': 'error',
      eqeqeq: ['error', 'always'],
    },
  },

  {
    files: ['src/**/*.{js,jsx,ts,tsx}'],
    extends: [reactHooks.configs.flat.recommended],
  },

  reactRefresh.configs.vite(),

  {
    files: ['eslint.config.{js,mjs}', 'vite.config.{js,ts,mjs,mts}', 'scripts/**/*.{js,mjs}'],

    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
  },

  eslintConfigPrettier,
])
