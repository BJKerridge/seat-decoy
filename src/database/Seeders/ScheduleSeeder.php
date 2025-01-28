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

namespace BJK\Decoy\Seat\database\Seeders;

use Seat\Services\Seeding\AbstractScheduleSeeder;
use BJK\Decoy\Seat\Jobs\ImportZKillData;
use BJK\Decoy\Seat\Jobs\ImportPilotKillData;
use BJK\Decoy\Seat\Jobs\ImportUserInfo;
use BJK\Decoy\Seat\Jobs\ImportDecoyNotifications;

/**
 * Class ScheduleSeeder.
 *
 * @package Seat\Web\database\seeds
 */
class ScheduleSeeder extends AbstractScheduleSeeder
{
    /**
     * Returns an array of schedules that should be seeded whenever the stack boots up.
     *
     * @return array
     */
    public function getSchedules(): array
    {
        return [
            [   // DECOY Update info from zKillboard | Every Five Minutes
                'command' => 'decoy:zkill-data',
                'expression' => '*/5 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            [   // DECOY Update pilot zkill data from internal tables | Every Five Minutes
                'command' => 'decoy:pilot-kill-data',
                'expression' => '*/5 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            [   // DECOY Update user homepage | Every Thirty Minutes
                'command' => 'decoy:update-user-homepage',
                'expression' => '*/30 * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
            [   // DECOY Notification Tool | Every Minute
                'command' => 'decoy:update-notifications',
                'expression' => '* * * * *',
                'allow_overlap' => false,
                'allow_maintenance' => false,
                'ping_before' => null,
                'ping_after' => null,
            ],
        ];
    }

    /**
     * Returns a list of commands to remove from the schedule.
     *
     * @return array
     */
    public function getDeprecatedSchedules(): array
    {
        return [];
    }
}