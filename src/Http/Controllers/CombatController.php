<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace BJK\Decoy\Seat\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Seat\Eveapi\Jobs\Killmails\Detail;
use Seat\Eveapi\Models\Alliances\AllianceMember;
use Seat\Eveapi\Models\Corporation\CorporationMember;
use Seat\Eveapi\Models\Killmails\Killmail;
use Seat\Eveapi\Models\Killmails\KillmailAttacker;
use Seat\Eveapi\Models\RefreshToken;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;


/**
 * Class HomeController.
 *
 * @package BJK\Decoy\Seat\Http\Controllers
 */
class CombatController extends Controller
{
     /**
     * @return \Illuminate\View\View
     */
    public function index(){return view('decoy::decoyCombat');}

    public function getCombat(){

    /* ===== CREATE THE TABLES
     ================================================== */

        $combat_tracker_table = 'decoy_combat_tracker';
        if (!Schema::hasTable($combat_tracker_table)) {
         Schema::create($combat_tracker_table, function (Blueprint $table) {
             $table->id();
             $table->unsignedBigInteger('alliance_id')->unique();
             $table->string('alliance_name');
             $table->integer('killmails')->default(0);
             $table->timestamps();
         });
        $message = "Table $combat_tracker_table created successfully.";} else {
        $message = "Table $combat_tracker_table already exists!";
        }

        $combat_users_table = 'decoy_combat_users';
        if (!Schema::hasTable($combat_users_table)) {
            Schema::create($combat_users_table, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('main_character_id');
                $table->json('associated_character_ids');
                $table->json('killmails')->default(json_encode([]));
                $table->timestamp('users_updated_at')->nullable();
                $table->timestamp('kills_updated_at')->nullable();
                $table->timestamps();
            });
            $message2 = "Table $combat_users_table created successfully.";} else {
            $message2 = "Table $combat_users_table already exists!";
        }

        $combat_users_zkill_table = 'decoy_combat_users_zkill';
        if (!Schema::hasTable($combat_users_zkill_table)) {
            Schema::create($combat_users_zkill_table, function (Blueprint $table) {
                $table->id();
                $table->timestamp('updated_at')->nullable();
            });
            $message2 = "Table $combat_users_zkill_table created successfully.";} else {
            $message2 = "Table $combat_users_zkill_table already exists!";
        }

    /* ==== POPULATE THE TABLES
     ================================================== */

