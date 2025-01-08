<?php

// Fuel
Route::get('/fuel')
    ->name('seatcore::decoyFuel')
    ->uses('FuelController@getFuel');