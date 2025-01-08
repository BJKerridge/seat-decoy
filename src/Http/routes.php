<?php

Route::group([
    'namespace' => 'BJK\Decoy\Http\Controllers',
    'middleware' => 'web',
], function () {
    Route::group([], function () {
        include __DIR__ . '/Routes/Fuel.php';
    });
});