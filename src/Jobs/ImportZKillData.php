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

    $allianceList = [99005338, 99003581, 1354830081, 1727758877, 1900696668, 99003214, 99011223, 1042504553, 99009927, 99009163, 99012042, 1411711376, 99011162, 99007203, 99006941, 99002685, 99012982, 99003995, 99007887, 1988009451, 99011416, 99001317, 99012328, 498125261, 386292982, 99001954, 99001969, 99012410, 99007722, 99010877, 99011312, 741557221, 150097440, 1220922756, 99012770, 99010281, 917526329, 99002003, 99010735, 99011990, 99007629, 99011279, 99011983, 99009758, 154104258, 99011268, 99013537, 99012813, 99009977, 99011181, 99013231, 99010389, 99008684, 1614483120, 99012617, 99011852, 99013095, 99008697, 99012485, 99013444, 99000285, 99010517, 99011720, 99008245, 99006751, 922190997, 99012279, 99013590, 99010339, 99012786];

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
