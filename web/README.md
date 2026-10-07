# Disrupt Cyprus — web (PWA)

React 19 + Vite 8 + TypeScript (strict) + Tailwind 4. Conventions, commands and decisions: see the root `CLAUDE.md`.

```bash
podman compose up -d node                      # dev server on http://localhost:5173
podman compose run --rm -T node npm test       # Vitest + Testing Library + MSW
podman compose run --rm -T node npm run build  # tokens → typecheck → production build + service worker
```

- `src/styles/tokens.json` → `npm run tokens` → `src/styles/theme.css` (generated)
- `src/i18n/{el,en}.json` hold every UI string
- `/dev/components` shows every component (development builds only)
