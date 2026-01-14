<?php

namespace BJK\Decoy\Seat\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Seat\Eveapi\Models\Alliances\AllianceMember;
use Seat\Eveapi\Models\Corporation\CorporationMember;
use Seat\Eveapi\Models\Killmails\Killmail;
use Seat\Eveapi\Models\Killmails\KillmailAttacker;
use Seat\Eveapi\Models\RefreshToken;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;

class ImportZKillData implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable;


    /**
     * The main logic to fetch and update killmail data for each alliance.
     *
     * @return void
     */
    public function handle()
    {
    
        $combat_tracker_table = 'decoy_combat_tracker';
        if (!Schema::hasTable($combat_tracker_table)) {
         Schema::create($combat_tracker_table, function (Blueprint $table) {
             $table->id();
             $table->unsignedBigInteger('alliance_id')->unique();
             $table->string('alliance_name');
             $table->integer('killmails')->default(0);
             $table->timestamps();
         });
        }

    $allianceList = [99000285, 99001317, 99001954, 99001969, 99002003, 99002685, 99003214, 99003581, 99003995, 99005338, 99005866, 99006751, 99006941, 99007203, 99007629, 99007722, 99007887, 99008245, 99008684, 99008697, 99009163, 99009758, 99009927, 99009977, 99010281, 99010339, 99010389, 99010468, 99010517, 99010735, 99010877, 99011162, 99011181, 99011223, 99011268, 99011279, 99011312, 99011416, 99011720, 99011852, 99011983, 99011990, 99012042, 99012279, 99012328, 99012410, 99012485, 99012617, 99012770, 99012786, 99012813, 99012982, 99013095, 99013231, 99013444, 99013537, 99013590, 99013981, 99014203, 150097440, 154104258, 386292982, 431502563, 498125261, 741557221, 917526329, 922190997, 933731581, 1042504553, 1220922756, 1354830081, 1411711376, 1614483120, 1727758877, 1900696668, 1988009451];

     $addedCount = 0;
     $updatedCount = 0;
     
     foreach ($allianceList as $allianceID) {
         $allianceRecord = DB::table('decoy_combat_tracker')->where('alliance_id', $allianceID)->first();
         
         // Skip if the record was updated within the last 6 hours
         if ($allianceRecord && Carbon::parse($allianceRecord->updated_at)->gt(Carbon::now()->subHours(1))) {
             continue;
         }
     
         // Fetch data from the ZKillboard API
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

        // Example: Call an Artisan command (e.g., 'import:zkill-data')
        //Artisan::call('decoy:zkill-data');


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
