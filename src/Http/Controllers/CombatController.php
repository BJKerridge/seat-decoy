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
use Illuminate\Support\Facades\Http;
use Seat\Eveapi\Models\Killmails\Killmail;
use Seat\Web\Http\Controllers\Controller;
use Seat\Eveapi\Models\Industry\CharacterMining;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Eveapi\Models\Killmails\KillmailAttacker;
use Seat\Web\Models\User;
use Seat\Web\Models\Acl\Affiliation;
use Seat\Eveapi\Models\Alliances\AllianceMember;
use Illuminate\View\View;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Api\Http\Resources\UserResource;
use Seat\Eveapi\Models\Corporation\CorporationMember;
use Seat\Eveapi\Models\RefreshToken;
use Illuminate\Support\Facades\Bus;
use Seat\Eveapi\Jobs\Killmails\Detail;


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

        // DECOY Tracker
        $allianceId = 99012410;
        $corpIds = AllianceMember::where('alliance_id', $allianceId)->pluck('corporation_id');
        $corpPilots = CorporationMember::wherein('corporation_id', $corpIds)->pluck('character_id');
        $characterList = User::whereIn('main_character_id', $corpPilots)->get(); // Get users
        $userIds = $characterList->pluck('id')->toArray(); // Get user IDs
        $characterIds = RefreshToken::whereIn('user_id', $userIds)->withTrashed()->pluck('character_id');
        $addedCount = 0;
        $existingCount = 0;
        $killmailData = KillmailAttacker::whereIn('character_id', $characterIds)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select('killmail_id', 'character_id')
            ->get();
        
        $killmailCounts = $killmailData->groupBy('killmail_id');
        
        $formattedCharacterList = [];
        
        foreach ($characterList as $user) {
            $associatedCharacterIds = RefreshToken::where('user_id', $user->id)
                ->withTrashed()
                ->pluck('character_id')
                ->toArray();
            
            $distinctKillmailIds = [];
            $killmailIdsForUser = [];
        
            foreach ($associatedCharacterIds as $characterId) {
                foreach ($killmailCounts as $killmailId => $charactersInKillmail) {
                    if ($charactersInKillmail->contains('character_id', $characterId)) {
                        $distinctKillmailIds[$killmailId] = true;
                        $killmailIdsForUser[$killmailId] = true;
                    }
                }
            }
            $killmailCount = count($distinctKillmailIds);
            $formattedCharacterList[] = [
                'id' => $user->id,
                'main_character_id' => $user->main_character_id,
                'name' => $user->name,
                'associatedIds' => $associatedCharacterIds,
                'killmail_count' => $killmailCount,
                'killmail_ids' => array_keys($killmailIdsForUser), // Extract killmail IDs
            ];
        }
        
        // Sort by killmail_count in descending order and limit to top 20 users
        usort($formattedCharacterList, function ($a, $b) {
            return $b['killmail_count'] <=> $a['killmail_count'];
        });
        
        $formattedCharacterList = array_slice($formattedCharacterList, 0, 20);
        
// $killmailDataNew = [];

// for ($page = 1; $page <= 10; $page++) {
//     $response = Http::get("https://zkillboard.com/api/kills/allianceID/99012410/page/{$page}/");
//     if ($response->successful()) {
//         $killmails = $response->json();
//         foreach ($killmails as $killmail) {
//             $killmail_id = $killmail['killmail_id'];
//             $killmail_hash = $killmail['zkb']['hash'];

//             $killmailModel = Killmail::firstOrCreate(
//                 ['killmail_id' => $killmail_id], // Unique constraint
//                 ['killmail_hash' => $killmail_hash] // Values to insert
//             );

//             if ($killmailModel->wasRecentlyCreated) {
//                 $addedCount++;  // Count if newly created
//             } else {
//                 $existingCount++;  // Count if it already existed
//             }

//             $killmailDataNew[] = [
//                 'killmail_id' => $killmail_id,
//                 'killmail_hash' => $killmail_hash,
//             ];
//         }
//         $message = "{$page} pages loaded! {$addedCount} killmails added, {$existingCount} killmails already existed.";
//     } else {
//         $message = "Failed to fetch killmails from page {$page}.";
//         break;
//     }
// }

// $killmailDataNew = json_encode($killmailDataNew);
        
//         $killmailsCount = Killmail::whereDoesntHave('detail')->count();

// if ($killmailsCount > 0) {
//     // Output the quantity of killmails that require details
//     $message = "There are {$killmailsCount} killmails that require details.";
// } else {
//     $message = "There are no killmails requiring details.";
// }

//25792 at 2025-01-19 00:21

        //$message = "Failed to fetch killmails.";

        //$mainCharacterId = 411225042;

        // Alliance Setup
        $allianceFriendly = [];
        $allianceNeutral = [];
        $allianceHostile = [];
        
        $allianceFriendly = [99005338, 1727758877, 1042504553, 99012410, 386292982, 99011983, 99011720, 99011279];
        $allianceNeutral = [1411711376, 99003581, 99007203, 99012982];
        $allianceHostile = [1900696668, 1354830081, 99011223, 99003214, 99009927, 99009163, 99012042, 99011162];
        


        //$characterIds = auth()->user()->associatedCharacterIds();
        //$characterIds = 0;

        function getKillmailLedger($allianceList) {
            $ledger = [];
        
            foreach ($allianceList as $allianceID) {
                $url = "https://zkillboard.com/api/stats/allianceID/{$allianceID}/";
                $response = Http::get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    $ledger[] = (object) [
                        'alliance_id' => $data['info']['id'] ?? $allianceID,
                        'name' => $data['info']['name'] ?? 'Unknown',
                        'count' => $data['activepvp']['kills']['count'] ?? 0, // PvP Kills in last 30 days
                    ];
                } else {
                    // Handle errors (API down, rate limits, etc.)
                    $ledger[] = [
                        'alliance_id' => $allianceID,
                        'name' => 'Unknown',
                        'count' => 0,
                    ];
                }
            }
        
            return collect($ledger)->sortByDesc('count')->values(); // Convert to Laravel Collection for easy use in views
        }
        
        
        // Fetching Killmail Ledgers using the function
        $killmailLedgerFriendly = getKillmailLedger($allianceFriendly);
        $killmailLedgerNeutral = getKillmailLedger($allianceNeutral);
        $killmailLedgerHostile = getKillmailLedger($allianceHostile);

        return view('decoy::decoyCombat', compact('killmailLedgerFriendly', 'killmailLedgerHostile', 'killmailLedgerNeutral', 'characterList', 'formattedCharacterList'))->render();
    }
}
