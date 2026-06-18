<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events-visual-2')->name('home');

// JSON data sources for the two visual pages.
Route::get('events/cards', [EventController::class, 'cards'])->name('events.cards');
Route::get('events/clusters', [EventController::class, 'clusters'])->name('events.clusters');
Route::get('events/filters', [EventController::class, 'filters'])->name('events.filters');
Route::get('events/random', [EventController::class, 'random'])->name('events.random');

// The two event-visual pages.
Route::inertia('events-visual-1', 'Events/VisualOne')->name('events.visual1');
Route::inertia('events-visual-2', 'Events/VisualTwo')->name('events.visual2');

Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
Route::post('events/{event}/attendees', [AttendeeController::class, 'store'])->name('events.attendees.store');

// No dashboard page — the post-login redirect target for Fortify just lands on
// the discover grid.
Route::redirect('dashboard', '/events-visual-2')->name('dashboard');

require __DIR__.'/settings.php';