        $allianceList = [99005338, 99003581, 1354830081, 1727758877, 1900696668, 99003214, 99011223, 1042504553, 99009927, 99009163, 99012042, 1411711376, 99011162, 99007203, 99006941, 99002685, 99012982, 99003995, 99007887, 1988009451, 99011416, 99001317, 99012328, 498125261, 386292982, 99001954, 99001969, 99012410, 99007722, 99010877, 99011312, 741557221, 150097440, 1220922756, 99012770, 99010281, 917526329, 99002003, 99010735, 99011990, 99007629, 99011279, 99011983, 99009758, 154104258, 99011268, 99013537, 99012813, 99009977, 99011181, 99013231, 99010389, 99008684, 1614483120, 99012617, 99011852, 99013095, 99008697, 99012485, 99013444, 99000285, 99010517, 99011720, 99008245, 99006751, 922190997, 99012279, 99013590, 99010339, 99012786];
        function importZKillData($allianceList) {
            $addedCount = 0;
            $updatedCount = 0;
            foreach ($allianceList as $allianceID) {
                $allianceRecord = DB::table('decoy_combat_tracker')->where('alliance_id', $allianceID)->first();
                if ($allianceRecord && Carbon::parse($allianceRecord->updated_at)->gt(Carbon::now()->subHours(6))) {continue;}
                $data = Http::get("https://zkillboard.com/api/stats/allianceID/{$allianceID}/")->json();
                if (isset($data['info']['id'])) {
                    $allianceName = $data['info']['name'];
                    $killCount = $data['activepvp']['kills']['count'] ?? 0;
                    if (!$allianceRecord) {
                        DB::table('decoy_combat_tracker')->insert([
                            'alliance_id' => $allianceID,
                            'alliance_name' => $allianceName,
                            'killmails' => $killCount,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        $addedCount++;
                    } else {
                        DB::table('decoy_combat_tracker')
                            ->where('alliance_id', $allianceID)
                            ->update([
                                'killmails' => $killCount,
                                'alliance_name' => $allianceName, // Update alliance name
                                'updated_at' => Carbon::now(),
                            ]);
                        $updatedCount++;
                    }
                }
            }  
        $message = "{$addedCount} alliances added, {$updatedCount} records updated.";
        return $message;
        }
        $message = importZKillData($allianceList);

    /* ====GET TOP {x} KILLERS OVER THE LAST {y} DAYS
     ================================================== */

        $recentUserUpdate = DB::table('decoy_combat_users')->max('users_updated_at');
        if (!$recentUserUpdate || Carbon::parse($recentUserUpdate)->lt(Carbon::now()->subHours(1))) {
            $corpIds = AllianceMember::where('alliance_id', 99012410)->pluck('corporation_id');
            $corpPilots = CorporationMember::whereIn('corporation_id', $corpIds)->pluck('character_id');
            $characterList = User::whereIn('main_character_id', $corpPilots)->get();
            $userIds = $characterList->pluck('id');
        
            foreach ($characterList as $user) {
                $associatedCharacterIds = RefreshToken::where('user_id', $user->id)->withTrashed()->pluck('character_id');
        
                // Convert collections to arrays before using array_intersect()
                $filteredCharacters = array_values(array_intersect($associatedCharacterIds->toArray(), $corpPilots->toArray()));

        
                DB::table('decoy_combat_users')->insert([
                    'name' => $user->name,
                    'main_character_id' => $user->main_character_id,
                    'associated_character_ids' => json_encode($filteredCharacters),
                    'users_updated_at' => Carbon::now(),
                ]);
            }
        }

        $toUpdate = DB::table('decoy_combat_users')->whereNull('kills_updated_at')->orWhere('kills_updated_at', '<', Carbon::now()->subMinutes(20))->pluck('main_character_id')->toArray();
        
        foreach ($toUpdate as $mainCharacterId) {
            $user = DB::table('decoy_combat_users')->where('main_character_id', $mainCharacterId)->first();
            $associatedCharacterIds = json_decode($user->associated_character_ids, true);
            $killmailData = DB::table('killmail_attackers')
                ->join('killmail_details', 'killmail_attackers.killmail_id', '=', 'killmail_details.killmail_id')
                ->whereIn('killmail_attackers.character_id', $associatedCharacterIds)
                ->where('killmail_details.killmail_time', '>=', Carbon::now()->subDays(30)) // Filter by killmail_time in last 30 days
                ->select('killmail_attackers.killmail_id')
                ->distinct()
                ->get();
            $distinctKillmailCount = $killmailData->count();
            DB::table('decoy_combat_users')
                ->where('main_character_id', $mainCharacterId)
                ->update([
                    'killmails' => $killmailData,
                    'kills_updated_at' => Carbon::now(),
                ]);
        }

        $formattedCharacterList = DB::table('decoy_combat_users')->select('main_character_id', 'name', DB::raw('JSON_LENGTH(killmails) as killmails'))->orderByDesc(DB::raw('JSON_LENGTH(killmails)'))->limit(20)->get()->toArray();

    /* ==== GET THE FRIENDLY/NEUTRAL/HOSTILE PAGE
     ================================================== */

        $allianceFriendly = [99005338, 1727758877, 1042504553, 99012410, 386292982, 99011983, 99011720, 99011279];
        $allianceNeutral = [1411711376, 99003581, 99007203, 99012982];
        $allianceHostile = [1900696668, 1354830081, 99011223, 99003214, 99009927, 99009163, 99012042, 99011162];
        
        function getKillmailLedger($allianceList) {
            return collect($allianceList)->map(function ($allianceID) {
                $data = DB::table('decoy_combat_tracker')->where('alliance_id', $allianceID)->first();
                return (object) [
                    'alliance_id' => $data->alliance_id,
                    'name' => $data->alliance_name,
                    'count' => $data->killmails,
                ];
            })->sortByDesc('count')->values();
        }     
        $killmailLedgerFriendly = getKillmailLedger($allianceFriendly);
        $killmailLedgerNeutral = getKillmailLedger($allianceNeutral);
        $killmailLedgerHostile = getKillmailLedger($allianceHostile);


    /* ==================================================
            GET LAST 20 ZKILL PAGES AND ADD TO DATABASE
     ================================================== */
        
     $updatedAt = DB::table('decoy_combat_users_zkill')->value('updated_at');
     if (!$updatedAt || Carbon::parse($updatedAt)->lt(Carbon::now()->subMinutes(60))) {
         $killmailDataNew = [];
         $addedCount = $existingCount = 0;     
         foreach (range(1, 2) as $page) {
             $response = Http::get("https://zkillboard.com/api/kills/allianceID/99012410/page/{$page}/");
             if (!$response->successful()) {
                 $message = "Failed to fetch killmails from page {$page}.";
                 break;
             }
             foreach ($response->json() as $killmail) {
                $killmailModel = Killmail::firstOrCreate(
                     ['killmail_id' => $killmail['killmail_id']],
                     ['killmail_hash' => $killmail['zkb']['hash']]
                );
                 $killmailModel->wasRecentlyCreated ? $addedCount++ : $existingCount++;
                 $killmailDataNew[] = [
                     'killmail_id' => $killmail['killmail_id'],
                     'killmail_hash' => $killmail['zkb']['hash'],
                 ];
             }
             $message = "{$page} pages loaded! {$addedCount} killmails added, {$existingCount} killmails already existed.";
         }
         DB::table('decoy_combat_users_zkill')->updateOrInsert(
             ['id' => 1],
             ['updated_at' => Carbon::now()]
         );
     }else{
        $message = "Timer is too recent";
     }
     

     /* ==================================================
            CHECK HOW MANY KILLMAILS DO NOT HAVE IDS
     ================================================== */
        
        // $killmails = Killmail::whereDoesntHave('detail')->get();
        // $message = $killmails->isNotEmpty()
        //     ? "There are {$killmails->count()} killmails that require details."
        //     : "There are no killmails requiring details.";
        //  if ($killmails->isNotEmpty()) {
        //  //        Bus::batch($killmails->map(fn($killmail) => new Detail($killmail->killmail_id, $killmail->killmail_hash)))->name('Process Killmail Details')->dispatch();
        //  }

    /* ==================================================
            RETURN THE VIEW
     ================================================== */

        return view('decoy::decoyCombat', compact('killmailLedgerFriendly', 'killmailLedgerHostile', 'killmailLedgerNeutral', 'formattedCharacterList', 'message'))->render();
    }
}
