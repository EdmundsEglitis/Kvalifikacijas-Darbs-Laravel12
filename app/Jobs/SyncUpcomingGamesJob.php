<?php

namespace App\Jobs;

use App\Models\NbaGame;
use App\Services\NbaService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncUpcomingGamesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NbaService $nbaService): void
    {
        $games = $nbaService->upcomingGames();

        foreach ($games as $game) {
            $tipoff = isset($game['tipoff'])
                ? Carbon::parse($game['tipoff'])->toDateTimeString()
                : null;

            NbaGame::updateOrCreate(
                ['external_id' => $game['id']],
                [
                    'schedule_date'   => $game['scheduleDate'] ?? null,
                    'tipoff'          => $tipoff,
                    'status'          => $game['status'] ?? null,
                    'venue'           => $game['venue'] ?? null,
                    'city'            => $game['city'] ?? null,
                    'home_team_id'    => $game['homeTeam']['id'] ?? null,
                    'home_team_name'  => $game['homeTeam']['name'] ?? null,
                    'home_team_short' => $game['homeTeam']['short'] ?? null,
                    'home_team_logo'  => $game['homeTeam']['logo'] ?? null,
                    'away_team_id'    => $game['awayTeam']['id'] ?? null,
                    'away_team_name'  => $game['awayTeam']['name'] ?? null,
                    'away_team_short' => $game['awayTeam']['short'] ?? null,
                    'away_team_logo'  => $game['awayTeam']['logo'] ?? null,
                ]
            );

            usleep(300000);
        }
    }
}