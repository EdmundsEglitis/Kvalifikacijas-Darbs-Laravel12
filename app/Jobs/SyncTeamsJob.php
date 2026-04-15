<?php

namespace App\Jobs;

use App\Models\NbaTeam;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTeamsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NbaService $nbaService): void
    {
        $teams = $nbaService->allTeams();

        foreach ($teams as $team) {
            NbaTeam::updateOrCreate(
                ['external_id' => $team['id']],
                [
                    'name'         => $team['name'],
                    'short_name'   => $team['shortName'] ?? null,
                    'abbreviation' => $team['abbrev'] ?? null,
                    'logo'         => $team['logo'] ?? null,
                    'logo_dark'    => $team['logoDark'] ?? null,
                    'url'          => $team['href'] ?? null,
                ]
            );

            usleep(900000);
        }
    }
}