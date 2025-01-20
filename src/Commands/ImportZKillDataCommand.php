<?php

namespace BJK\Decoy\Seat\Commands;

use Illuminate\Console\Command;
use BJK\Decoy\Seat\Jobs\ImportZKillData;

class ImportZKillDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decoy:zkill-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ZKillboard data for alliances';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Importing ZKillboard data...');

        // Dispatch the job that handles the actual data import
        ImportZKillData::dispatch();

        $this->info('ZKillboard data import started.');
    }
}
