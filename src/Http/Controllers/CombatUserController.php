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
class CombatUserController extends Controller
{
     /**
     * @return \Illuminate\View\View
     */
    public function index(){return view('decoy::decoyCombatUser');}
    public function getCombatUser($id){
        $id = (int) $id; //Check for validation
        $user = DB::table('decoy_combat_users')->where('main_character_id', $id)->first();
        $main = DB::table('decoy_combat_users')->whereRaw('JSON_CONTAINS(associated_character_ids, ?)', [json_encode([$id])])->pluck('main_character_id')->first();
        $total_kill_value = 0;
        $message2 = 0;
        if (!$user) {
            if ($main !== null){
                return redirect()->route('decoy::decoyCombatUser', ['id' => $main]);
                            }else{
                 abort(404, 'User not found.');
            }
        }    
        $killmailIds = json_decode($user->killmails, true); // Assuming it's a JSON array of killmail_ids
        usort($killmailIds, function ($a, $b) {
            return $b['killmail_id'] <=> $a['killmail_id']; // Descending order
        });
        $killmailDatas = [124126821, 124126795, 124126825];

        foreach ($killmailIds as $killmailId) {
            $victimData = DB::table('killmail_victims')
                ->where('killmail_id', $killmailId)
                ->select('killmail_id', 'ship_type_id', 'character_id', 'corporation_id', 'alliance_id')
                ->first();

            $detailsData = DB::table('killmail_details')
                ->where('killmail_id', $killmailId)
                ->select('killmail_time', 'solar_system_id')
                ->first();

            $attackerData = DB::table('killmail_attackers')
                ->where('killmail_id', $killmailId)
                ->pluck('character_id') // This will return an array of attacker character IDs
                ->toArray();

            $shipValue = DB::table('market_prices')->where('type_id', $victimData->ship_type_id)->value('adjusted_price');
            $shipContentsValue = DB::table('killmail_victim_items')
            ->join('market_prices', 'killmail_victim_items.item_type_id', '=', 'market_prices.type_id')
            ->where('killmail_victim_items.killmail_id', $victimData->killmail_id)
            ->selectRaw('SUM((COALESCE(killmail_victim_items.quantity_destroyed, 0) + COALESCE(killmail_victim_items.quantity_dropped, 0)) * market_prices.adjusted_price) as total_value')
            ->value('total_value'); // Fetch the single summed value
            $killValue = $shipValue + $shipContentsValue + 10000;
            $total_kill_value = $total_kill_value + $killValue;

            $associatedCharacterIds = json_decode($user->associated_character_ids, true);
            $victimName = DB::table('character_infos')->where('character_id', $victimData->character_id)->value('name');
            $value = DB::table('killmail_victim_items')->where('killmail_id', $killmailId)->select('item_type_id', 'quantity_destroyed', 'quantity_dropped')->get();
            $matchingAttackers = array_intersect($attackerData, $associatedCharacterIds);
            $solarSystem = DB::table('solar_systems')->where('system_id', $detailsData->solar_system_id)->select('name', 'security')->first();
            $attackersWithNames = [];
            foreach($matchingAttackers as $attackerId){
                $attackerName = DB::table('character_infos')->where('character_id', $attackerId)->value('name');
                $attackersWithNames[] = [
                    'attacker_id' => $attackerId,
                    'attacker_name' => $attackerName
                ];
            }
        
            $killmailData[] = [
                'killmail_id' => $victimData->killmail_id,
                'ship_type_id' => $victimData->ship_type_id,
                'character_id' => $victimData->character_id,
                'victim_name' => $victimName,
                'kill_value' => number_format($killValue, 0),
                'corporation_id' => $victimData->corporation_id,
                'alliance_id' => $victimData->alliance_id,
                'killmail_time' => $detailsData->killmail_time,
                'killmail_location' => $solarSystem->name,
                'killmail_location_sec' => number_format($solarSystem->security, 2),
                'attacker_ids' => $attackersWithNames
            ];

        } 

        $total_kill_value = number_format($total_kill_value, 0);



    /* ==================================================
            RETURN THE VIEW
     ================================================== */

        return view('decoy::decoyCombatUser', compact('user', 'id', 'message2', 'killmailData', 'total_kill_value'))->render();
    }
}
