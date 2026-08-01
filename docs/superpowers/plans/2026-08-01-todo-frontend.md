# To Do List KAI Frontend Implementation Plan

**Design:** `docs/superpowers/specs/2026-08-01-todo-frontend-design.md`

**Goal:** Replace the current test workspace UI with a professional, responsive shadcn-vue dashboard while preserving every existing backend file and contract.

## Phase 1 — Foundation

1. Add `components.json` for the existing Tailwind 4 and JavaScript setup.
2. Install shadcn-vue runtime dependencies, Lucide icons, Plus Jakarta Sans, and IBM Plex Mono.
3. Add the `cn` helper and shadcn-vue source components required by the dashboard.
4. Replace global CSS with the approved light-only token system, font faces, focus styles, motion rules, and deadline rail utilities.
5. Run `npm run build` and fix foundation errors before feature work.

Expected files:

- `components.json`
- `package.json`
- `package-lock.json`
- `resources/css/app.css`
- `resources/js/lib/utils.js`
- `resources/js/components/ui/**`

## Phase 2 — Shared shell and authentication

1. Build `AppSidebar`, mobile navigation, workspace switcher, header, account menu, and global toast handling.
2. Rebuild `AppLayout.vue` around the command-center shell.
3. Rebuild `AuthLayout.vue` as a calm split layout using the same tokens.
4. Restyle login, registration, forgot password, reset password, and verification pages with shared shadcn-vue fields and buttons.
5. Verify Fortify field names, routes, loading states, and validation messages remain unchanged.

Expected files:

- `resources/js/layouts/AppLayout.vue`
- `resources/js/layouts/AuthLayout.vue`
- `resources/js/components/shared/**`
- `resources/js/Pages/Auth/*.vue`

## Phase 3 — Task workspace

1. Extract workspace data and mutation logic from the monolithic `TodoWorkspace.vue` into a feature composable.
2. Build the workspace header, summary strip, search, category filter, and view switcher.
3. Build Kanban, List, and Calendar views. Keep Kanban as the initial view.
4. Build task cards with the deadline rail, category, creator, deadline, status, and reminder summary.
5. Build task create/edit Sheet and task detail Dialog.
6. Connect create, update, status, delete, filter, and reminder routes through Inertia.
7. Handle near-deadline manual reminder requirements before submit while leaving final validation to Laravel.

Expected files:

- `resources/js/features/todo/components/Task*.vue`
- `resources/js/features/todo/components/Workspace*.vue`
- `resources/js/features/todo/composables/useTodoWorkspace.js`
- `resources/js/features/todo/utils/*.js`
- `resources/js/features/todo/constants/*.js`

## Phase 4 — Notes, activity, categories, and teams

1. Build the sticky-note grid, editor Dialog, color selector, and conversion flow.
2. Build the activity timeline and readable snapshot formatter.
3. Build category management with read-only system categories.
4. Build team creation, join-code, invite-code, capacity, leave, and exact-confirmation deletion flows.
5. Keep transfer ownership and remove-member controls conditional on member payload availability.
6. Add empty, loading, success, validation, forbidden, and destructive states.

Expected files:

- `resources/js/features/todo/components/Sticky*.vue`
- `resources/js/features/todo/components/Activity*.vue`
- `resources/js/features/todo/components/Category*.vue`
- `resources/js/features/todo/components/WorkspaceSettings.vue`

## Phase 5 — Integration and verification

1. Compose the feature panels in `TodoWorkspace.vue` and keep `Todo/Index.vue` thin.
2. Run `npm run build` and `php artisan test`.
3. Confirm `git diff -- app routes database config tests` shows no backend changes.
4. Use the local Docker app for browser QA at desktop and mobile widths.
5. Exercise authentication, workspace switching, all task views, forms, notes, activity, categories, and team settings.
6. Check keyboard focus, labels, dialogs, horizontal Kanban overflow, contrast, and reduced motion.
7. Capture final screenshots and revise the UI after visual critique.
8. Update `docs/ai-handoff/BACKEND_PROGRESS.md` with frontend status and measured verification results only.

## Verification commands

```powershell
npm run build
php artisan test
git diff --check
git diff -- app routes database config tests
docker compose up -d --build
docker compose ps
```

## Change boundary

Do not edit files under `app/`, `routes/`, `database/`, or `config/`. Do not add API endpoints, Inertia props, priority, assignee, or calendar tables. If an existing backend contract cannot support a safe UI, document the limitation and keep the control unavailable.
