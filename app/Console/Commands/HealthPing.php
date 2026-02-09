<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HealthPing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick health check for Docker healthcheck';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Quick check - just verify Laravel can bootstrap
        $this->info('OK');
        
        return Command::SUCCESS;
    }
}
