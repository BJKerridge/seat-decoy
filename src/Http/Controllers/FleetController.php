<?php

namespace BJK\Decoy\Seat\Http\Controllers;

use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use BJK\Decoy\Seat\Models\Fleet;
use Seat\Eveapi\Models\Character\CharacterInfo;

/**
 * Class FleetController.
 *
 * @package BJK\Decoy\Seat\Http\Controllers
 */
class FleetController extends Controller
{
    /**
     * Show the list of fleets.
     *
     * @return \Illuminate\View\View
     */
    public function getFleets()
    {
        $fleets = Fleet::orderBy('fleet_time', 'asc')->get();
        $debug = $fleets;
        return view('decoy::decoyFleets', compact('debug', 'fleets'));
    }

    
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'fleet_time' => 'required|date',
            'duration' => 'required|string',
            'fleet_comp' => 'required|string',
            'fleet_name' => 'required|string',
            'formup_location' => 'required|string',
            'importance' => 'required|integer',
            'message' => 'required|string',
            'fleet_members' => 'nullable|string',
        ]);
    
        // Handle fleet_members logic
        $fleetMembersData = $this->parseFleetMembers($request->input('fleet_members'));
    
        // Add fleet_members to the validated data
        $validated['fleet_members'] = json_encode($fleetMembersData);
    
        // Create the fleet record in the database
        Fleet::create($validated);
    
        return redirect()->route('decoy::decoyFleets')->with('success', 'Fleet created successfully!');
    }

    /**
 * Parse the fleet members based on the given input.
 * 
 * @param string $fleetMembers
 * @return array
 */
private function parseFleetMembers($fleetMembers)
{
    // Initialize the array to hold the fleet members data
    $fleetMembersData = [];

    // Check if the input is in JSON format
    $decodedMembers = json_decode($fleetMembers, true);

    // If it's a valid JSON, parse each member and return it
    if (json_last_error() === JSON_ERROR_NONE) {
        foreach ($decodedMembers as $member) {
            // Ensure each member has 'user_name' and 'ship_name' keys
            if (isset($member['user_name']) && isset($member['ship_name'])) {
                $shipName = $member['ship_name'];

                // Search for the ship type by its name (we can also extend this logic to fetch ship ID if needed)
                $ship = \DB::table('invTypes')
                    ->where('typeName', $shipName)
                    ->first();
                
                if ($ship) {
                    $shipName = $ship->typeName;
                } else {
                    $shipName = ''; // If the ship name is not found, we keep it empty
                }

                // Retrieve character information
                $character = CharacterInfo::where('name', $member['user_name'])->first();

                if ($character) {
                    $fleetMembersData[] = [
                        'character_id' => $character->character_id,
                        'name' => $character->name,
                        'ship' => $shipName, // Use the ship name or an empty string
                    ];
                }
            }
        }
    } else {
        // Handle the original non-JSON format (you can retain this section if necessary)
        $lines = explode("\n", $fleetMembers);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode("\t", $line);

            if (count($parts) === 1) {
                // Case 1: List of names
                $name = $parts[0];
                $character = CharacterInfo::where('name', $name)->first();

                if ($character) {
                    $fleetMembersData[] = [
                        'character_id' => $character->character_id,
                        'name' => $character->name,
                        'ship' => '', // No ship info in the name list case
                    ];
                }
            } elseif (count($parts) >= 4) {
                // Case 2: Fleet composition (username, system, ship type, ship class, etc.)
                $name = $parts[0];
                $shipClass = $parts[2];

                // Find the ship type by name and validate
                $ship = \DB::table('invTypes')
                    ->where('typeName', $shipClass)
                    ->first();

                $shipName = $ship ? $ship->typeName : ''; // Get the ship name, or empty string if not found

                $character = CharacterInfo::where('name', $name)->first();

                if ($character) {
                    $fleetMembersData[] = [
                        'character_id' => $character->character_id,
                        'name' => $character->name,
                        'ship' => $shipName, // Save the ship name
                    ];
                }
            }
        }
    }

    return $fleetMembersData;
}


public function destroy($id)
{
    $fleet = Fleet::findOrFail($id);
    $fleet->delete();

    return redirect()->route('decoy::decoyFleets')->with('success', 'Fleet deleted successfully');
}

public function update(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'fleet_id' => 'required|exists:decoy_fleets,id',
        'fleet_name' => 'required|string|max:255',
        'fleet_time' => 'required|date',
        'fleet_comp' => 'required|string',
        'formup_location' => 'required|string',
        'importance' => 'required|integer',
        'message' => 'required|string',
        'fleet_members' => 'nullable|string',
    ]);

    // Parse the fleet members with the same logic used in store
    $fleetMembersData = $this->parseFleetMembers($request->input('fleet_members'));

    // Find the fleet by ID and update the data
    $fleet = Fleet::findOrFail($request->fleet_id);
    $fleet->update([
        'fleet_name' => $request->fleet_name,
        'fleet_time' => $request->fleet_time,
        'fleet_comp' => $request->fleet_comp,
        'formup_location' => $request->formup_location,
        'importance' => $request->importance,
        'message' => $request->message,
        'fleet_members' => json_encode($fleetMembersData), // Store the parsed fleet members
    ]);

    return redirect()->route('decoy::decoyFleets')->with('success', 'Fleet updated successfully.');
}

    /**
     * Create the fleets table if it doesn't exist.
     *
     * @return void
     */
    protected function createFleetTableIfNeeded()
    {
        if (!Schema::hasTable('decoy_fleets')) {
            Schema::create('decoy_fleets', function (Blueprint $table) {
                $table->id();
                $table->timestamp('fleet_time')->nullable(false);
                $table->text('duration');
                $table->text('fleet_comp');
                $table->text('fleet_name');
                $table->text('formup_location');
                $table->integer('importance');
                $table->text('message');
                $table->longText('fleet_members');
                $table->timestamps();
            });
        }
    }
}
