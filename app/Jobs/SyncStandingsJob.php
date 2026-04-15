<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\NbaService;

class SyncStandingsJob implements ShouldQueue
{
    public function __construct(public int $season) {}

    public function handle(NbaService $nbaService): void
    {
        $standings = $nbaService->standings($this->season);

        if (empty($standings['entries'])) {
            return;
        }

        foreach ($standings['entries'] as $entry) {
            // your existing mapping logic here
        }
    }
}
