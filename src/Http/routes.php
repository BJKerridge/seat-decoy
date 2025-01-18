<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;
use BJK\Decoy\Seat\Http\Controllers\CombatController;

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

