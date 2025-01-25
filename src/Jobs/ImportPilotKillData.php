<?php

namespace BJK\Decoy\Seat\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
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
use Illuminate\Support\Facades\Http;

class ImportPilotKillData implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable;

    /**
     * The main logic to fetch and update killmail data for each alliance.
     *
     * @return void
     */
    public function handle()
    {
    
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
        }

        $combat_users_zkill_table = 'decoy_combat_users_zkill';
        if (!Schema::hasTable($combat_users_zkill_table)) {
            Schema::create($combat_users_zkill_table, function (Blueprint $table) {
                $table->id();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $recentUserUpdate = DB::table('decoy_combat_users')->max('users_updated_at');
        if (!$recentUserUpdate || Carbon::parse($recentUserUpdate)->lt(Carbon::now()->subMinutes(1))) {
            $corpIds = AllianceMember::where('alliance_id', 99012410)->pluck('corporation_id');
            $corpPilots = CorporationMember::whereIn('corporation_id', $corpIds)->pluck('character_id');
            $characterList = User::whereIn('main_character_id', $corpPilots)->get();
            $userIds = $characterList->pluck('id');
            DB::table('decoy_combat_users')->whereNotIn('main_character_id', $corpPilots)->delete();
        
            foreach ($characterList as $user) {
                $associatedCharacterIds = RefreshToken::where('user_id', $user->id)->pluck('character_id');
                
                // Convert collections to arrays before using array_intersect()
                $filteredCharacters = array_values(array_intersect($associatedCharacterIds->toArray(), $corpPilots->toArray()));
                $newAssociatedCharacterIds = json_encode($filteredCharacters);
                $currentTime = Carbon::now();
            
                // Find the existing record for the user
                $existingUser = DB::table('decoy_combat_users')
                    ->where('main_character_id', $user->main_character_id)
                    ->first();
            
                // Check if there is a difference in the associated_character_ids or users_updated_at
                if (!$existingUser || $existingUser->associated_character_ids !== $newAssociatedCharacterIds || $existingUser->users_updated_at !== $currentTime) {
                    // If there are changes, either insert or update the record
                    DB::table('decoy_combat_users')->updateOrInsert(
                        ['main_character_id' => $user->main_character_id], // Condition for finding existing data
                        [
                            'name' => $user->name,
                            'main_character_id' => $user->main_character_id,
                            'associated_character_ids' => $newAssociatedCharacterIds,
                            'users_updated_at' => $currentTime,
                        ]
                    );
                }
            }
        }

        $toUpdate = DB::table('decoy_combat_users')->whereNull('kills_updated_at')->orWhere('kills_updated_at', '<', Carbon::now()->subMinutes(2))->pluck('main_character_id')->toArray();
        
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
