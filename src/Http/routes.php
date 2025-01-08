<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;

// Fuel
Route::get('/fuel')
    ->name('seatcore::decoyFuel')
    ->uses('FuelController@getFuel');