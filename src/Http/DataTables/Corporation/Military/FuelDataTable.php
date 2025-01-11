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

namespace BJK\Decoy\Seat\Http\DataTables\Corporation\Military;

use Illuminate\Http\JsonResponse;
use Seat\Eveapi\Models\Corporation\CorporationStructure;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

/**
 * Class StructureDataTable.
 *
 * @package BJK\Decoy\Seat\Http\DataTables\Corporation\Military
 */
class FuelDataTable extends DataTable
{
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function ajax(): JsonResponse
{
    // Initialize empty arrays
    $ansiblex = [];
    $metenox = [];

    $query = $this->applyScopes($this->query());

    // Loop through each row in the query results
    foreach ($query->get() as $row) {
        // Check typeID and populate the corresponding array
        if ($row->type->typeID == 35841) {
            $ansiblex[] = $row->info->structure_id;
        } elseif ($row->type->typeID == 81826) {
            $metenox[] = $row->info->structure_id;
        }
    }

    // You can now pass the arrays to the view if needed, or you can return them as part of the response
    return datatables()
        ->eloquent($query->whereNotIn('type_id', [35841, 81826]))
        ->editColumn('type.typeName', function ($row) {
            $first_word = strtok($row->type->typeName, ' ');
            return view('web::partials.type', [
                'type_id' => $row->type->typeID,
                'type_name' => $first_word
            ])->render();
        })
        ->editColumn('state', function ($row) {
            return ucfirst(str_replace('_', ' ', $row->state));
        })
        ->editColumn('fuel_expires', function ($row) {
            if ($row->fuel_expires)
                return \Carbon\Carbon::parse($row->fuel_expires)->format('Y-m-d H:i');
            return trans('web::seat.low_power');
        })
        ->editColumn('reinforce_hour', function ($row) {
            return view('web::corporation.structures.partials.reinforcement', compact('row'))->render();
        })
        ->editColumn('services', function ($row) {
            return view('web::corporation.structures.partials.services', compact('row'))->render();
        })
        ->rawColumns(['type.typeName', 'fuel_expires', 'title'])
        ->with('ansiblex', $ansiblex) // You can pass the array to the view or respond with it
        ->with('metenox', $metenox)   // Similarly, pass the second array to the view or respond with it
        ->toJson();
}

    /**
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->postAjax()
            ->columns($this->getColumns())
            ->addTableClass('table-striped table-hover')
            ->parameters([
                'drawCallback' => 'function(settings) { 
                // Tooltip initialization
                $("[data-toggle=tooltip]").tooltip(); 
                
                // Row callback for adding table-warning or table-danger
                this.api().rows().every(function() {
                    var data = this.data();
                    var row = this.node();
                    var dangerClass = ""; 
                    var warningClass = "";
                    
                    // Check if fuel_expires is within a certain range
                    if (data.fuel_expires) {
                        var fuelDate = new Date(data.fuel_expires);
                        var now = new Date();
                        var diffInDays = (fuelDate - now) / (1000 * 60 * 60 * 24);
                        if (diffInDays <= 7) {
                            dangerClass = "table-danger"; // Add danger class if within 7 days
                        } else if (diffInDays <= 14) {
                            warningClass = "table-warning"; // Add warning class if within 14 days
                        }
                    }

                    // Apply the classes if conditions are met
                    if (dangerClass) {
                        $(row).addClass(dangerClass);
                    } else if (warningClass) {
                        $(row).addClass(warningClass);
                    }
                });
            }',
                'lengthChange' => false, // Disable the 'Show X entries' dropdown
                'order' => [[2, 'asc']],  // Default order by first column (index 0) ascending
                'paging' => false,
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return CorporationStructure::with('info', 'type', 'solar_system', 'services')->where('corporation_id', 98405234);
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return [
            ['data' => 'type.typeName', 'title' => trans_choice('web::seat.type', 1)],
            ['data' => 'info.name', 'title' => trans_choice('web::seat.name', 1)],
            ['data' => 'fuel_expires', 'title' => trans('web::seat.offline')],
        ];
    }
}
