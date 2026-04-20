<?php

namespace App\Jobs;

use App\Models\NbaStanding;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

            foreach ($standings as $conference) {
                foreach (($conference['children'] ?? []) as $division) {
                    $entries = $division['standings']['entries'] ?? [];

                    foreach ($entries as $entry) {
                        $team = $entry['team'] ?? [];
                        $stats = collect($entry['stats'] ?? [])->keyBy(function ($stat) {
                            return $stat['name'] ?? $stat['type'] ?? null;
                        });

                        NbaStanding::updateOrCreate(
                            [
                                'team_id' => $team['id'] ?? null,
                                'season'  => $season,
                            ],
                            [
                                'team_name'          => $team['displayName'] ?? null,
                                'abbreviation'       => $team['abbreviation'] ?? null,
                                'wins'               => data_get($stats->get('wins'), 'value'),
                                'losses'             => data_get($stats->get('losses'), 'value'),
                                'win_percent'        => data_get($stats->get('winPercent'), 'value'),
                                'playoff_seed'       => data_get($stats->get('playoffSeed'), 'value'),
                                'games_behind'       => data_get($stats->get('gamesBehind'), 'value'),
                                'avg_points_for'     => data_get($stats->get('avgPointsFor'), 'value'),
                                'avg_points_against' => data_get($stats->get('avgPointsAgainst'), 'value'),
                                'point_differential' => data_get($stats->get('pointDifferential'), 'value'),
                                'home_record'        => data_get($stats->get('home'), 'summary'),
                                'road_record'        => data_get($stats->get('road'), 'summary'),
                                'last_ten'           => data_get($stats->get('lasttengames'), 'summary'),
                                'streak'             => data_get($stats->get('streak'), 'value'),
                                'clincher'           => data_get($stats->get('clincher'), 'displayValue'),
                            ]
                        );
                    }
                }
            }

            usleep(500000);
        }

        cache()->increment('processed');
    }
}