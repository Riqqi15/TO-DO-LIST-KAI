<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'todo.index' : 'login'));

Route::get('/app', fn () => Inertia::render('Todo/Index'))
    ->middleware(['auth', 'verified'])
    ->name('todo.index');
