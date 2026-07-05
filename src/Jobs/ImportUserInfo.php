<?php

namespace BJK\Decoy\Seat\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Seat\Eveapi\Models\Alliances\AllianceMember;
use Seat\Eveapi\Models\Corporation\CorporationMember;
use Seat\Web\Models\User;
use Seat\Web\Http\Controllers\Controller;

class ImportUserInfo implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable;


    /**
     * The main logic to fetch and update killmail data for each alliance.
     *
     * @return void
     */
    public function handle()
{
    if (!Schema::hasTable('decoy_user_dashboard')) {
        Schema::create('decoy_user_dashboard', function (Blueprint $table) {
            $table->id();
            $table->integer('character_id');
            $table->integer('order')->default(0);
            $table->integer('decoy')->default(0);
            $table->text('filter')->nullable();
            $table->text('name')->nullable();
            $table->float('sec')->default(0);
            $table->text('home')->nullable();
            $table->timestamp('training_until')->nullable();
            $table->text('training_skills')->nullable();
            $table->float('standings_blood')->default(0);
            $table->float('standings_eden')->default(0);
            $table->float('standings_trig')->default(0);
            $table->integer('fleets')->default(0);
            $table->double('killmails')->default(0);
            $table->double('kill_value')->default(0);
            $table->double('isk_total')->default(0);
            $table->double('isk_market')->default(0);
            $table->double('isk_ratting')->default(0);
            $table->double('isk_incursions')->default(0);
            $table->double('isk_missions')->default(0);
            $table->double('mining_value')->default(0);
            $table->double('mining_m3')->default(0);
            $table->integer('industry_manufacturing_slots')->default(0);
            $table->integer('industry_manufacturing_slots_total')->default(0);
            $table->integer('industry_research_slots')->default(0);
            $table->integer('industry_research_slots_total')->default(0);
            $table->integer('industry_reaction_slots')->default(0);
            $table->integer('industry_reaction_slots_total')->default(0);
            $table->json('planets')->nullable();
            $table->timestamps();
        });
    }

    // 1. Resolve Sync Rosters
    $fetchAllCorps = AllianceMember::where('alliance_id', 99012410)->pluck('corporation_id');
    $fetchAllCorpPilots = CorporationMember::whereIn('corporation_id', $fetchAllCorps)->pluck('character_id')->toArray();
    $fetchAllRegisteredMains = User::whereIn('main_character_id', $fetchAllCorpPilots)->get();
    $fetchAllAssociatedPilots = DB::table('refresh_tokens')
        ->whereIn('user_id', $fetchAllRegisteredMains->pluck('id'))
        ->whereNull('deleted_at')
        ->pluck('character_id')
        ->toArray();

    // Sync pilots in target table
    $existingDashboardPilots = DB::table('decoy_user_dashboard')->pluck('character_id')->toArray();
    $missingPilots = array_diff($fetchAllAssociatedPilots, $existingDashboardPilots);

    if (!empty($missingPilots)) {
        $validCharacters = DB::table('character_infos')
            ->whereIn('character_id', $missingPilots)
            ->whereNotNull('name')
            ->pluck('character_id');

        $inserts = [];
        foreach ($validCharacters as $pilotId) {
            $inserts[] = [
                'character_id' => $pilotId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($inserts)) {
            DB::table('decoy_user_dashboard')->insert($inserts);
        }
    }

    DB::table('decoy_user_dashboard')->whereNotIn('character_id', $fetchAllAssociatedPilots)->delete();

    // Reset decoy flags and set active ones in bulk
    DB::table('decoy_user_dashboard')->update(['decoy' => 0]);
    DB::table('decoy_user_dashboard')->whereIn('character_id', $fetchAllCorpPilots)->update(['decoy' => 1]);

    $pilotsToUpdate = DB::table('decoy_user_dashboard')->pluck('character_id')->toArray();
    if (empty($pilotsToUpdate)) {
        return;
    }

    // 2. Eager-Load Large Aggregates Upfront
    $characterInfos = DB::table('character_infos')->whereIn('character_id', $pilotsToUpdate)->get()->keyBy('character_id');
    $characterClones = DB::table('character_clones')->whereIn('character_id', $pilotsToUpdate)->get()->keyBy('character_id');

    $stationIds = $characterClones->where('home_location_type', 'station')->pluck('home_location_id')->unique();
    $structureIds = $characterClones->where('home_location_type', 'structure')->pluck('home_location_id')->unique();
    $stations = DB::table('universe_stations')->whereIn('station_id', $stationIds)->pluck('name', 'station_id');
    $structures = DB::table('universe_structures')->whereIn('structure_id', $structureIds)->pluck('name', 'structure_id');

    $allSkillQueues = DB::table('character_skill_queues')
        ->whereIn('character_id', $pilotsToUpdate)
        ->orderBy('queue_position', 'asc')
        ->get()
        ->groupBy('character_id');

    $invTypesNames = DB::table('invTypes')
        ->whereIn('typeID', $allSkillQueues->flatten()->pluck('skill_id')->unique())
        ->pluck('typeName', 'typeID');

    // Fix: standings_blood reads from_id 500012, so fetch 500012 here (was 500011)
    $allStandings = DB::table('character_standings')
        ->whereIn('character_id', $pilotsToUpdate)
        ->whereIn('from_id', [500012, 500027, 500026])
        ->get()
        ->groupBy('character_id');

    $allSkillChecks = DB::table('character_skills')
        ->whereIn('character_id', $pilotsToUpdate)
        ->where('skill_id', 3361)
        ->pluck('trained_skill_level', 'character_id');

    $allWalletBalances = DB::table('character_wallet_balances')->whereIn('character_id', $pilotsToUpdate)->pluck('balance', 'character_id');

    $allWalletJournals = DB::table('character_wallet_journals')
        ->whereIn('character_id', $pilotsToUpdate)
        ->where('date', '>=', Carbon::now()->subDays(30))
        ->get()
        ->groupBy('character_id');

    $allOrders = DB::table('character_orders')
        ->whereIn('character_id', $pilotsToUpdate)
        ->where('state', 'active')
        ->whereNull('is_buy_order')
        ->get()
        ->groupBy('character_id');

    $allMiningData = DB::table('character_minings')
        ->whereIn('character_id', $pilotsToUpdate)
        ->where('date', '>=', Carbon::now()->subDays(30))
        ->get()
        ->groupBy('character_id');

    $allIndustrySlots = DB::table('character_skills')
        ->whereIn('character_id', $pilotsToUpdate)
        ->whereIn('skill_id', [3387, 24625, 3406, 24624, 45748, 45749])
        ->get()
        ->groupBy('character_id');

    $allActiveIndyJobs = DB::table('character_industry_jobs')
        ->whereIn('character_id', $pilotsToUpdate)
        ->where('status', 'active')
        ->get()
        ->groupBy('character_id');

    $allPlanets = DB::table('character_planets')
        ->whereIn('character_id', $pilotsToUpdate)
        ->orderBy('planet_id', 'asc')
        ->get()
        ->groupBy('character_id');

    $planetNames = DB::table('planets')->whereIn('planet_id', $allPlanets->flatten()->pluck('planet_id')->unique())->pluck('name', 'planet_id');
    $planetPins = DB::table('character_planet_pins')
        ->whereIn('character_id', $pilotsToUpdate)
        ->orderBy('expiry_time', 'desc')
        ->get()
        ->groupBy(function ($item) { return $item->character_id . '-' . $item->planet_id; });

    // 3. Eager-Load Complex Join Aggregates (Killmails)
    $killmailCounts = DB::table('killmail_details')
        ->join('killmail_attackers', 'killmail_details.killmail_id', '=', 'killmail_attackers.killmail_id')
        ->whereIn('killmail_attackers.character_id', $pilotsToUpdate)
        ->where('killmail_details.killmail_time', '>=', Carbon::now()->subDays(30))
        ->groupBy('killmail_attackers.character_id')
        ->select('killmail_attackers.character_id', DB::raw('COUNT(DISTINCT killmail_details.killmail_id) as total'))
        ->pluck('total', 'character_id');

    $rawKillmailItems = DB::table('killmail_details')
        ->join('killmail_attackers', 'killmail_details.killmail_id', '=', 'killmail_attackers.killmail_id')
        ->join('killmail_victims', 'killmail_details.killmail_id', '=', 'killmail_victims.killmail_id')
        ->leftJoin('killmail_victim_items', 'killmail_details.killmail_id', '=', 'killmail_victim_items.killmail_id')
        ->whereIn('killmail_attackers.character_id', $pilotsToUpdate)
        ->where('killmail_details.killmail_time', '>=', Carbon::now()->subDays(30))
        ->select(
            'killmail_attackers.character_id',
            'killmail_details.killmail_id',
            'killmail_victims.ship_type_id',
            'killmail_victim_items.item_type_id',
            'killmail_victim_items.quantity_destroyed',
            'killmail_victim_items.quantity_dropped'
        )->get()->groupBy('character_id');

    // Fix: fleet membership counted in one pass instead of one query per pilot
    $allFleetMembers = DB::table('decoy_fleets')->pluck('fleet_members');
    $fleetCounts = array_fill_keys($pilotsToUpdate, 0);
    foreach ($allFleetMembers as $membersJson) {
        $members = json_decode($membersJson, true) ?? [];
        foreach ($members as $member) {
            $cid = $member['character_id'] ?? null;
            if ($cid !== null && isset($fleetCounts[$cid])) {
                $fleetCounts[$cid]++;
            }
        }
    }

    // Fix: only load the reference-price/material data actually needed this run,
    // instead of pulling entire SDE tables (invTypes / invTypeMaterials / market_prices
    // can run into tens of thousands of rows).
    $miningTypeIds = $allMiningData->flatten()->pluck('type_id')->unique();

    $invTypesVolume = DB::table('invTypes')->whereIn('typeID', $miningTypeIds)->pluck('volume', 'typeID');

    $invTypeMaterials = DB::table('invTypeMaterials')
        ->whereIn('typeID', $miningTypeIds)
        ->get()
        ->groupBy('typeID');

    $killmailShipTypeIds = $rawKillmailItems->flatten()->pluck('ship_type_id')->filter()->unique();
    $killmailItemTypeIds = $rawKillmailItems->flatten()->pluck('item_type_id')->filter()->unique();
    $materialTypeIds = $invTypeMaterials->flatten()->pluck('materialTypeID')->filter()->unique();

    $neededPriceTypeIds = $killmailShipTypeIds
        ->merge($killmailItemTypeIds)
        ->merge($materialTypeIds)
        ->unique();

    $marketPrices = DB::table('market_prices')->whereIn('type_id', $neededPriceTypeIds)->pluck('average_price', 'type_id');

    // Helper for skill queue level -> roman numeral
    $toRoman = function ($number) {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'][$number] ?? '';
    };

    // 4. The Consolidated Loop (builds rows in memory, no per-pilot writes)
    $rows = [];

    foreach ($pilotsToUpdate as $characterId) {
        $updatePayload = ['character_id' => $characterId];

        // Identity & Security
        if ($info = $characterInfos->get($characterId)) {
            $updatePayload['name'] = $info->name;
            $updatePayload['sec'] = $info->security_status;
        }

        // Home Clone Location
        if ($clone = $characterClones->get($characterId)) {
            if ($clone->home_location_type === 'station') {
                $updatePayload['home'] = $stations->get($clone->home_location_id, 'N/A');
            } else {
                $updatePayload['home'] = $structures->get($clone->home_location_id, 'N/A');
            }
        }

        // Skills in Training
        $skills = $allSkillQueues->get($characterId, collect());
        $updatePayload['training_until'] = $skills->max('finish_date') ?: null;

        $skillNames = [];
        foreach ($skills as $skill) {
            if ($name = $invTypesNames->get($skill->skill_id)) {
                $skillNames[] = $name . ' ' . $toRoman($skill->finished_level);
            }
        }
        $updatePayload['training_skills'] = json_encode($skillNames);

        // Standings
        $standings = $allStandings->get($characterId, collect())->pluck('standing', 'from_id');
        $skillCheck = $allSkillChecks->get($characterId, 0);
        $standingsBlood = $standings->get(500012, 0);

        $updatePayload['standings_blood'] = round($standingsBlood + (10 - $standingsBlood) * ($skillCheck * 0.04), 2);
        $updatePayload['standings_eden'] = $standings->get(500027, 0);
        $updatePayload['standings_trig'] = $standings->get(500026, 0);

        // Fleets & Killmails Counts
        $updatePayload['fleets'] = $fleetCounts[$characterId] ?? 0;
        $updatePayload['killmails'] = $killmailCounts->get($characterId, 0);

        // Killboard Valuation
        $totalKillValue = 0;
        if ($pilotKills = $rawKillmailItems->get($characterId)) {
            $groupedByKill = $pilotKills->groupBy('killmail_id');
            foreach ($groupedByKill as $killItems) {
                $first = $killItems->first();
                $shipValue = $marketPrices->get($first->ship_type_id, 0);

                $contentsValue = 0;
                foreach ($killItems as $item) {
                    if ($item->item_type_id) {
                        $qty = ($item->quantity_destroyed ?? 0) + ($item->quantity_dropped ?? 0);
                        $contentsValue += $qty * $marketPrices->get($item->item_type_id, 0);
                    }
                }
                $totalKillValue += ($shipValue + $contentsValue + 10000);
            }
        }
        $updatePayload['kill_value'] = $totalKillValue;

        // Wallet Metrics
        $updatePayload['isk_total'] = $allWalletBalances->get($characterId, 0);

        $journals = $allWalletJournals->get($characterId, collect());
        $updatePayload['isk_ratting'] = $journals->where('ref_type', 'bounty_prizes')->sum('amount');
        $updatePayload['isk_missions'] = $journals->whereIn('ref_type', ['agent_mission_reward', 'agent_mission_time_bonus_reward'])->sum('amount');
        $updatePayload['isk_incursions'] = $journals->where('ref_type', 'corporate_reward_payout')->sum('amount');

        // Escrow/Market Orders
        $orders = $allOrders->get($characterId, collect());
        $marketValue = 0;
        foreach ($orders as $order) {
            $marketValue += ($order->volume_remain * $order->price);
        }
        $updatePayload['isk_market'] = $marketValue;

        // Mining Yield Ingestion
        $miningRecords = $allMiningData->get($characterId, collect())->groupBy('type_id');
        $totalM3Sum = 0;
        $totalValSum = 0;

        foreach ($miningRecords as $typeId => $records) {
            $totalQty = $records->sum('quantity');
            $volume = $invTypesVolume->get($typeId, 0);
            $totalM3Sum += ($totalQty * $volume);

            if ($materials = $invTypeMaterials->get($typeId)) {
                $itemUnitValue = 0;
                foreach ($materials as $mat) {
                    $itemUnitValue += ($marketPrices->get($mat->materialTypeID, 0) * $mat->quantity);
                }
                $totalValSum += ($itemUnitValue * $totalQty);
            }
        }
        $updatePayload['mining_value'] = round($totalValSum / 100 * 0.9, 2);
        $updatePayload['mining_m3'] = $totalM3Sum;

        // Industry Limits & Jobs
        $indySkills = $allIndustrySlots->get($characterId, collect())->pluck('trained_skill_level', 'skill_id');
        $updatePayload['industry_manufacturing_slots_total'] = 1 + $indySkills->get(3387, 0) + $indySkills->get(24625, 0);
        $updatePayload['industry_research_slots_total'] = 1 + $indySkills->get(3406, 0) + $indySkills->get(24624, 0);
        $updatePayload['industry_reaction_slots_total'] = 1 + $indySkills->get(45748, 0) + $indySkills->get(45749, 0);

        $indyJobs = $allActiveIndyJobs->get($characterId, collect());
        $updatePayload['industry_manufacturing_slots'] = $indyJobs->where('activity_id', 1)->count();
        $updatePayload['industry_reaction_slots'] = $indyJobs->where('activity_id', 9)->count();
        $updatePayload['industry_research_slots'] = $indyJobs->whereNotIn('activity_id', [1, 9])->count();

        // Planetary Interaction Data Array Mapping
        $planets = $allPlanets->get($characterId, collect());
        foreach ($planets as $planet) {
            $planet->user = $characterId;
            $planet->planet_name = $planetNames->get($planet->planet_id, 'Unknown');

            switch ($planet->planet_type) {
                case 'barren': $planet->image = 2016; break;
                case 'gas': $planet->image = 13; break;
                case 'ice': $planet->image = 12; break;
                case 'lava': $planet->image = 2015; break;
                case 'oceanic': $planet->image = 2014; break;
                case 'plasma': $planet->image = 2063; break;
                case 'storm': $planet->image = 2017; break;
                case 'temperate': $planet->image = 11; break;
                default: $planet->image = null; break;
            }

            $pinKey = $characterId . '-' . $planet->planet_id;
            $planet->extractor_end = $planetPins->has($pinKey) ? $planetPins->get($pinKey)->first()->expiry_time : null;
        }
        $updatePayload['planets'] = json_encode($planets);
        $updatePayload['updated_at'] = now();

        $rows[] = $updatePayload;
    }

    // 5. Batched write — chunked upsert instead of one UPDATE per pilot
    if (!empty($rows)) {
        $updateColumns = [
            'name', 'sec', 'home', 'training_until', 'training_skills',
            'standings_blood', 'standings_eden', 'standings_trig',
            'fleets', 'killmails', 'kill_value',
            'isk_total', 'isk_market', 'isk_ratting', 'isk_incursions', 'isk_missions',
            'mining_value', 'mining_m3',
            'industry_manufacturing_slots', 'industry_manufacturing_slots_total',
            'industry_research_slots', 'industry_research_slots_total',
            'industry_reaction_slots', 'industry_reaction_slots_total',
            'planets', 'updated_at',
        ];

        DB::transaction(function () use ($rows, $updateColumns) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('decoy_user_dashboard')->upsert($chunk, ['character_id'], $updateColumns);
            }
        });
    }
}

    /**
     * Define tags for the job (optional).
     *
     * @return array
     */
    public function tags()
    {
        return ['decoy'];
    }
}
