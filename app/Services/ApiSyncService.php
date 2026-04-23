<?php

namespace App\Services;

use App\Jobs\SyncTeamsJob;
use App\Jobs\SyncPlayersJob;
use App\Jobs\SyncUpcomingGamesJob;
use App\Jobs\SyncPlayerDetailJob;
use App\Jobs\SyncPlayerGamelogJob;
use App\Jobs\SyncStandingsRangeJob;
use App\Models\NbaPlayer;

class ApiSyncService
{
    public function sync(): void
    {
        $this->syncTeams();
        //$this->syncPlayers();   
        //$this->syncUpcomingGames();
        //$this->syncPlayerDetails();
        //$this->syncPlayerGamelogs();
        //$this->syncStandingsRange(2021);
    }

    public function syncTeams(): void
    {
        SyncTeamsJob::dispatch();
    }

    public function syncPlayers(): void
    {
        $nbaService = app(\App\Services\NbaService::class);
        $teams = $nbaService->allTeams();

        $delay = 0;

        foreach ($teams as $teamId => $team) {
            SyncPlayersJob::dispatch($teamId, $team)
                ->delay(now()->addSeconds($delay));

            $delay += 2;
        }
    }

    public function syncUpcomingGames(): void
    {
        SyncUpcomingGamesJob::dispatch();
    }

    public function syncPlayerDetails(): void
    {
        $delay = 0;

        NbaPlayer::chunk(100, function ($players) use (&$delay) {
            foreach ($players as $player) {
                SyncPlayerDetailJob::dispatch($player->external_id)
                    ->delay(now()->addSeconds($delay));

                $delay += 2;
            }
        });
    }

    public function syncPlayerGamelogs(): void
    {
        $delay = 0;

        NbaPlayer::chunk(20, function ($players) use (&$delay) {
            SyncPlayerGamelogJob::dispatch(
                $players->pluck('external_id')->toArray()
            )->delay(now()->addSeconds($delay));

            $delay += 5;
        });
    }

    public function syncStandingsRange(int $from = 2021, ?int $to = null): void
    {
        SyncStandingsRangeJob::dispatch($from, $to);
    }
}