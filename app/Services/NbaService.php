<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\NbaPlayer;
use Carbon\Carbon;
class NbaService
{
    protected string $baseUri;
    protected string $key;
    protected int $timeout;

    public function __construct()
    {
        $this->key = config('nba.key');
        $this->baseUri = config('nba.base_uri');
        $this->timeout = config('nba.timeout');

        if (empty($this->key)) {
            throw new \Exception("NBA API key is missing. Check your .env and config/nba.php.");
        }
    }

    protected function request(string $endpoint, array $params = [])
    {
        $attempts = 0;
        $maxAttempts = 5;
        $delaySeconds = 2;

        while ($attempts < $maxAttempts) {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-rapidapi-key' => $this->key,
                    'x-rapidapi-host' => parse_url($this->baseUri, PHP_URL_HOST),
                ])
                ->get("{$this->baseUri}{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            $status = $response->status();

            // 🔥 Handle rate limits / temporary errors
            if (in_array($status, [429, 503])) {
                $attempts++;

                sleep($delaySeconds);

                // exponential backoff (2 → 4 → 8 → 16...)
                $delaySeconds *= 2;

                continue;
            }

            // ❌ Other errors → real failure
            throw new \Exception(
                "NBA API request failed: {$status} " . $response->body()
            );
        }

        // 🚨 After retries, don't kill everything — return empty
        return [
            'response' => []
        ];
    }

    public function allTeams(): array
    {
        return Cache::remember('nba:teams', 3600, function () {
            $endpoints = [
                '/nba-atlantic-team-list',
                '/nba-central-team-list',
                '/nba-southeast-team-list',
                '/nba-northwest-team-list',
                '/nba-pacific-team-list',
                '/nba-southwest-team-list',
            ];

            $teams = [];
            foreach ($endpoints as $endpoint) {
                $response = $this->request($endpoint);
                foreach ($response['response']['teamList'] ?? [] as $team) {
                    if (!empty($team['id'])) {
                        $teams[$team['id']] = $team;
                    }
                    usleep(300000);
                }
                usleep(300000);
            }
            return $teams;
            
        });
    }

    public function playersByTeam(string|int $teamId): array
    {
        return Cache::remember("nba:players:team:{$teamId}", 1800, function () use ($teamId) {
            $response = $this->request('/nba-player-list', ['teamid' => (string) $teamId]);
            $players = $response['response']['PlayerList'] ?? $response['response']['playerList'] ?? [];
            return is_array($players) ? $players : [];
        });
    }

    public function allPlayersFromLoop(): array
    {
        $teams = $this->allTeams();
        $all = [];

        foreach ($teams as $teamId => $team) {

            $teamPlayers = $this->playersByTeam($teamId);

            foreach ($teamPlayers as &$p) {
                $p['teamId'] = (string) $teamId;
                $p['teamName'] = $team['name'] ?? 'Unknown';
                $p['teamLogo'] = $team['logo'] ?? null;
            }

            $all = array_merge($all, $teamPlayers);
        }

        return $all;
    }

    public function upcomingGames(): array
    {
        $start = Carbon::today();
        $end   = $start->copy()->adddays(20);
    
        $allGames = [];
    
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            $end->copy()->addDay()
        );
    
        foreach ($period as $date) {
            $ymd = $date->format('Ymd');
    
            $dayBlocks = Cache::remember("nba:schedule:$ymd", 3600, function () use ($ymd) {
                $response = $this->request('/nba-schedule-by-date', ['date' => $ymd]);
                return $response['response'] ?? [];
            });
             usleep(300000);
    
            if (empty($dayBlocks)) {
                continue;
            }
    
            foreach ($dayBlocks as $dayBlock) {
                if (!is_array($dayBlock)) {
                    continue;
                }
    
                $dayLabel = $dayBlock['scheduleDate'] ?? $ymd;
    
                foreach ($dayBlock as $key => $game) {
                    if (!is_int($key) || !is_array($game)) {
                        continue;
                    }
    
                    if (empty($game['competitors'])) {
                        continue;
                    }
    
                    $home = null;
                    $away = null;
                    
                    foreach ($game['competitors'] as $team) {
                        if (!empty($team['isHome']) && $team['isHome'] === true) {
                            $home = $team;
                        } else {
                            $away = $team;
                        }
                    }
                    
                    $allGames[] = [
                        'id'          => $game['id'] ?? null,
                        'scheduleDate'=> $dayLabel,
                        'tipoff'      => $game['date'] ?? null,
                        'status'      => $game['status']['detail'] ?? '',
                        'venue'       => $game['venue']['fullName'] ?? '',
                        'city'        => $game['venue']['address']['city'] ?? '',
                        'homeTeam'    => [
                            'id'    => $home['id'] ?? null,
                            'name'  => $home['displayName'] ?? '',
                            'short' => $home['shortDisplayName'] ?? '',
                            'logo'  => $home['logo'] ?? '',
                        ],
                        'awayTeam'    => [
                            'id'    => $away['id'] ?? null,
                            'name'  => $away['displayName'] ?? '',
                            'short' => $away['shortDisplayName'] ?? '',
                            'logo'  => $away['logo'] ?? '',
                        ],
                    ];
                    
                }
            }
        }

    
        return $allGames;
    }
    public function playerInfo(string|int $playerId): array
    {
        set_time_limit(0);
        $response = $this->request('/nba-player-info', [
            'playerid' => (string) $playerId,
        ]);
    
        return $response['response']['athlete'] ?? [];
    }

