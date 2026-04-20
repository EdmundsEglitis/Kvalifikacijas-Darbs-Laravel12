<?php

namespace App\Jobs;

use App\Models\NbaPlayer;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlayersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60];

    public function __construct(
        public string|int $teamId,
        public array $team
    ) {}

    public function handle(NbaService $nbaService): void
    {
        $players = $nbaService->playersByTeam($this->teamId);

        foreach ($players as $player) {
            NbaPlayer::updateOrCreate(
                ['uid' => $player['uid']],
                [
                    'external_id'    => $player['id'],
                    'guid'           => $player['guid'] ?? null,
                    'first_name'     => $player['firstName'],
                    'last_name'      => $player['lastName'],
                    'full_name'      => $player['fullName'],
                    'display_weight' => $player['displayWeight'] ?? null,
                    'display_height' => $player['displayHeight'] ?? null,
                    'age'            => $player['age'] ?? null,
                    'salary'         => $player['salary'] ?? null,
                    'image'          => $player['image'] ?? null,
                    'team_id'        => $this->teamId,
                    'team_name'      => $this->team['name'] ?? null,
                    'team_logo'      => $this->team['logo'] ?? null,
                ]
            );
        }
    }
}