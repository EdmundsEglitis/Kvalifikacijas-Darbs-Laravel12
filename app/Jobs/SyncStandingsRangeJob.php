<?php

namespace App\Jobs;

use App\Models\NbaStanding;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncStandingsRangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $from = 2021,
        public ?int $to = null
    ) {}

    public function handle(NbaService $nbaService): void
    {
        $to = $this->to ?? now()->year;

        for ($season = $this->from; $season <= $to; $season++) {
            $standings = $nbaService->standings($season);

            foreach ($standings['entries'] ?? [] as $entry) {
                NbaStanding::updateOrCreate(
                    [
                        'team_id' => $entry['team']['id'] ?? null,
                        'season'  => $season,
                    ],
                    [
                        'wins' => $entry['stats'][0]['value'] ?? null,
                    ]
                );
            }

            usleep(500000);
        }
    }
}