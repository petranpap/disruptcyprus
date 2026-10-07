import js from '@eslint/js'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import globals from 'globals'
import tseslint from 'typescript-eslint'

export default tseslint.config(
  { ignores: ['dist', 'dev-dist', 'coverage', 'src/styles/theme.css'] },
  {
    extends: [js.configs.recommended, ...tseslint.configs.strict],
    files: ['**/*.{ts,tsx}'],
    languageOptions: { ecmaVersion: 2023, globals: globals.browser },
    plugins: { 'react-hooks': reactHooks, 'react-refresh': reactRefresh },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
      '@typescript-eslint/consistent-type-imports': 'error',
    },
  },
  { files: ['src/sw.ts'], languageOptions: { globals: globals.serviceworker } },
  // Route table module, not a component module.
  { files: ['src/app/router.tsx'], rules: { 'react-refresh/only-export-components': 'off' } },
  { files: ['scripts/**/*.mjs', '*.config.{js,ts}'], languageOptions: { globals: globals.node } },
)
