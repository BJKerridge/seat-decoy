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


    /* ====GET TOP {x} KILLERS OVER THE LAST {y} DAYS
     ================================================== */

       

        $formattedCharacterList = DB::table('decoy_combat_users')->select('main_character_id', 'name', DB::raw('JSON_LENGTH(killmails) as killmails'))->orderByDesc(DB::raw('JSON_LENGTH(killmails)'))->limit(20)->get()->toArray();

    /* ==== GET THE FRIENDLY/NEUTRAL/HOSTILE PAGE
     ================================================== */

        $allianceFriendly = [99012410, 99012982, 99010468, 431502563, 99012786, 99014203];
        $allianceNeutral = [1354830081, 1900696668, 99009163, 99011223, 99010735, 99012042, 933731581, 99006941, 99009927];
        $allianceHostile = [99003581, 99007203, 498125261, 99002685, 1411711376, 99012770, 154104258, 99013981, 99005866, 99013537];
        
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

        return view('decoy::decoyCombat', compact('killmailLedgerFriendly', 'killmailLedgerHostile', 'killmailLedgerNeutral', 'formattedCharacterList'))->render();
    }
}
