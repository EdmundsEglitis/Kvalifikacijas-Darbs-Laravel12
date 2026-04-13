<?php

namespace App\Http\Controllers\Lbs\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\League;

class GameController extends Controller
{
    public function show($id)
    {
        $game = Game::with(['team1','team2','playerGameStats.player'])->findOrFail($id);

        $team1Score = $team2Score = 0;

        if ($game->score) {
            if (str_contains($game->score, '-')) {
                $parts = explode('-', $game->score);
            } elseif (str_contains($game->score, ':')) {
                $parts = explode(':', $game->score);
            } else {
                $parts = [];
            }
            $team1Score = isset($parts[0]) ? (int)$parts[0] : 0;
            $team2Score = isset($parts[1]) ? (int)$parts[1] : 0;
        } else {
            $team1Score = ($game->team1_q1 ?? 0) + ($game->team1_q2 ?? 0) + ($game->team1_q3 ?? 0) + ($game->team1_q4 ?? 0);
            $team2Score = ($game->team2_q1 ?? 0) + ($game->team2_q2 ?? 0) + ($game->team2_q3 ?? 0) + ($game->team2_q4 ?? 0);
        }

        $playerStats = $game->playerGameStats->groupBy('team_id');
        $parentLeagues = League::whereNull('parent_id')->get();

        return view('lbs.games.show', compact(
            'game',
            'team1Score',
            'team2Score',
            'playerStats',
            'parentLeagues'
        ));
    }
}
