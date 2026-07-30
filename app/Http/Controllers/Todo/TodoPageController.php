<?php

namespace App\Http\Controllers\Todo;

use App\Domain\ActivityLog\Models\ActivityLog;
use App\Domain\Category\Models\Category;
use App\Domain\StickyNote\Models\StickyNote;
use App\Domain\Todo\Models\Todo;
use App\Domain\Workspace\Models\Workspace;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoPageController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaces = Workspace::query()
            ->whereHas('membershipRows', fn ($query) => $query->where('user_id', $request->user()->id))
            ->withCount('membershipRows')
            ->orderBy('type')->orderBy('name')->get();

        $workspace = $request->filled('workspace')
            ? $workspaces->firstWhere('id', $request->integer('workspace'))
            : $workspaces->first();
        abort_if($request->filled('workspace') && ! $workspace, 403);

        $categories = collect();
        $todos = collect();
        $notes = collect();
        $activities = collect();
        if ($workspace) {
            $categories = Category::where('is_system', true)->orWhere('workspace_id', $workspace->id)->orderByDesc('is_system')->orderBy('name')->get();
            $todosQuery = Todo::where('workspace_id', $workspace->id)->with(['category:id,name,slug,is_system', 'creator:id,name', 'reminders'])->orderBy('deadline_at');
            if ($request->filled('status')) {
                $todosQuery->where('status', $request->string('status')->toString());
            }
            if ($request->filled('category')) {
                $todosQuery->where('category_id', $request->integer('category'));
            }
            $todos = $todosQuery->get()->map(fn (Todo $todo) => $this->todoPayload($todo));
            $notes = StickyNote::where('workspace_id', $workspace->id)->with('creator:id,name')->latest()->get();
            $activities = ActivityLog::where('workspace_id', $workspace->id)->with('actor:id,name')->latest('created_at')->limit(100)->get();
        }

        return Inertia::render('Todo/Index', [
            'workspaces' => $workspaces,
            'activeWorkspace' => $workspace,
            'categories' => $categories,
            'todos' => $todos,
            'stickyNotes' => $notes,
            'activities' => $activities,
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function calendar(Request $request, Workspace $workspace): JsonResponse
    {
        abort_unless($workspace->hasMember($request->user()), 403);
        $validated = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = $workspace->todos()->with('category:id,name');
        if (isset($validated['from'])) {
            $query->where('deadline_at', '>=', $validated['from']);
        }
        if (isset($validated['to'])) {
            $query->where('deadline_at', '<=', $validated['to']);
        }
        $events = $query->orderBy('deadline_at')->get()->map(fn (Todo $todo) => [
            'id' => $todo->id,
            'title' => $todo->title,
            'status' => $todo->status->value,
            'category' => $todo->category?->name,
            'deadline_at' => $todo->deadline_at->toIso8601String(),
            'deadline_wib' => $todo->deadline_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i'),
        ]);

        return response()->json(['timezone' => 'Asia/Jakarta', 'events' => $events]);
    }

    private function todoPayload(Todo $todo): array
    {
        return [
            ...$todo->toArray(),
            'status' => $todo->status->value,
            'deadline_at' => $todo->deadline_at->toIso8601String(),
            'deadline_wib' => $todo->deadline_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i'),
        ];
    }
}
