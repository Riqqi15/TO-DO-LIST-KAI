import { TODO_STATUSES } from '@/features/todo/constants/todo-options';

export const byDeadlineWib = (a, b) => (a.deadline_wib || '9999').localeCompare(b.deadline_wib || '9999');

/**
 * Todos keyed by status value, keeping every status present even when empty.
 */
export const groupTodosByStatus = (todos, compare = null) => Object.fromEntries(
    TODO_STATUSES.map(({ value }) => {
        const group = todos.filter((todo) => todo.status === value);

        return [value, compare ? group.sort(compare) : group];
    })
);
