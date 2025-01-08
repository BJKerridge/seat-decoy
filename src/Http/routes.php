<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;

Route::get('/fuel', [FuelController::class, 'getFuel'])
    ->name('seatcore::decoyFuel');
