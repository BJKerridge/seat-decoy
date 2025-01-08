<?php

Route::group([
    'namespace' => 'Seat\Web\Http\Controllers',
    'middleware' => 'web',   // Web middleware for state etc since L5.3
], function () {

    Route::group([
        // 'namespace' => 'Stock',
        // 'prefix' => 'stock',
    ], function () {

        include __DIR__ . '/Routes/Fuel.php';
    });
});