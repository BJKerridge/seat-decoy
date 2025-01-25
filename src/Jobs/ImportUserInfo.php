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
                $table->float('standings_angel')->default(0);
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
                $table->integer('mining_m3')->default(0);
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
    
        //Populate table with the character list
        $fetchAllCorps = AllianceMember::where('alliance_id', 99012410)->pluck('corporation_id');
        $fetchAllCorpPilots = CorporationMember::whereIn('corporation_id', $fetchAllCorps)->pluck('character_id');
        $fetchAllRegisteredMains = User::whereIn('main_character_id', $fetchAllCorpPilots)->get();
        $fetchAllAssociatedPilots = DB::table('refresh_tokens')->whereIn('user_id', $fetchAllRegisteredMains->pluck('id'))->pluck('character_id');
            
        // Insert missing pilots
        foreach ($fetchAllAssociatedPilots as $pilotId) {
            $exists = DB::table('decoy_user_dashboard')->where('character_id', $pilotId)->exists();
            $characterInfo = DB::table('character_infos')->where('character_id', $pilotId)->first();
            
            if (!$exists && $characterInfo && $characterInfo->name) {
                DB::table('decoy_user_dashboard')->insert([
                    'character_id' => $pilotId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Delete pilots that don't exist in fetchAllAssociatedPilots
        DB::table('decoy_user_dashboard')
            ->whereNotIn('character_id', $fetchAllAssociatedPilots)
            ->delete();

        $pilotsToUpdate = DB::table('decoy_user_dashboard')->pluck('character_id');

        // Update 'decoy' value for each pilot in fetchAllCorpPilots that exists in decoy_user_dashboard
        foreach ($fetchAllCorpPilots as $pilotId) {
            DB::table('decoy_user_dashboard')
                ->where('character_id', $pilotId)
                ->update(['decoy' => 1]);
        }

        // Fetch the name and security status
        foreach ($pilotsToUpdate as $user) {
            $character = DB::table('character_infos')->where('character_id', $user)->first();
            if ($character) {
                DB::table('decoy_user_dashboard')
                    ->where('character_id', $character->character_id)
                    ->update([
                        'name' => $character->name,
                        'sec' => $character->security_status,
                    ]);
            }
        }

        // Fetch the home system
        foreach ($pilotsToUpdate as $user) {
            $character = DB::table('character_clones')->where('character_id', $user)->first();
            if ($character->home_location_type == "station"){
                $station = DB::table('universe_stations')->where('station_id', $character->home_location_id)->first();
                $home = $station->name ?? 'N/A';
            }else{
                $structure = DB::table('universe_structures')->where('structure_id', $character->home_location_id)->first();
                $home = $structure->name ?? 'N/A';
            }        
            DB::table('decoy_user_dashboard')->where('character_id', $character->character_id)->update(['home' => $home,]);
        };

        //Roman Numeral Function
        function toRoman($number) {
            $map = [
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V'
            ];
            return $map[$number] ?? '';
        }

        // Select the skill in training and the finish date
        foreach ($pilotsToUpdate as $character_id) {
            $skills_finish = DB::table('character_skill_queues')
            ->where('character_id', $character_id)
            ->max('finish_date');

            // If there are no rows, set $skills_finish to null
            if (!$skills_finish) {
                $skills_finish = null;
            }

            $skills = DB::table('character_skill_queues')
            ->where('character_id', $character_id)
            ->orderBy('queue_position', 'asc')
            ->get(['skill_id', 'finished_level']);

            // Initialize the skill_names array
            $skill_names = [];

            // Fetch the skill names and store them in the skill_names array
            foreach ($skills as $skill) {
                $skill_name = DB::table('invTypes')
                    ->where('typeId', $skill->skill_id)
                    ->value('typeName'); // Use value instead of pluck

                if ($skill_name) {
                    $skill_names[] = $skill_name . ' ' . toRoman($skill->finished_level);
                }
            }

            // Convert the skill_names array to JSON
            $skill_names_json = json_encode($skill_names);

            DB::table('decoy_user_dashboard')
            ->where('character_id', $character_id)
            ->update(['training_skills' => $skill_names_json, 'training_until' => $skills_finish]);
        };

        // Fetch Angel/Eden/Trig standings
        foreach ($pilotsToUpdate as $character) {
            $standings = DB::table('character_standings')
            ->where('character_id', $character)
            ->whereIn('from_id', [500011, 500027, 500026])
            ->pluck('standing', 'from_id');

            $skillCheck = DB::table('character_skills')
            ->where('character_id', $character)
            ->where('skill_id', 3361)
            ->value('trained_skill_level') ?? 0;

            // Extract the standings for each specific `from_id`
            $standings_angel = $standings[500011] ?? 0;
            $standings_angel = round($standings_angel + (10 - $standings_angel) * ($skillCheck * 0.04), 2);
            $standings_eden = $standings[500027] ?? 0;
            $standings_trig = $standings[500026] ?? 0;
            
            DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update(['standings_angel' => $standings_angel,
                        'standings_eden' => $standings_eden,
                        'standings_trig' => $standings_trig]);
        };

        // Fetch the number of fleets
        foreach ($pilotsToUpdate as $character) {
            $fleet_count = DB::table('decoy_fleets')
            ->whereRaw('json_contains(fleet_members, \'{"character_id": ' . $character . '}\')')
            ->count();
            
            DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update(['fleets' => $fleet_count]);
        };

        // Fetch the number of killmails per pilot
        foreach ($pilotsToUpdate as $character) {
            $kill_count = DB::table('killmail_details')
            ->join('killmail_attackers', 'killmail_details.killmail_id', '=', 'killmail_attackers.killmail_id')
            ->where('killmail_attackers.character_id', $character)
            ->where('killmail_details.killmail_time', '>=', Carbon::now()->subDays(30))
            ->distinct()
            ->count('killmail_details.killmail_id');
            
            DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update(['killmails' => $kill_count]);
        };

        // Fetch the total kill value per pilot
        foreach ($pilotsToUpdate as $character) {
            $total_kill_value = 0;
            $killmailIds = DB::table('killmail_details')
            ->join('killmail_attackers', 'killmail_details.killmail_id', '=', 'killmail_attackers.killmail_id')
            ->where('killmail_attackers.character_id', $character)
            ->where('killmail_details.killmail_time', '>=', Carbon::now()->subDays(30))
            ->distinct()
            ->pluck('killmail_details.killmail_id')
            ->toArray();

            foreach ($killmailIds as $killmailId) {
                $victimData = DB::table('killmail_victims')
                    ->where('killmail_id', $killmailId)
                    ->select('killmail_id', 'ship_type_id')
                    ->first();
    
                $shipValue = DB::table('market_prices')->where('type_id', $victimData->ship_type_id)->value('adjusted_price');
                $shipContentsValue = DB::table('killmail_victim_items')
                ->join('market_prices', 'killmail_victim_items.item_type_id', '=', 'market_prices.type_id')
                ->where('killmail_victim_items.killmail_id', $victimData->killmail_id)
                ->selectRaw('SUM((COALESCE(killmail_victim_items.quantity_destroyed, 0) + COALESCE(killmail_victim_items.quantity_dropped, 0)) * market_prices.adjusted_price) as total_value')
                ->value('total_value'); // Fetch the single summed value
                $killValue = $shipValue + $shipContentsValue + 10000;
                $total_kill_value = $total_kill_value + $killValue;
            };

            DB::table('decoy_user_dashboard')
            ->where('character_id', $character)
            ->update(['kill_value' => $total_kill_value]);

            };

        // Fetch the total ISK per pilot
        foreach ($pilotsToUpdate as $character) {
            $total_isk = DB::table('character_wallet_balances')
            ->where('character_id', $character)
            ->value('balance') ?? 0;
            
            DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update(['isk_total' => $total_isk]);
            };

        $last30DaysData = DB::table('character_wallet_journals')
        ->where('date', '>=', Carbon::now()->subDays(30))
        ->get();

        foreach ($pilotsToUpdate as $character) {
            $characterData = $last30DaysData->where('character_id', $character);
            $bounty = $characterData->where('ref_type', 'bounty_prizes')->sum('amount');
            $missions = $characterData->whereIn('ref_type', ['agent_mission_reward', 'agent_mission_time_bonus_reward'])->sum('amount');
            $incursions = $characterData->where('ref_type', 'corporate_reward_payout')->sum('amount');

            DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update(['isk_ratting' => $bounty,
                        'isk_missions' => $missions,
                        'isk_incursions' => $incursions]);
            };

            // Fetch the market amount per pilot
            foreach ($pilotsToUpdate as $character) {
                $market_value = 0; // Initialize market value for each character
        
                // Query the character_orders table
                $orders = DB::table('character_orders')
                    ->where('character_id', $character)
                    ->where('state', '=', 'active')
                    ->whereNull('is_buy_order')
                    ->get(['volume_remain', 'price']);
        
                // Loop through the results and calculate the market value
                foreach ($orders as $order) {
                    $market_value += $order->volume_remain * $order->price;
                }
    
                DB::table('decoy_user_dashboard')
                    ->where('character_id', $character)
                    ->update(['isk_market' => $market_value]);
    
            }


            // Fetch the mining data
            foreach ($pilotsToUpdate as $character) {
                $miningData = DB::table('character_minings')
                    ->join('invTypes', 'character_minings.type_id', '=', 'invTypes.typeID')
                    ->where('character_minings.character_id', $character)
                    ->where('character_minings.date', '>=', Carbon::now()->subDays(30))
                    ->select('character_minings.type_id', DB::raw('SUM(character_minings.quantity) as total_quantity'), 'invTypes.volume')
                    ->groupBy('character_minings.type_id', 'invTypes.volume')
                    ->get();
    
                $total_m3_sum = 0;
                $total_value_sum = 0;
    
                // Process the mining data as needed
                foreach ($miningData as $data) {
                    $total_m3 = $data->total_quantity * $data->volume;
                    $total_m3_sum += $total_m3;
    
                    // Calculate the total value
                    $materials = DB::table('invTypeMaterials')
                        ->where('typeID', $data->type_id)
                        ->get(['materialTypeID', 'quantity']);
    
                    $total_value = 0;
    
                    foreach ($materials as $material) {
                        $adjusted_price = DB::table('market_prices')
                            ->where('type_id', $material->materialTypeID)
                            ->value('adjusted_price');
                        $material_value = $adjusted_price * $material->quantity;
                        $total_value += $material_value;
                    }
                    $total_value *= $data->total_quantity;
                    $total_value_sum += $total_value;
                }
                $total_value_sum = round($total_value_sum / 100 * 0.9, 2);
                DB::table('decoy_user_dashboard')
                        ->where('character_id', $character)
                        ->update(['mining_value' => $total_value_sum, 'mining_m3' => $total_m3_sum]);
            }


        // Fetch the industry slots
            foreach ($pilotsToUpdate as $character) {
                // Initialize the manufacturing slots total
                $manufacturing_slots_total = 1;
                $research_slots_total = 1;
                $reaction_slots_total = 1;
        
                // Query the character_skills table for the specified skill_ids
                $skills = DB::table('character_skills')
                    ->where('character_id', $character)
                    ->whereIn('skill_id', [3387, 24625, 2406, 24624, 45748, 45749])
                    ->pluck('trained_skill_level', 'skill_id');
        
                // Get the values for each skill_id, defaulting to 0 if not found
                $mp = $skills[3387] ?? 0;
                $amp = $skills[24625] ?? 0;
                $lo = $skills[2406] ?? 0;
                $alp = $skills[24624] ?? 0;
                $mr = $skills[45748] ?? 0;
                $amr = $skills[45749] ?? 0;
        
                // Calculate the manufacturing slots total
                $manufacturing_slots_total += $mp + $amp;
                $research_slots_total += $lo + $alp;
                $reaction_slots_total += $mr + $amr;
        
                DB::table('decoy_user_dashboard')
                            ->where('character_id', $character)
                            ->update([
                            'industry_manufacturing_slots_total' => $manufacturing_slots_total,
                            'industry_research_slots_total' => $research_slots_total,
                            'industry_reaction_slots_total' => $reaction_slots_total,
                            ]);
            }

            // Fetch active industry jobs

            foreach ($pilotsToUpdate as $character) {
                $manufacturing_jobs = 0;
                $research_jobs = 0;
                $reaction_jobs = 0;
    
                $industry_jobs = DB::table('character_industry_jobs')
                ->where('character_id', $character)
                ->where('status', 'active')
                ->pluck('activity_id');
    
                foreach ($industry_jobs as $job) {
                    if ($job == 1) {
                        $manufacturing_jobs++;
                    } elseif ($job == 9) {
                        $reaction_jobs++;
                    } else {
                        $research_jobs++;
                    }
                }
    
                DB::table('decoy_user_dashboard')
                ->where('character_id', $character)
                ->update([
                'industry_manufacturing_slots' => $manufacturing_jobs,
                'industry_research_slots' => $research_jobs,
                'industry_reaction_slots' => $reaction_jobs,
                ]);
    
            }

            // Fetch the planets

            foreach ($pilotsToUpdate as $character) {

                $planets = DB::table('character_planets')
                    ->where('character_id', $character)
                    ->orderBy('planet_id', 'asc')
                    ->get(['planet_id', 'planet_type']);
    
                    foreach ($planets as $planet) {
                        $planet->user = $character;
                    
                        // Fetch planet name
                        $planet->planet_name = DB::table('planets')
                            ->where('planet_id', $planet->planet_id)
                            ->value('name');
                    
                        // Set the planet image based on type
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
                    
                        // Fetch extractor end time
                        $planet->extractor_end = DB::table('character_planet_pins')
                            ->where('planet_id', $planet->planet_id)
                            ->orderBy('expiry_time', 'desc')
                            ->value('expiry_time');
                    }
                    
                    DB::table('decoy_user_dashboard')
                        ->where('character_id', $character)
                        ->update([
                            'planets' => $planets,
                        ]);
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
