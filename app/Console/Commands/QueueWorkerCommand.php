<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueWorkerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:school {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queue worker for a specific database connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->argument('connection');

        if (!config("database.connections.$connection")) {
            $this->error("Database connection {$connection} not found.");
            return 1;
        }

        config(['database.default' => $connection]);

        DB::purge($connection);
        DB::reconnect($connection);

        $this->info("Running queue worker for {$connection}");

        Artisan::call('queue:work', [
            '--sleep' => 3,
            '--tries' => 3,
            '--timeout' => 900,
        ]);
    }
}