public function allPlayersInfoForCron(): array
{
    set_time_limit(0);
    $playersData = [];

    \App\Models\NbaPlayer::chunk(10, function ($allPlayers) use (&$playersData) {
        foreach ($allPlayers as $player) {
            
            $athlete = $this->playerInfo($player->external_id);
            if (!empty($athlete)) {
                $playersData[] = [
                    'external_id'      => $athlete['id'] ?? null,
                    'uid'              => $athlete['uid'] ?? null,
                    'guid'             => $athlete['guid'] ?? null,
                    'type'             => $athlete['type'] ?? null,
                    'first_name'       => $athlete['firstName'] ?? null,
                    'last_name'        => $athlete['lastName'] ?? null,
                    'full_name'        => $athlete['fullName'] ?? null,
                    'display_name'     => $athlete['displayName'] ?? null,
                    'jersey'           => $athlete['jersey'] ?? null,
                    'links'            => $athlete['links'] ?? null,
                    'college'          => $athlete['college'] ?? null,
                    'college_team'     => $athlete['collegeTeam'] ?? null,
                    'college_athlete'  => $athlete['collegeAthlete'] ?? null,
                    'headshot_href'    => $athlete['headshot']['href'] ?? null,
                    'headshot_alt'     => $athlete['headshot']['alt'] ?? null,
                    'position'         => $athlete['position'] ?? null,
                    'team'             => $athlete['team'] ?? null,
                    'active'           => $athlete['active'] ?? null,
                    'status'           => $athlete['status'] ?? null,
                    'birth_place'      => $athlete['displayBirthPlace'] ?? null,
                    'display_height'   => $athlete['displayHeight'] ?? null,
                    'display_weight'   => $athlete['displayWeight'] ?? null,
                    'display_dob'      => $athlete['displayDOB'] ?? null,
                    'age'              => $athlete['age'] ?? null,
                    'display_jersey'   => $athlete['displayJersey'] ?? null,
                    'display_experience'=> $athlete['displayExperience'] ?? null,
                    'display_draft'    => $athlete['displayDraft'] ?? null,
                ];
            }
        }
    });
    return $playersData;
}

    public function playerGameLog(string|int $playerId, ?string $season = null): array
    {
        $params = ['playerid' => (string) $playerId];
    
        if ($season) {
            $params['season'] = $season;
        }
    
        $response = $this->request('/nba-player-gamelog', $params);
    
        $data = $response['response']['gamelog'] ?? [];
    
        return [
            'labels'      => $data['labels'] ?? [],
            'names'       => $data['names'] ?? [],
            'events'      => $data['events'] ?? [],
            'seasonTypes' => $data['seasonTypes'] ?? [],
            'filters'     => $data['filters'] ?? [],
            'glossary'    => $data['glossary'] ?? [],
        ];
    }
    
    public function allPlayersGameLogsForCron(): array
{
    $allLogs = [];

    \App\Models\NbaPlayer::chunk(10, function ($players) use (&$allLogs) {
        foreach ($players as $player) {
            $gamelog = $this->playerGameLog($player->external_id);
            
            foreach ($gamelog['seasonTypes'] ?? [] as $season) {
                $seasonName = $season['displayName'] ?? null;

                foreach ($season['categories'] ?? [] as $category) {
                    if (!in_array($category['type'], ['event'])) {
                        continue;
                    }

                    foreach ($category['events'] ?? [] as $event) {
                        $meta = $gamelog['events'][$event['eventId']] ?? null;

                        $allLogs[] = [
                            'player_id'      => $player->id,
                            'season_type'    => $seasonName,
                            'game_date'      => isset($meta['gameDate']) ? \Carbon\Carbon::parse($meta['gameDate'])->toDateString() : null,
                            'opponent_name'  => $meta['opponent']['displayName'] ?? null,
                            'opponent_logo'  => $meta['opponent']['logo'] ?? null,
                            'result'         => $meta['gameResult'] ?? null,
                            'score'          => $meta['score'] ?? null,
                            'stats'          => $event['stats'] ?? [],
                        ];
                    }
                }
            }
        }
    });

    return $allLogs;
}

public function standings(int $season = 2024): array
{
    $response = $this->request('/nba-league-standings', [
        'year' => $season,
    ]);

    

    // Defensive: ensure keys exist
    return $response['response']['standings'] ?? [];
}





}
