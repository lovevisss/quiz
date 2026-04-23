<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LeaderboardCleanup extends Command
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
        // Logic for cleaning up old leaderboard data
        $this->info('Old leaderboard data cleaned up successfully.');
        return Command::SUCCESS;
    }
}