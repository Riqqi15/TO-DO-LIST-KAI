# Centered Task Dialog Design

## Goal

Display the create and edit task form as a centered desktop dialog instead of a right-side sheet. Preserve the form's data, validation, reminders, and backend requests.

## Scope

- Replace the shadcn `Sheet` wrapper in `TaskFormSheet.vue` with shadcn `Dialog` components.
- Keep the existing component name and public props and events so `TodoWorkspace.vue` requires no integration change.
- Optimize the dialog for desktop use. Mobile-specific behavior is outside this change.
- Do not change backend routes, request payloads, validation rules, or database code.

## Layout

The dialog sits at the center of the viewport above the existing dimmed overlay. It uses a maximum width of 760 pixels and a maximum height of 85 viewport height.

The dialog contains three regions:

1. A fixed header with the create or edit title, supporting text, and close control.
2. A vertically scrollable form body with the existing title, description, category, deadline, and manual reminder fields.
3. A fixed footer with the secondary **Batal** action and the primary **Buat task** or **Simpan perubahan** action.

Only the form body scrolls when its content exceeds the available height. The header and footer remain visible.

## Behavior and Data Flow

- Opening the dialog continues to reset the form from the selected task or the default values.
- Create requests continue to post to `/workspaces/{workspaceId}/todos`.
- Edit requests continue to send a put request to `/todos/{todoId}`.
- Successful requests close the dialog and emit the existing `saved` event.
- Validation errors remain beside their current fields.
- The dialog closes from the close control, **Batal**, the overlay, or the Escape key.
- While a request is processing, the primary action remains disabled and displays the loading indicator.

## Visual Direction

Reuse the dashboard's current colors, typography, borders, radii, and shadcn components. The centered placement is the only visual emphasis. Do not add decorative elements or change the form copy.

## Verification

- Build the frontend production bundle.
- Run the existing automated test suite.
- Open create and edit flows in a desktop browser.
- Confirm the dialog is centered, stays within 85 viewport height, and scrolls only inside its body.
- Confirm closing, validation, reminder controls, and submit states still work.
- Check the browser console for errors.
