# Centered Task Dialog Implementation Plan

## Objective

Move the existing create and edit task form from a right-side sheet into a centered shadcn dialog. Keep all data handling and backend requests unchanged.

## Files

- Modify `resources/js/features/todo/components/TaskFormSheet.vue`.
- Use the existing components exported from `resources/js/components/ui/dialog/index.js`.
- Do not modify backend files or the parent component contract.

## Steps

1. Replace the `Sheet` imports and template wrapper with `Dialog` components.
2. Configure `DialogContent` with a 760-pixel maximum width, an 85-viewport-height limit, hidden outer overflow, and three grid rows.
3. Keep the header in the first row and the action footer in the last row.
4. Place the existing form in the middle row with vertical scrolling and unchanged field bindings, validation messages, and reminder controls.
5. Keep the current submit function, request routes, payload, processing state, emitted events, and close behavior.
6. Build the frontend and run the automated tests.
7. Verify create, edit, close, scroll, and submit behavior in a desktop browser. Check the console for errors.

## Acceptance Criteria

- Create and edit task forms open at the center of the desktop viewport.
- The dialog does not exceed 760 pixels in width or 85 viewport height.
- Long form content scrolls without moving the header or footer.
- Existing task operations and validation behave as before.
- No backend file changes.
