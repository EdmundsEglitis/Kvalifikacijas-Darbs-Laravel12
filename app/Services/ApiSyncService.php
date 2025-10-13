<?php

namespace App\Services;
use Carbon\Carbon;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Models\NbaGame;
use App\Models\NbaStanding;
use App\Models\NbaPlayerGamelog;
use App\Services\NbaService;
use App\Jobs\SyncPlayerDetailJob;
use App\Jobs\SyncPlayerGamelogJob;
use Illuminate\Support\Facades\Log;

class ApiSyncService
{
    protected NbaService $nbaService;

    public function __construct(NbaService $nbaService)
    {
        $this->nbaService = $nbaService;
    }

    public function sync()
    {
        $this->syncTeams();
        $this->syncPlayers();
        $this->syncUpcomingGames();
        $this->syncPlayerDetails();
        $this->syncPlayerGamelogs();
        $this->syncStandingsRange(2021);
        // $this->syncGames(); 
    }

    protected function syncPlayers(): void
    {
        $allPlayers = $this->nbaService->allPlayersFromLoop();

        foreach ($allPlayers as $player) {
            
            NbaPlayer::updateOrCreate(
                        ['uid' => $player['uid']],
                        [
                        'external_id'   => $player['id'],
                        'guid'          => $player['guid'] ?? null,
                        'uid'           => $player['uid'],
                        'first_name'    => $player['firstName'],
                        'last_name'     => $player['lastName'],
                        'full_name'     => $player['fullName'],
                        'display_weight'=> $player['displayWeight'] ?? null,
                        'display_height'=> $player['displayHeight'] ?? null,
                        'age'           => $player['age'] ?? null,
                        'salary'        => $player['salary'] ?? null,
                        'image'         => $player['image'] ?? null,
                        'team_id'       => $player['teamId'] ?? null,
                        'team_name'     => $player['teamName'] ?? null,
                        'team_logo'     => $player['teamLogo'] ?? null,
                    ]
            );
        }
    }
    public function syncTeams(): void
        {
            $teams = $this->nbaService->allTeams();

                foreach ($teams as $team) {
                    NbaTeam::updateOrCreate(
                        ['external_id' => $team['id']],
                        [
                            'name'        => $team['name'],
                            'short_name'  => $team['shortName'] ?? null,
                            'abbreviation'=> $team['abbrev'] ?? null,
                            'logo'        => $team['logo'] ?? null,
                            'logo_dark'   => $team['logoDark'] ?? null,
                            'url'         => $team['href'] ?? null,
                        ]
                    );
                }
            }
            public function syncUpcomingGames(): void
            {
                set_time_limit(0);
                $games = $this->nbaService->upcomingGames();

                foreach ($games as $game) {
                    $tipoff = isset($game['tipoff']) ? Carbon::parse($game['tipoff'])->toDateTimeString() : null;

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
                }
            }
            public function syncPlayerDetails(): void
            {
                set_time_limit(0);
                \App\Models\NbaPlayer::chunk(10, function ($players) {
                    foreach ($players as $player) {
                        SyncPlayerDetailJob::dispatch($player->external_id);
                    }
                });
            }
public function syncPlayerGamelogs(): void
{
    \App\Models\NbaPlayer::chunk(200, function ($players) {
        SyncPlayerGamelogJob::dispatch($players->pluck('external_id')->toArray());
    });
}


public function testStandings(int $season = 2024): void
{
    $standings = $this->nbaService->standings($season);

    // Show top-level keys
    dump(array_keys($standings));

    // Show first entry
    dump($standings['entries'][0] ?? 'No entries found');

    dd("Entries count:", isset($standings['entries']) ? count($standings['entries']) : 0);
}











public function syncStandingsRange(int $from = 2021, ?int $to = null): void
{
    // Determine the “upcoming” season (e.g., 2024–25 uses 2025)
    // Rough rule: after August, consider next season the upcoming one.
    $year = (int) now()->year;
    $to = $to
        ?? ($this->isAfterOffseasonCutoff() ? $year + 1 : $year);

    for ($season = $from; $season <= $to; $season++) {
        $this->syncStandings($season);
    }
}

protected function isAfterOffseasonCutoff(): bool
{
    // NBA new season rolls in the fall; use Aug 1 as a simple cutoff.
    $now = now();
    return $now->month >= 8;
}

public function syncStandings(int $season): void
{
    $standings = $this->nbaService->standings($season);

    if (empty($standings) || empty($standings['entries']) || !is_array($standings['entries'])) {
        return;
    }

    foreach ($standings['entries'] as $entry) {
        $team  = $entry['team']  ?? [];
        $stats = $entry['stats'] ?? [];
        if (!$team || !$stats) continue;

        // Index stats by name for easy access
        $by = [];
        foreach ($stats as $s) {
            if (!empty($s['name'])) $by[$s['name']] = $s;
        }

        $num = static function ($s) {
            if (!is_array($s)) return null;
            if (isset($s['value']) && is_numeric($s['value'])) return $s['value'] + 0;
            foreach (['summary', 'displayValue'] as $k) {
                if (isset($s[$k]) && is_numeric($s[$k])) return $s[$k] + 0;
            }
            return null;
        };
        $str = static function ($s) {
            if (!is_array($s)) return null;
            foreach (['summary', 'displayValue'] as $k) {
                if (!empty($s[$k]) && is_string($s[$k])) return $s[$k];
            }
            return null;
        };

        // Streak -> integer (W2 => +2, L3 => -3)
        $streakInt = null;
        if (!empty($by['streak'])) {
            $disp = $by['streak']['displayValue'] ?? null;
            if (is_string($disp) && preg_match('/^[WL](\d+)/i', $disp, $m)) {
                $streakInt = (int) $m[1];
                if (stripos($disp, 'L') === 0) $streakInt = -$streakInt;
            } else {
                $streakInt = $num($by['streak']);
            }
        }

        // Games Behind: "-" => null
        $gbRaw = $by['gamesBehind']['displayValue'] ?? $by['gamesBehind']['value'] ?? null;
        $gamesBehind = (is_string($gbRaw) && trim($gbRaw) === '-') ? null : (is_numeric($gbRaw) ? $gbRaw + 0 : null);

        // Clincher symbol if present
        $clincher = $by['clincher']['displayValue']
            ?? $by['clincher']['summary']
            ?? (isset($by['clincher']['value']) ? (string) $by['clincher']['value'] : null);

        $payload = [
            'team_id'              => $team['id'] ?? null,
            'team_name'            => $team['displayName'] ?? null,
            'abbreviation'         => $team['abbreviation'] ?? null,

            'wins'                 => $num($by['wins'] ?? null),
            'losses'               => $num($by['losses'] ?? null),
            'win_percent'          => $num($by['winPercent'] ?? null),
            'playoff_seed'         => $num($by['playoffSeed'] ?? null),
            'games_behind'         => $gamesBehind,

            'avg_points_for'       => $num($by['avgPointsFor'] ?? null),
            'avg_points_against'   => $num($by['avgPointsAgainst'] ?? null),
            'point_differential'   => $num($by['pointDifferential'] ?? null),

            'points'               => $num($by['points'] ?? null),
            'points_for'           => $num($by['pointsFor'] ?? null),
            'points_against'       => $num($by['pointsAgainst'] ?? null),
            'division_win_percent' => $num($by['divisionWinPercent'] ?? null),
            'league_win_percent'   => $num($by['leagueWinPercent'] ?? null),

            'streak'               => $streakInt,
            'clincher'             => $clincher,

            'league_standings'     => $str($by['League Standings'] ?? null),
            'home_record'          => $str($by['Home'] ?? null),
            'road_record'          => $str($by['Road'] ?? null),
            'division_record'      => $str($by['vs. Div.'] ?? null),
            'conference_record'    => $str($by['vs. Conf.'] ?? null),
            'last_ten'             => $str($by['Last Ten Games'] ?? null),

            'season'               => $season,
        ];

        NbaStanding::updateOrCreate(
            ['team_id' => $payload['team_id'], 'season' => $season],
            $payload
        );
    }
}            


        }
