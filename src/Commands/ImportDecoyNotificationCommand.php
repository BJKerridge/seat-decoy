<?php

namespace BJK\Decoy\Seat\Commands;

use Illuminate\Console\Command;
use BJK\Decoy\Seat\Jobs\ImportDecoyNotifications;

class ImportDecoyNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decoy:update-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Decoy-Specific Notifications';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Importing Notifications...');

        // Dispatch the job that handles the actual data import
        ImportDecoyNotifications::dispatch();

        $this->info('Notifications processed');
    }
}
