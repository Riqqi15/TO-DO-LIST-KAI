# Status Dates and Pinned Sticky Notes Design

## Goal

Separate status dates from reminders and replace sticky-note conversion with
persistent pinning. Users can record when work starts or finishes, change a
deadline when a task returns to "Belum Dikerjakan", see the relevant date on
the Board, and arrange pinned notes by drag-and-drop.

The application remains a Laravel, Inertia, and Vue modular monolith. This
change touches only the Todo and Sticky Note domains, their Inertia payloads,
and their feature components.

## Todo Status Dates

### Data model

Add nullable `started_at` and `completed_at` columns to `todos`. Keep
`deadline_at` as the deadline. Cast all three values as datetimes in the Todo
model and expose ISO-8601 values in the Inertia payload.

The migration backfills existing data:

- `sedang_dikerjakan` tasks receive `updated_at` as `started_at`;
- `selesai` tasks receive `updated_at` as `completed_at`;
- `belum_dikerjakan` tasks keep their existing deadline and receive no status
  timestamp.

The backfill provides a useful date for existing cards without pretending that
the application captured a more precise historical event.

### Status request

The status endpoint accepts two fields:

- `status`: one of the existing Todo status values;
- `status_at`: the WIB date and time associated with the selected status.

The endpoint no longer accepts `manual_reminder_at`. The controller converts
`status_at` from `Asia/Jakarta` to UTC before calling the domain action.

The same endpoint may update the date while the status remains unchanged. This
supports deadline corrections and timestamp corrections without routing the
user through the general task editor.

### Transition rules

The `ChangeTodoStatus` action applies these rules in one transaction:

| Target status | Date effect | Other effect |
| --- | --- | --- |
| `belum_dikerjakan` | Set `deadline_at = status_at` | Clear `started_at` and `completed_at`; cancel manual reminders at or after the new deadline; rebuild automatic reminders |
| `sedang_dikerjakan` | Set `started_at = status_at` | Clear `completed_at`; keep the deadline |
| `selesai` | Set `completed_at = status_at` | Keep `started_at`; cancel active reminders |

Returning from `selesai` no longer requires a reminder through the status
form. The action rebuilds any automatic reminder that still falls in the
future and reactivates valid future manual reminders. Users add any missing
manual reminder through the existing Reminder section.

Changing the deadline to less than three days away may produce no future
automatic reminder. The deadline change still succeeds because status editing
and reminder creation are separate actions. The Reminder section remains the
only place that creates or deletes a manual reminder.

### Validation

The request and action enforce rules that depend on the target status:

- `belum_dikerjakan`: `status_at` must be at least five minutes in the future;
- `sedang_dikerjakan`: `status_at` cannot be in the future;
- `selesai`: `status_at` cannot be in the future and cannot precede
  `started_at` when a start date exists.

Validation errors attach to `status_at`. Existing Todo policies continue to
authorize status changes.

### Activity log

Each save records `todo.status_changed`. Its changes contain the old and new
status plus the old and new values for `deadline_at`, `started_at`, and
`completed_at`. Recording both values keeps the audit trail meaningful when a
user corrects a date without changing the status.

## Todo User Interface

### Task detail dialog

The status form labels its date input from the target status:

- `Deadline baru` for `belum_dikerjakan`;
- `Tanggal mulai` for `sedang_dikerjakan`;
- `Tanggal selesai` for `selesai`.

Selecting `belum_dikerjakan` fills the input with the current deadline.
Selecting either later status fills it with the current WIB time. The user may
edit either value before saving. The Save button enables when the status or its
date differs from the stored value.

The helper text explains the selected date. It does not mention reminders.
Validation errors appear below the date field.

### Board interaction

Each card shows the date that defines its current column:

- `Deadline` and `deadline_at` in Belum Dikerjakan;
- `Mulai` and `started_at` in Sedang Dikerjakan;
- `Selesai` and `completed_at` in Selesai.

The display uses the existing compact data typography, a Lucide calendar
icon, and an explicit Indonesian label. A missing backfilled timestamp displays
`Belum tercatat` instead of a fabricated date.

