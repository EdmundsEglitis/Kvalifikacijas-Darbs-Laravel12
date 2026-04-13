<?php

namespace App\Http\Controllers\Lbs\Players;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Player;

class PlayerController extends Controller
{
    public function show($id)
    {
        $player = Player::with(['team.league', 'playerGameStats.game.team1', 'playerGameStats.game.team2'])
            ->findOrFail($id);

        $parentLeagues = League::whereNull('parent_id')->get();

        $stats = $player->playerGameStats;
        $totals = [
            'games'   => $stats->count(),
            'points'  => $stats->sum('points'),
            'reb'     => $stats->sum('reb'),
            'ast'     => $stats->sum('ast'),
            'stl'     => $stats->sum('stl'),
            'blk'     => $stats->sum('blk'),
            'eff'     => $stats->sum('eff'),
        ];
        $averages = $totals['games'] > 0 ? [
            'points' => round($totals['points'] / $totals['games'], 1),
            'reb'    => round($totals['reb'] / $totals['games'], 1),
            'ast'    => round($totals['ast'] / $totals['games'], 1),
            'stl'    => round($totals['stl'] / $totals['games'], 1),
            'blk'    => round($totals['blk'] / $totals['games'], 1),
            'eff'    => round($totals['eff'] / $totals['games'], 1),
        ] : [];

        
        return view('lbs.players.show', compact('player', 'parentLeagues', 'totals', 'averages'));
    }
}
