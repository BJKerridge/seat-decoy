<?php

namespace BJK\Decoy\Seat\Commands;

use Illuminate\Console\Command;
use BJK\Decoy\Seat\Jobs\ImportPilotKillData;

class ImportPilotKillDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decoy:pilot-kill-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh pilot kills and associated IDs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Refreshing Pilot Kills + IDs...');

        // Dispatch the job that handles the actual data import
        ImportPilotKillData::dispatch();

        $this->info('Pilot kill data import started.');
    }
}
