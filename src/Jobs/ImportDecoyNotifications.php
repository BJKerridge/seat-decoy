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
use Seat\Eveapi\Commands\Esi\Update\Notifications;

class ImportDecoyNotifications implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable;


    /**
     * The main logic to fetch and update killmail data for each alliance.
     *
     * @return void
     */
    public function handle()
    {       
        if (!Schema::hasTable('decoy_notifications')) {
            Schema::create('decoy_notifications', function (Blueprint $table) {
                $table->id();
                $table->text('notification_id');
                $table->timestamp('timestamp');
                $table->text('type');
                $table->longText('text');
                $table->timestamps();
            });
        }

        $characters = DB::table('refresh_tokens')->where('user_id', 2)->pluck('character_id');

        $character_list = DB::table('decoy_user_dashboard')
        ->whereIn('character_id', $characters)
        ->where('decoy', 1)
        ->orderBy('order', 'asc')
        ->pluck('character_id')
        ->toArray();

        $chosenCharacter = DB::table('refresh_tokens')
        ->whereIn('character_id', $character_list)
        ->where('deleted_at', null)
        ->orderBy('updated_at', 'desc')
        ->value('character_id');

        // Call the Artisan command
        Artisan::call('esi:update:notifications', [
            'character_id' => $chosenCharacter,
        ]);

        $alertsToCheck = [
            'BillPaidCorpAllMsg',
            'StructureLostShields',
            'StructureLostArmor',
            'StructureUnderAttack',
            'SkyhookUnderAttack',
            'SkyhookLostShields',
            'SkyhookDestroyed',
            'EntosisCaptureStarted',
            'SovStructureReinforced'
        ];

        $distinctNotifications = DB::table('character_notifications')
        ->whereIn('character_id', $character_list)  // Filtering by character_id in character_list
        ->where('timestamp', '>', Carbon::now()->subDays(28))  // Filtering by timestamp within the last 14 days
        ->whereIn('type', $alertsToCheck)
        ->distinct('notification_id')  // Ensures distinct notification_id values
        ->select('notification_id', 'type', 'timestamp', 'text')  // Selecting the specific columns
        ->get();  // Retrieves the results as a collection

        foreach ($distinctNotifications as $notification) {
            $exists = DB::table('decoy_notifications')->where('notification_id', $notification->notification_id)->exists();
            if (!$exists) {
                DB::table('decoy_notifications')->insert([
                    'notification_id' => $notification->notification_id,
                    'type' => $notification->type,
                    'timestamp' => $notification->timestamp,
                    'text' => $notification->text,
                ]);
                // Perform action based on the alert type
                switch ($notification->type) {
                    case 'SkyhookUnderAttack': self::skyhookUnderAttack($notification); break;
                    case 'SkyhookLostShields': self::skyhookLostShields($notification); break;
                    case 'EntosisCaptureStarted': self::entosisCaptureStarted($notification); break;
                    case 'SovStructureReinforced': self::sovStructureReinforced($notification); break;
                    case 'StructureUnderAttack': self::structureUnderAttack($notification); break;
                    case 'StructureLostShields': self::structureLostShields($notification); break;
                    case 'StructureLostArmor': self::structureLostArmor($notification); break;
                    default: break;
                }
            }
        }
    }
    

    

    /* ==================================================
           SEND THE NOTIFICATION TO DISCORD
    ================================================== */
    private static function sendToDiscord(array $message)
    {
        $webhookUrl = "https://discord.com/api/webhooks/1333410250346336360/fUZc9H4_Ua9o6d4srw6f5VMPUmUAoQOMNaQEKJVozzn13IBY8mueLv0ukDhGHCfMGz9j";
        $response = Http::post($webhookUrl, $message);

        return $response->successful()
            ? redirect()->back()->with('success', 'Message sent to Discord!')
            : redirect()->back()->with('error', 'Failed to send message.');
    }

    private static function formatNotificationMessage($content, $title, $description, $color, $thumbnailUrl = null)
    {
        $message = [
            'content' => $content,
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $description,
                    'color' => $color,
                ]
            ]
        ];

        if ($thumbnailUrl) {
            $message['embeds'][0]['thumbnail'] = ['url' => $thumbnailUrl];
        }

        return $message;
    }

    /* ==================================================
           PARSE THE NOTIFICATION DATA
    ================================================== */
    private static function parseNotificationData($notification)
    {
        $lines = explode("\n", $notification->text);
        $parsedData = [];
        $currentKey = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(":", $line, 2);
                $parsedData[trim($key)] = trim($value) ?: [];
                $currentKey = empty($value) ? $key : null;
            } elseif ($currentKey !== null) {
                $parsedData[$currentKey][] = trim($line, '- ');
            }
        }

        return $parsedData;
    }

    private static function getStructureId($structureIdString)
    {
        $parts = explode(' ', $structureIdString);
        return trim(end($parts));
    }

    /* ==================================================
           SKYHOOK UNDER ATTACK
    ================================================== */
    public static function skyhookUnderAttack($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $planet = DB::table('planets')->where('planet_id', $parsedData['planetID'])->first(['name', 'type_id']);
        $planetValue = $parsedData['planetShowInfoData'][1] ?? 'Unknown';

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$planet->name} Skyhook is under attack!",
            "{$planet->name} Skyhook Under Attack",
            "Attacking Corporation: {$parsedData['corpName']}\n" .
            "Attacking Alliance: {$parsedData['allianceName']}\n" .
            "{$notification->timestamp}",
            33791,
            "https://images.evetech.net/types/{$planetValue}/icon"
        ));
    }

    /* ==================================================
           SKYHOOK LOST SHIELDS
    ================================================== */
    public static function skyhookLostShields($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $planet = DB::table('planets')->where('planet_id', $parsedData['planetID'])->first(['name', 'type_id']);
        $exitTimer = Carbon::parse($notification->timestamp)->addSeconds($parsedData['timeLeft'] / 10000000)->format('Y-m-d H:i:s');

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$planet->name} Skyhook has lost shields",
            "{$planet->name} Skyhook has lost shields",
            "Exit Timer: {$exitTimer}\n \n{$notification->timestamp}",
            16711680,
            "https://images.evetech.net/types/{$planet->type_id}/icon"
        ));
    }

    /* ==================================================
           ENTOSIS CAPTURE STARTED
    ================================================== */
    public static function entosisCaptureStarted($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $systemData = DB::table('solar_systems')->where('system_id', $parsedData['solarSystemID'])->first(['name', 'region_id']);
        $region = DB::table('regions')->where('region_id', $systemData->region_id)->value('name');
        $adm = DB::table('sovereignty_structures')->where('solar_system_id', $parsedData['solarSystemID'])->value('vulnerability_occupancy_level');
        $admTime = $adm * 10;

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$systemData->name} is being Entosised!",
            "{$systemData->name} is being Entosised!",
            "ADM: {$adm} \n {$admTime} minutes remaining \n \n{$notification->timestamp}",
            33791,
            "https://images.evetech.net/types/34593/icon"
        ));
    }

    /* ==================================================
           SOVEREIGNTY STRUCTURE REINFORCED
    ================================================== */
    public static function sovStructureReinforced($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $systemData = DB::table('solar_systems')->where('system_id', $parsedData['solarSystemID'])->first(['name', 'region_id']);
        $region = DB::table('regions')->where('region_id', $systemData->region_id)->value('name');
        $exitTimer = Carbon::parse('31/12/1600 23:59:59')->addSeconds($parsedData['decloakTime'] / 10000000)->format('Y-m-d H:i:s');

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$systemData->name} has been reinforced",
            "{$systemData->name} has been reinforced",
            "ADM: {$adm} \n {$admTime} minutes remaining \n \n{$notification->timestamp}",
            16711680,
            "https://images.evetech.net/types/34593/icon"
        ));
    }

    /* ==================================================
           STRUCTURE UNDER ATTACK
    ================================================== */
    public static function structureUnderAttack($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $structureId = self::getStructureId($parsedData['structureID']);
        $structureName = DB::table('universe_structures')->where('structure_id', $structureId)->value('name');

        return self::sendToDiscord(self::formatNotificationMessage(
            "Structure is under attack!",
            "{$structureName} is under attack!",
            "Attacking Corporation: {$parsedData['corpName']}\n" .
            "Attacking Alliance: {$parsedData['allianceName']}\n" .
            "{$notification->timestamp}",
            33791,
            "https://images.evetech.net/types/{$parsedData['structureTypeID']}/icon"
        ));
    }

    /* ==================================================
           STRUCTURE LOST SHIELDS
    ================================================== */
    public static function structureLostShields($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $structureId = self::getStructureId($parsedData['structureID']);
        $structureName = DB::table('universe_structures')->where('structure_id', $structureId)->value('name');
        $exitTimer = Carbon::parse($notification->timestamp)->addSeconds($parsedData['timeLeft'] / 10000000)->format('Y-m-d H:i:s');

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$structureName} has lost its shields",
            "{$structureName} has lost its shields",
            "Exit Timer: {$exitTimer}\n \n{$notification->timestamp}",
            16711680,
            "https://images.evetech.net/types/{$parsedData['structureTypeID']}/icon"
        ));
    }

    /* ==================================================
           STRUCTURE LOST ARMOR
    ================================================== */
    public static function structureLostArmor($notification)
    {
        $parsedData = self::parseNotificationData($notification);
        $structureId = self::getStructureId($parsedData['structureID']);
        $structureName = DB::table('universe_structures')->where('structure_id', $structureId)->value('name');
        $exitTimer = Carbon::parse($notification->timestamp)->addSeconds($parsedData['timeLeft'] / 10000000)->format('Y-m-d H:i:s');

        return self::sendToDiscord(self::formatNotificationMessage(
            "{$structureName} has lost its armor",
            "{$structureName} has lost its armor",
            "Exit Timer: {$exitTimer}\n \n{$notification->timestamp}",
            16711680,
            "https://images.evetech.net/types/{$parsedData['structureTypeID']}/icon"
        ));
    }

    /* ==================================================
           FLEET ADD
    ================================================== */
    public function fleetAdd()
    {
        return self::sendToDiscord(self::formatNotificationMessage(
            "New fleet update from " . auth()->user()->name . "!",
            "Fleet Notification",
            "A new fleet update has been posted.",
            16711680
        ));
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
