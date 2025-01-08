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
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use BJK\Decoy\Seat\Http\DataTables\Corporation\Military\FuelDataTable;
use Seat\Eveapi\Models\Assets\CorporationAsset;

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

    public function getFuel(CorporationInfo $corporation, FuelDataTable $dataTable)
{
    // Initialize empty arrays
    $ansiblex = [];
    $metenox = [];
    $ansiblexTableData = [];
    $metenoxTableData = [];

    // Get the data from the query
    $query = $dataTable->query(); // Get the query used in the DataTable

    // Loop through each row in the query results
    $results = [];

    foreach ($query->get() as $row) {
    if ($row->type->typeID == 35841) {
        $result = [
            'StructureID' => $row->info->structure_id,      // Rename 'id' to 'StructureID'
            'StructureType' => $row->type->typeID,      // Rename 'id' to 'StructureID'
            'StructureName' => $row->info->name,  // Rename 'name' to 'StructureName'
            'quantity' => CorporationAsset::whereHas('structure', function ($query) use ($row) {$query->where('structure_id', $row->info->structure_id);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->pluck('quantity')->implode(', '),
        ];
        $results[] = $result; // Append the result to the results array
    } elseif ($row->type->typeID == 81826) {
        $result = [
            'StructureID' => $row->info->structure_id,      // Rename 'id' to 'StructureID'
            'StructureType' => $row->type->typeID,      // Rename 'id' to 'StructureID'
            'StructureName' => $row->info->name,  // Rename 'name' to 'StructureName'
            'quantity' => CorporationAsset::whereHas('structure', function ($query) use ($row) {$query->where('structure_id', $row->info->structure_id);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->pluck('quantity')->implode(', '),
            'Fueled' => Carbon::now()->addHours(floor(CorporationAsset::whereHas('structure', function ($query) use ($row) {$query->where('structure_id', $row->info->structure_id);})->whereHas('type', function ($query) {$query->whereIn('typeID', [81143, 16273]);})->pluck('quantity')->implode(', ')/110))->format('Y-m-d H:i'),

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
    return $dataTable->render('decoy::decoyFuel', compact('dataTable', 'jsonOut'));

}
}
