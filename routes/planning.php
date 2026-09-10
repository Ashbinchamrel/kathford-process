<?php
use App\Http\Controllers\Planning\{PlanningController,TaskController};
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','can:planning.view'])->prefix('planning')->name('planning.')->group(function(){
 Route::get('/',[PlanningController::class,'index'])->name('index');
 Route::get('/setup',[PlanningController::class,'setup'])->name('setup');
 Route::post('/periods',[PlanningController::class,'period'])->name('periods.store');
 Route::get('/support',[PlanningController::class,'support'])->name('support');
 Route::post('/support/{support}',[PlanningController::class,'respond'])->name('support.respond');
 Route::get('/tasks',[TaskController::class,'index'])->name('tasks');
 Route::post('/tasks',[TaskController::class,'store'])->name('tasks.store');
 Route::put('/tasks/{task}',[TaskController::class,'update'])->name('tasks.update');
 Route::post('/sprints',[TaskController::class,'sprint'])->name('sprints.store');
 Route::post('/sprints/{sprint}',[TaskController::class,'sprintAction'])->name('sprints.action');
 Route::get('/documents/create',[PlanningController::class,'create'])->name('create');
 Route::post('/documents',[PlanningController::class,'store'])->name('store');
 Route::get('/documents/{document}',[PlanningController::class,'show'])->name('show');
 Route::get('/documents/{document}/edit',[PlanningController::class,'edit'])->name('edit');
 Route::put('/documents/{document}',[PlanningController::class,'update'])->name('update');
 Route::post('/documents/{document}/action',[PlanningController::class,'action'])->name('action');
});
