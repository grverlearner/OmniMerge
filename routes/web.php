<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Entities\EntityController;
use App\Http\Controllers\EntityTypes\EntityTypeController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Attributes\AttributeController;
use App\Http\Controllers\Attributes\AttributeGroupController;
use App\Http\Controllers\Attributes\AttributeOptionController;
use App\Http\Controllers\Entities\EntityAttributeController;

use App\Http\Controllers\Collections\CollectionController;

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

    Route::resource(
        'attributes',
        AttributeController::class
    );

    Route::resource(
        'attribute-groups',
        AttributeGroupController::class
    );

    Route::post(
        'attributes/{attribute}/options',
        [AttributeOptionController::class, 'store']
    )->name('attributes.options.store');

    Route::delete(
        'attributes/{attribute}/options/{option}',
        [AttributeOptionController::class, 'destroy']
    )->name('attributes.options.destroy');

    Route::get(
        'entities/{entity}/attributes',
        [EntityAttributeController::class, 'edit']
    )->name('entities.attributes.edit');

    Route::put(
        'entities/{entity}/attributes',
        [EntityAttributeController::class, 'update']
    )->name('entities.attributes.update');

    Route::resource(
        'collections',
        CollectionController::class
    );

    Route::get(
        'attribute-options',
        [AttributeOptionController::class, 'index']
    )->name('attribute-options.index');

    Route::get(
        'attribute-options/create',
        [AttributeOptionController::class, 'create']
    )->name('attribute-options.create');

    Route::get(
        'attribute-options/{attributeOption}',
        [AttributeOptionController::class, 'show']
    )->name('attribute-options.show');

    Route::get(
        'attribute-options/{attributeOption}/edit',
        [AttributeOptionController::class, 'edit']
    )->name('attribute-options.edit');

    Route::post(
        'attributes/{attribute}/options',
        [AttributeOptionController::class, 'store']
    )->name('attributes.options.store');

    Route::put(
        'attributes/{attribute}/options/{option}',
        [AttributeOptionController::class, 'update']
    )->name('attributes.options.update');

    Route::delete(
        'attribute-options/{attributeOption}',
        [AttributeOptionController::class, 'destroy']
    )->name('attribute-options.destroy');
});

require __DIR__ . '/auth.php';
