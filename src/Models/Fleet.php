<?php

namespace BJK\Decoy\Seat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    use HasFactory;

    // Define the table name if it's not the plural of the model name
    protected $table = 'decoy_fleets';

    // Define the attributes that can be mass-assigned
    protected $fillable = [
        'fleet_time',
        'duration',
        'fleet_comp',
        'fleet_name',
        'formup_location',
        'importance',
        'message',
        'fleet_members',
    ];

    // Optionally, you can define any date-related fields (e.g., for carbon date handling)
    protected $dates = [
        'fleet_time',
    ];
}