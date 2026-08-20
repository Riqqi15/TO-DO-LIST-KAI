<?php

use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Reminder\ReminderController;
use App\Http\Controllers\Todo\TodoController;
use App\Http\Controllers\Todo\TodoPageController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Workspace\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'todo.index' : 'login'));

Route::get('/app', [TodoPageController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('todo.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->middleware('throttle:10,1')->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password.update');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::post('/teams/join', [TeamController::class, 'join'])->middleware('throttle:10,1')->name('teams.join');
    Route::post('/workspaces/{workspace}/invite', [TeamController::class, 'generateInvite'])->middleware('throttle:10,1')->name('teams.invite');
    Route::patch('/workspaces/{workspace}/capacity', [TeamController::class, 'capacity'])->name('teams.capacity');
    Route::patch('/workspaces/{workspace}/ownership', [TeamController::class, 'transfer'])->name('teams.transfer');
    Route::delete('/workspaces/{workspace}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');
    Route::delete('/workspaces/{workspace}/leave', [TeamController::class, 'leave'])->name('teams.leave');
    Route::delete('/workspaces/{workspace}', [TeamController::class, 'destroy'])->name('teams.destroy');

    Route::post('/workspaces/{workspace}/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/workspaces/{workspace}/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::get('/workspaces/{workspace}/calendar', [TodoPageController::class, 'calendar'])->name('todos.calendar');
    Route::get('/todos/{todo}', [TodoPageController::class, 'show'])->name('todos.show');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::patch('/todos/{todo}/status', [TodoController::class, 'status'])->name('todos.status');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/{todo}/reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

    Route::post('/todos/{todo}/notes', [\App\Http\Controllers\Todo\TodoNoteController::class, 'store'])->name('todos.notes.store');
    Route::delete('/notes/{note}', [\App\Http\Controllers\Todo\TodoNoteController::class, 'destroy'])->name('todos.notes.destroy');

    // Route::post('/workspaces/{workspace}/sticky-notes', [StickyNoteController::class, 'store'])->name('sticky-notes.store');
    // Route::patch('/sticky-notes/{note}', [StickyNoteController::class, 'update'])->name('sticky-notes.update');
    // Route::patch('/sticky-notes/{note}/pin', [StickyNoteController::class, 'togglePin'])->name('sticky-notes.pin');
    // Route::patch('/workspaces/{workspace}/sticky-notes/reorder', [StickyNoteController::class, 'reorder'])->name('sticky-notes.reorder');
    // Route::delete('/sticky-notes/{note}', [StickyNoteController::class, 'destroy'])->name('sticky-notes.destroy');
});
