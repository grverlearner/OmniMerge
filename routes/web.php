<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Entities\EntityController;
use App\Http\Controllers\EntityTypes\EntityTypeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get(
        '/dashboard',
        DashboardController::class
    )->name('dashboard');

    Route::resource(
        'entity-types',
        EntityTypeController::class
    );

    Route::resource(
        'entities',
        EntityController::class
    );

    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');
});

require __DIR__.'/auth.php';