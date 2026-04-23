<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupLeaderboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old leaderboard data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Logic to clean up leaderboard
        $this->info('Old leaderboard data cleaned up successfully.');
        return Command::SUCCESS;
    }
}