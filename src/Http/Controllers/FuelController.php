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
use Seat\Web\Http\Controllers\Controller;
use BJK\Decoy\Seat\Http\DataTables\Corporation\Military\FuelDataTable;
use Seat\Eveapi\Models\Assets\CorporationAsset;
use Seat\Eveapi\Models\Corporation\CorporationStarbase;
use Seat\Web\Models\UniverseMoonReport;
use Seat\Eveapi\Models\Sde\Moon;
use Seat\Eveapi\Models\Corporation\CorporationStarbaseFuel;
use Seat\Eveapi\Models\Sde\InvControlTowerResource;

/**
 * Class HomeController.
 *
 * @package BJK\Decoy\Seat\Http\Controllers
 */
class FuelController extends Controller
{
     /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('decoy::decoyFuel');
    }

    public function getFuel(FuelDataTable $dataTable)
    {
    
    $ansiblex = [];
    $metenox = [];
    $ansiblexTableData = [];
    $metenoxTableData = [];
    $query = $dataTable->query(); // Get the query used in the DataTable 
    $starbases = CorporationStarbase::where('corporation_id', 98405234)->get(); //1047113642980
    //$starbases2 = CorporationStarbase::where('starbase_id', 1047113642980)->get();
    $starbases2 = InvControlTowerResource::where('controlTowerTypeID', 20066)->where('resourceTypeID', 4246)->pluck('quantity');
    $query2 = [];
    $results = [];
    $starbaseList = [];

    foreach ($starbases as $starbase) {
        $result2 = [
         'POSType' => $starbase->type_id,
         'Location' => Moon::find($starbase->moon_id)->name,
         //'OnlineSince' => Moon::find($starbase->moon_id)->name,
         //'OnlineSince' => CorporationStarbaseFuel::where('starbase_id', $starbase->starbase_id)->where('type_id', 4246)->pluck('quantity'),
         //'OnlineSince' => InvControlTowerResource::where('controlTowerTypeID', $starbase->type_id)->whereBetween('resourceTypeID', [4000, 5000])->pluck('quantity'),
         'FueledUntil' => $starbase->state === 'online'
    ? Carbon::now()
        ->addHours(
            (CorporationStarbaseFuel::where('starbase_id', $starbase->starbase_id)
                ->where('type_id', 4246)
                ->pluck('quantity')
                ->first() ?? 0) / 
            (InvControlTowerResource::where('controlTowerTypeID', $starbase->type_id)
                ->whereBetween('resourceTypeID', [4000, 5000])
                ->pluck('quantity')
                ->first() ?? 1)
        )
        ->format('Y-m-d H:i')
    : 'Offline',
         //'OnlineSince' => (CorporationStarbaseFuel::where('starbase_id', $starbase->starbase_id)->where('type_id', 4246)->pluck('quantity')->first()) / (InvControlTowerResource::where('controlTowerTypeID', $starbase->type_id)->whereBetween('resourceTypeID', [4000, 5000])->pluck('quantity')->first() ?? 1),
         'UnanchorAt' => CorporationStarbaseFuel::where('starbase_id', $starbase->starbase_id)->where('type_id', 16275)->pluck('quantity'),
        ];
        $starbaseList[] = $result2;
    }

    foreach ($query->get() as $row) {
        if ($row->type->typeID == 35841) {
            $result = [
                'StructureID' => $row->info->structure_id,      // Rename 'id' to 'StructureID'
                'StructureType' => $row->type->typeID,      // Rename 'id' to 'StructureID'
                'StructureName' => $row->info->name,  // Rename 'name' to 'StructureName'
                'FueledUntil' => Carbon::parse($row->fuel_expires)->format('Y-m-d H:i'),  // Rename 'name' to 'StructureName'
                'quantity' => number_format(CorporationAsset::whereHas('structure', function ($query) use ($row) {$query->where('structure_id', $row->info->structure_id);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->pluck('quantity')->implode(', '),0,".",","),
            ];
            $results[] = $result; // Append the result to the results array
            } elseif ($row->type->typeID == 81826) {
            $result = [
                'StructureID' => $row->info->structure_id,      // Rename 'id' to 'StructureID'
                'StructureType' => $row->type->typeID,      // Rename 'id' to 'StructureID'
                'StructureName' => $row->info->name,  // Rename 'name' to 'StructureName'
                'FueledUntil' => Carbon::parse($row->fuel_expires)->format('Y-m-d H:i'),  // Rename 'name' to 'StructureName'
                'quantity' => number_format(CorporationAsset::whereHas('structure', function ($query) use ($row) {$query->where('structure_id', $row->info->structure_id);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->pluck('quantity')->implode(', '),0,".",","),

            ];
            $results[] = $result; // Append the result to the results array
        }
    }

    $desiredTypeIDs = [81143, 16273];
    $structureId = 1046235177199; // Replace with the actual structure_id you're looking for

    //$assets = CorporationAsset::whereHas('structure', function ($query) use ($structureId){$query->where('structure_id', $structureId);})->with('type')->get();
    $assets = CorporationAsset::whereHas('structure', function ($query) use ($structureId){$query->where('structure_id', $structureId);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->with('type')->get()->pluck('quantity')->implode(', ');
    //$jsonOut = json_encode($results, JSON_PRETTY_PRINT);
    $jsonOut = $results;
    // Return the DataTable view along with the arrays
    return $dataTable->render('decoy::decoyFuel', compact('dataTable', 'jsonOut', 'starbaseList', 'starbases2'));
    }
}
