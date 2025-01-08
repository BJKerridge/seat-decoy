<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;

Route::group([
    'middleware' => ['web', 'auth'],
], function (): void {
    Route::get('/fuel', [FuelController::class, 'getFuel'])
        ->name('decoy::decoyFuel');
});