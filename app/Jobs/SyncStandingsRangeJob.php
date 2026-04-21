<?php

namespace App\Jobs;

use App\Models\NbaStanding;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

            $entries = $this->extractEntries($standings);

            Log::info('standings sync season', [
                'season' => $season,
                'entries_found' => count($entries),
            ]);

            foreach ($entries as $entry) {
                $team = $entry['team'] ?? [];
                $teamId = $team['id'] ?? null;

                if (!$teamId) {
                    continue;
                }

                $stats = collect($entry['stats'] ?? [])->keyBy(function ($stat) {
                    return strtolower($stat['type'] ?? $stat['name'] ?? '');
                });

                NbaStanding::updateOrCreate(
                    [
                        'team_id' => (int) $teamId,
                        'season'  => $season,
                    ],
                    [
                        'team_name'            => $team['displayName']
                            ?? trim(($team['location'] ?? '') . ' ' . ($team['name'] ?? '')),
                        'abbreviation'         => $team['abbreviation'] ?? null,

                        'wins'                 => $this->num($stats, 'wins'),
                        'losses'               => $this->num($stats, 'losses'),
                        'win_percent'          => $this->num($stats, 'winpercent'),
                        'playoff_seed'         => $this->num($stats, 'playoffseed'),
                        'games_behind'         => $this->num($stats, 'gamesbehind'),

                        'avg_points_for'       => $this->num($stats, 'avgpointsfor'),
                        'avg_points_against'   => $this->num($stats, 'avgpointsagainst'),
                        'point_differential'   => $this->num($stats, 'pointdifferential'),

                        'points'               => $this->num($stats, 'points'),
                        'points_for'           => $this->num($stats, 'pointsfor'),
                        'points_against'       => $this->num($stats, 'pointsagainst'),
                        'division_win_percent' => $this->num($stats, 'divisionwinpercent'),
                        'league_win_percent'   => $this->num($stats, 'leaguewinpercent'),

                        'streak'               => $this->num($stats, 'streak'),
                        'clincher'             => $this->text($stats, 'clincher'),

                        'league_standings'     => $this->text($stats, 'leaguestandings'),
                        'home_record'          => $this->text($stats, 'home'),
                        'road_record'          => $this->text($stats, 'road'),
                        'division_record'      => $this->text($stats, 'vsdiv'),
                        'conference_record'    => $this->text($stats, 'vsconf'),
                        'last_ten'             => $this->text($stats, 'lasttengames'),
                    ]
                );
            }

            usleep(500000);
        }
    }

    private function extractEntries(array $data): array
    {
        $found = [];

        $walk = function ($node) use (&$walk, &$found) {
            if (!is_array($node)) {
                return;
            }

            if (isset($node['entries']) && is_array($node['entries'])) {
                foreach ($node['entries'] as $entry) {
                    if (is_array($entry) && isset($entry['team'])) {
                        $found[] = $entry;
                    }
                }
            }

            foreach ($node as $value) {
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };

        $walk($data);

        $unique = [];
        foreach ($found as $entry) {
            $teamId = $entry['team']['id'] ?? null;
            if ($teamId) {
                $unique[(string) $teamId] = $entry;
            }
        }

        return array_values($unique);
    }

    private function num($stats, string $key): mixed
    {
        return data_get($stats->get($key), 'value');
    }

    private function text($stats, string $key): ?string
    {
        $stat = $stats->get($key);

        if (!$stat) {
            return null;
        }

        $value = data_get($stat, 'summary')
            ?? data_get($stat, 'displayValue');

        return $value !== null ? (string) $value : null;
    }
}