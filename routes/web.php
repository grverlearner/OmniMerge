<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Hub\HubController;

use App\Http\Controllers\Entities\EntityController;
use App\Http\Controllers\EntityTypes\EntityTypeController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Attributes\AttributeController;
use App\Http\Controllers\Attributes\AttributeGroupController;
use App\Http\Controllers\Attributes\AttributeOptionController;
use App\Http\Controllers\Entities\EntityAttributeController;

use App\Http\Controllers\Collections\CollectionController;
use App\Http\Controllers\Community\ExploreController;
use App\Http\Controllers\Community\CreatorController;

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get(
        '/hub',
        HubController::class
    )->name('hub');

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

    Route::prefix('explore')
        ->name('community.')
        ->group(function () {
            Route::get(
                '/',
                [ExploreController::class, 'index']
            )->name('index');

            Route::get(
                '/creators/{user:username}',
                [
                    CreatorController::class,
                    'show',
                ]
            )->name('creators.show');

            Route::get(
                '/entities/{entity}',
                [ExploreController::class, 'entity']
            )->name('entities.show');

            Route::get(
                '/collections/{collection}',
                [ExploreController::class, 'collection']
            )->name('collections.show');

            Route::get(
                '/attributes/{attribute}',
                [ExploreController::class, 'attribute']
            )->name('attributes.show');

            Route::post(
                '/entities/{entity}/clone',
                [ExploreController::class, 'cloneEntity']
            )->name('entities.clone');

            Route::post(
                '/collections/{collection}/clone',
                [ExploreController::class, 'cloneCollection']
            )->name('collections.clone');

            Route::post(
                '/attributes/{attribute}/clone',
                [ExploreController::class, 'cloneAttribute']
            )->name('attributes.clone');
        });
});

require __DIR__ . '/auth.php';
