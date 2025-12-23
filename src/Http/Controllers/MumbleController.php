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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Web\Models\Squads\SquadMember;

/**
 * Class MumbleController.
 *
 * @package BJK\Decoy\Seat\Http\Controllers
 */
class MumbleController extends Controller
{
    // Mumble API endpoint
    
    public function getMumble()
    {
        return $this->index();
    }
    
    public function index()
    {
        $user = Auth::user();
        $main = $user->main_character;

        $ticker = null;

        if ($main && ! empty($main->affiliation->alliance_id)) {
            $alliance = Alliance::find($main->affiliation->alliance_id);
            $ticker = $alliance ? $alliance->ticker : null;
        }
        
        // Get character name and ID
        $characterName = $main ? $main->name : 'Unknown';
        $characterId = $main ? $main->character_id : null;
        $passwordGenerator = Str::random(24); // 12-character random password
        $discord_channel = env('DISCORD_NOTIFICATION_CHANNEL', 'poop');
        
        return view('decoy::decoyMumble', compact('characterName', 'characterId', 'ticker', 'passwordGenerator', 'discord_channel'));
    }    
}