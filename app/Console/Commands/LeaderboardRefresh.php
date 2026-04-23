<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LeaderboardRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the leaderboard data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Logic for refreshing leaderboard data
        $this->info('Leaderboard refreshed successfully.');
        return Command::SUCCESS;
    }
}