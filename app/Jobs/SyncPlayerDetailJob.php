<?php

namespace App\Jobs;

use App\Models\NbaPlayerDetail;
use App\Services\NbaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlayerDetailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $playerId;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;
    }

    public function handle(NbaService $nbaService): void
    {
        $athlete = $nbaService->playerInfo($this->playerId);
        if (empty($athlete)) {
            return;
        }
        NbaPlayerDetail::updateOrCreate(
            ['external_id' => $athlete['id'] ?? null],
            [
                'uid'        => $athlete['uid'] ?? null,
                'guid'       => $athlete['guid'] ?? null,
                'first_name' => $athlete['firstName'] ?? null,
                'last_name'  => $athlete['lastName'] ?? null,
                'full_name'  => $athlete['fullName'] ?? null,
                'display_name' => $athlete['displayName'] ?? null,
                'jersey'     => $athlete['jersey'] ?? null,
                'headshot_href' => $athlete['headshot']['href'] ?? null,
                'headshot_alt'  => $athlete['headshot']['alt'] ?? null,
                'age'        => $athlete['age'] ?? null,
            ]
        );
    }
}