# Status Dates and Pinned Sticky Notes Implementation Plan

## Objective

Store a date for each active Todo status, show that date on Board cards, and
replace sticky-note conversion with persistent pinning and manual ordering.
Preserve legacy conversion data and leave unrelated backend behavior intact.

## Phase 1: Database and Todo status domain

1. Add a migration for nullable Todo status timestamps and sticky-note pin
   metadata, including the approved backfill and indexes.
2. Extend the Todo and StickyNote casts and fillable fields.
3. Replace `manual_reminder_at` with `status_at` in the status Form Request,
   controller, and domain action.
4. Apply target-specific validation, timestamp reset rules, deadline reminder
   synchronization, and detailed Activity Log changes in one transaction.
5. Expose status timestamps as ISO-8601 values in the Inertia Todo payload.

## Phase 2: Sticky-note pin domain

1. Add Form Requests for pin reorder validation.
2. Add transactional actions to toggle a pin and reorder all pinned notes in a
   workspace.
3. Add controller methods and routes for toggle and reorder.
4. Remove the conversion route and controller method while retaining legacy
   columns and records.
5. Query pinned notes first by `pin_order`, followed by ordinary notes from
   newest to oldest.

## Phase 3: Automated backend tests

1. Replace conversion coverage with pin, unpin, compaction, reorder,
   authorization, invalid-list, and legacy-data preservation tests.
2. Expand Todo status tests for timestamps, backward resets, same-status date
   corrections, validation, reminders, and Activity Log changes.
3. Extend page-data tests for serialized timestamps and pinned-note ordering.
4. Run focused tests before continuing to the frontend.

## Phase 4: Frontend

1. Add SortableJS to frontend dependencies.
2. Update the status detail form with a dynamic date label, target-specific
   defaults, validation output, and the new `status_at` payload.
3. Change Board quick status selection to open the detail dialog with the
   target status preselected.
4. Show Deadline, Mulai, or Selesai dates on Board cards according to their
   current status.
5. Replace sticky-note conversion controls and dialog with Pin/PinOff actions,
   a pinned group, a GripVertical handle, and persistent SortableJS ordering.
6. Restore the prior pinned order and show a toast when a reorder request
   fails.

## Phase 5: Documentation and verification

1. Update the backend handoff to describe status timestamps, pinning, routes,
   tests, and verification results.
2. Run the full PHP test suite, Pint, route inspection, migration inspection,
   and the Vite production build.
3. Run desktop browser QA for status date changes, Board labels, pin/unpin, and
   drag-and-drop. Check the browser console for errors.

## Acceptance Criteria

- Every status save records the approved date field and reset behavior.
- Board cards label and display the date for their current status.
- Status forms never treat their date input as a reminder.
- Users can pin multiple sticky notes and reorder only pinned notes.
- Pin order persists across refreshes and rejects invalid cross-workspace data.
- Legacy conversion data remains stored but conversion is absent from the UI
  and active routes.
- Full tests, formatting, frontend build, and desktop browser QA pass.
