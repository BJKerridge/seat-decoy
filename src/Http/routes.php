<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;

Route::group([
    'namespace'  => 'BJK\Decoy\Seat\Http\Controllers',
    'middleware' => ['web', 'auth', 'locale'],
], function (): void {

    Route::group([
        'prefix' => 'fuel',
    ], function (): void {

        Route::group([
            'middleware' => 'can:global.superuser',
        ], function (): void {
            Route::get('/', [
                'as' => 'decoy.fuel',
                'uses' => 'FuelController@getFuel'
            ]);
        });
    });
});
