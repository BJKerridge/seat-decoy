<?php

use BJK\Decoy\Seat\Http\Controllers\FuelController;
use BJK\Decoy\Seat\Http\Controllers\CombatController;
use BJK\Decoy\Seat\Http\Controllers\CombatUserController;
use BJK\Decoy\Seat\Http\Controllers\FleetController;
use BJK\Decoy\Seat\Http\Controllers\DecoyController;
use BJK\Decoy\Seat\Http\Controllers\MarketController;
use BJK\Decoy\Seat\Http\Controllers\MumbleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Seat\Web\Models\User;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Web\Models\Squads\SquadMember;

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

Route::group(['middleware' => ['web', 'auth']], function () {
    // Original Mumble page
    Route::get('/mumble', [MumbleController::class, 'getMumble'])
        ->name('decoy::decoyMumble');

    // Proxy POST to Python backend
    Route::post('/mumble-proxy', function (Request $request) {
        // Validate input (only password comes from client)
        $data = $request->validate([
            'pass' => 'required|string|min:8|max:128',
        ]);

        // Get authenticated user
        $user = Auth::user();
        $ticker = Alliance::find($user?->main_character?->affiliation?->alliance_id)?->ticker;
        $characterName = $user?->main_character?->name ?? 'Unknown';
        $fcs = SquadMember::where('squad_id', 10)->with('user')->get()->pluck('user.name')->filter()->values()->all();

        if (in_array($characterName, $fcs)) {
            $role = 'FC';
        } elseif ($ticker === 'DECOY') {
            $role = 'DECOY';
        } else {
            $role = '';
        }

        // Server-generated username
        $username = "[{$ticker}] {$characterName}";
        $password = $data['pass'];

        // Call Python backend internally (Docker network)
        $response = Http::timeout(5)->post('http://mumble-backend:5000/login', [
            'u' => $username,
            'pass' => $password,
            'role' => $role
        ]);

        return response()->json([
            'status' => $response->successful() ? 'ok' : 'error',
            'message' => $response->body(),
        ], $response->status());
    })->middleware('throttle:10,1'); // rate-limit per user
});