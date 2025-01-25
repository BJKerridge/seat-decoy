<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;
use BJK\Decoy\Seat\Http\Controllers\CombatController;
use BJK\Decoy\Seat\Http\Controllers\CombatUserController;
use BJK\Decoy\Seat\Http\Controllers\FleetController;
use BJK\Decoy\Seat\Http\Controllers\DecoyController;

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/fuel', [FuelController::class, 'getFuel'])
        ->name('decoy::decoyFuel');
});

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/combat', [CombatController::class, 'getCombat'])
        ->name('decoy::decoyCombat');
});

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/combat/{id}', [CombatUserController::class, 'getCombatUser'])
        ->name('decoy::decoyCombatUser');
});

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/fleets', [FleetController::class, 'getFleets'])->name('decoy::decoyFleets');
    Route::post('/fleets', [FleetController::class, 'store'])->name('decoy::storeFleet');
    Route::post('/fleet/update', [FleetController::class, 'update'])->name('decoy::updateFleet');
    Route::delete('/fleets/{id}', [FleetController::class, 'destroy'])->name('decoy::fleetDestroy');
});

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/decoyhome', [DecoyController::class, 'getNewHome'])->name('decoy::decoyHome');
    Route::post('/updateOrder', [DecoyController::class, 'updateOrder'])->name('decoy::updateOrder');
    Route::post('/updateFilter', [DecoyController::class, 'updateFilter'])->name('decoy::updateFilter');
});