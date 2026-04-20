<?php

namespace App\Services;
use App\Models\NbaPlayerGameLog;
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
        $this->syncPlayers();
        $this->syncUpcomingGames();
        $this->syncPlayerDetails();
        $this->syncPlayerGamelogs();
        $this->syncStandingsRange(2021);
    }

    public function syncTeams(): void
    {
        cache()->increment('total');
        SyncTeamsJob::dispatch();
    }

    public function syncPlayers(): void
    {
        $nbaService = app(\App\Services\NbaService::class);
        $teams = $nbaService->allTeams();

        $delay = 0;

        foreach ($teams as $teamId => $team) {
            cache()->increment('total');
            SyncPlayersJob::dispatch($teamId, $team)
                ->delay(now()->addSeconds($delay));

            $delay += 2;
        }
    }

    public function syncUpcomingGames(): void
    {
        cache()->increment('total');
        SyncUpcomingGamesJob::dispatch();
    }

    public function syncPlayerDetails(): void
    {
        $delay = 0;

        NbaPlayer::chunk(100, function ($players) use (&$delay) {
            foreach ($players as $player) {
                cache()->increment('total');
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
            cache()->increment('total');
            SyncPlayerGamelogJob::dispatch(
                $players->pluck('external_id')->toArray()
            )->delay(now()->addSeconds($delay));

            $delay += 5;
        });
    }

    public function syncStandingsRange(int $from = 2021, ?int $to = null): void
    {
        cache()->increment('total');
        SyncStandingsRangeJob::dispatch($from, $to);
    }
}