<?php

use App\Http\Controllers\Planning\ImportController;
use App\Http\Controllers\Planning\PlanningController;
use App\Http\Controllers\Planning\TaskController;
use App\Http\Middleware\PlanningValidation;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'can:planning.view', PlanningValidation::class])->prefix('planning')->name('planning.')->group(function () {
    Route::get('/import-template', [ImportController::class, 'template'])->name('import.template');
    Route::post('/documents/{document}/import-preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/documents/{document}/import-confirm', [ImportController::class, 'confirm'])->name('import.confirm');
    Route::post('/governance', [\App\Http\Controllers\Planning\GovernanceController::class, 'save'])->name('governance.save');
    Route::get('/dashboards/{level}', [\App\Http\Controllers\Planning\GovernanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/overview', [PlanningController::class, 'overview'])->name('overview');
    Route::get('/', [PlanningController::class, 'index'])->name('index');
    Route::get('/setup', [PlanningController::class, 'setup'])->name('setup');
    Route::post('/periods', [PlanningController::class, 'period'])->name('periods.store');
    Route::get('/support', [PlanningController::class, 'support'])->name('support');
    Route::post('/support/{support}', [PlanningController::class, 'respond'])->name('support.respond');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/sprints', [TaskController::class, 'sprint'])->name('sprints.store');
    Route::post('/sprints/{sprint}', [TaskController::class, 'sprintAction'])->name('sprints.action');
    Route::get('/documents/create', [PlanningController::class, 'create'])->name('create');
    Route::post('/documents', [PlanningController::class, 'store'])->name('store');
    Route::post('/documents/{document}/amend', [PlanningController::class, 'amend'])->name('amend');
    Route::post('/documents/{document}/items/{item}/checkin', [PlanningController::class, 'checkin'])->name('checkin');
    Route::get('/documents/{document}', [PlanningController::class, 'show'])->name('show');
    Route::get('/documents/{document}/edit', [PlanningController::class, 'edit'])->name('edit');
    Route::put('/documents/{document}', [PlanningController::class, 'update'])->name('update');
    Route::post('/documents/{document}/action', [PlanningController::class, 'action'])->name('action');
});
