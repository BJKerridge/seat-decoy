<?php

namespace BJK\Decoy\Seat\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DecoyNotificationController extends Controller
{
    private static $webhookUrl = env('DISCORD_NOTIFICATION_CHANNEL', '');

    /* ==================================================
           SEND THE NOTIFICATION TO DISCORD
    ================================================== */
    private static function sendToDiscord(array $message)
    {
        $response = Http::post(self::$webhookUrl, $message);

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
}
