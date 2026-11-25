<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class CleanupExpiredRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rooms:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired temporary chat rooms and all of their data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = Room::query()->expired()->delete();

        $this->info("Deleted {$deleted} expired room(s).");

        return self::SUCCESS;
    }
}