Changing a status from a card opens the task detail dialog with the target
status already selected. The user confirms or edits the status date before
saving. This replaces the current immediate patch from the card and guarantees
that every transition supplies `status_at`.

## Pinned Sticky Notes

### Data model

Add two nullable fields to `sticky_notes`:

- `pinned_at`: when the note was pinned;
- `pin_order`: the note's zero-based position among pinned notes in its
  workspace.

Index `(workspace_id, pinned_at, pin_order)` for workspace reads. Cast
`pinned_at` as a datetime. Keep the legacy `converted_to_todo_id` and
`converted_at` columns so existing data remains intact, but stop exposing or
using the conversion feature.

### Pin action

Add a dedicated toggle-pin endpoint and domain action. Any active workspace
member who may update the note may pin or unpin it.

Pinning sets `pinned_at` to the current time and assigns the next `pin_order`
in the workspace. Unpinning clears both fields and compacts the remaining
positions. Both operations run in a transaction and record either
`sticky_note.pinned` or `sticky_note.unpinned` in the Activity Log.

### Reorder action

Add a workspace-scoped reorder endpoint. It accepts `note_ids`, the complete
ordered list of pinned note IDs for that workspace. The request rejects:

- duplicate IDs;
- IDs from another workspace;
- unpinned note IDs;
- incomplete lists that omit a currently pinned note.

The action locks the pinned rows, verifies the submitted set, updates
`pin_order` in a transaction, and records `sticky_note.pins_reordered`.
Workspace membership authorizes the action.

The page query returns pinned notes first by `pin_order`, then ordinary notes
by newest creation time.

### Sticky Notes interface

Remove the "Jadikan task" icon, conversion dialog, and conversion copy. Replace
them with Lucide `Pin` and `PinOff` actions. Pinned notes display a
`Disematkan` badge and appear in a dedicated pinned group above ordinary
notes.

Pinned cards show a `GripVertical` drag handle. Dragging works only from this
handle, so edit, pin, and delete controls retain their normal behavior. The
project adds SortableJS for reliable desktop drag-and-drop. Ordinary notes
cannot be reordered.

After a drop, the frontend applies the new order optimistically and sends the
complete ID list. A failed request restores the previous order and displays an
error toast. The interface disables duplicate reorder requests while one is in
progress.

## Routes and Removed Behavior

Add routes for toggling a note's pin and reordering pinned notes. Remove the
sticky-note conversion route and its controller method from the active
application. The unused database columns remain for data preservation; no
migration drops them.

Remove the conversion action from runtime use and replace its feature tests
with pinning tests. No Todo creation, editing, reminder, calendar, workspace,
or authorization route changes outside the behavior described here.

## Error Handling

Form Request validation returns field-specific Inertia errors. Domain actions
retain policy checks and use database transactions for timestamp, reminder,
pin, order, and Activity Log changes.

The UI keeps the dialog open when a status save fails. A pin toggle failure
leaves the previous badge and order intact. A reorder failure rolls the list
back and tells the user that the order was not saved.

## Testing

Backend feature tests cover:

- each status target and its timestamp;
- same-status date corrections;
- clearing later timestamps when a task moves backward;
- future deadline, future start, future completion, and completion-before-start
  validation;
- automatic and manual reminder handling after deadline or status changes;
- status date fields in the Inertia payload and Activity Log;
- pin, unpin, next position, and position compaction;
- valid reorder plus duplicate, incomplete, cross-workspace, and unpinned IDs;
- member authorization and Activity Log events;
- preservation of legacy sticky-note conversion data.

Frontend verification covers the dynamic status label and defaults, Board date
labels, dialog opening from a card status selection, pin controls, pinned group,
drag handle, optimistic reorder rollback, and empty states.

Run the full PHP test suite, Pint, the Vite production build, and focused browser
QA at the desktop viewport. The project does not target mobile implementation.

## Scope Boundaries

This change does not add a general status-history table, task assignees,
priority, calendar events, or reminder controls to the status form. It does not
drop legacy conversion data or refactor unrelated backend modules.
