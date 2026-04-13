<?php

namespace App\Http\Controllers\Lbs\Teams;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\League;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
class TeamController extends Controller
{
    public function show($id)
    {
        $team = Team::with(['players.games'])->findOrFail($id);

        $games = Game::where('team1_id', $team->id)
            ->orWhere('team2_id', $team->id)
            ->get();

        $gamesCount = max($games->count(), 1);

        $totalStats = ['points'=>0,'reb'=>0,'ast'=>0,'stl'=>0,'blk'=>0];

        foreach ($team->players as $player) {
            if (!$player->games) continue;
            foreach ($player->games as $game) {
                if (!$game->pivot) continue;
                if ($game->team1_id == $team->id || $game->team2_id == $team->id) {
                    $totalStats['points'] += $game->pivot->points ?? 0;
                    $totalStats['reb']    += $game->pivot->reb ?? 0;
                    $totalStats['ast']    += $game->pivot->ast ?? 0;
                    $totalStats['stl']    += $game->pivot->stl ?? 0;
                    $totalStats['blk']    += $game->pivot->blk ?? 0;
                }
            }
        }

        $averageStats = [
            'points' => $totalStats['points'] / $gamesCount,
            'reb'    => $totalStats['reb'] / $gamesCount,
            'ast'    => $totalStats['ast'] / $gamesCount,
            'stl'    => $totalStats['stl'] / $gamesCount,
            'blk'    => $totalStats['blk'] / $gamesCount,
        ];

        $bestPlayers = ['points'=>null,'rebounds'=>null,'assists'=>null];

        foreach (['points','reb','ast'] as $stat) {
            $bestPlayer = $team->players
                ->filter(fn($p) => $p->games && $p->games->count() > 0)
                ->map(fn($p) => (object)[
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'value' => $p->games->sum(fn($g) => $g->pivot->{$stat} ?? 0),
                ])
                ->sortByDesc('value')
                ->first();

            if ($stat === 'reb')      $bestPlayers['rebounds'] = $bestPlayer;
            elseif ($stat === 'ast')  $bestPlayers['assists']  = $bestPlayer;
            else                      $bestPlayers['points']   = $bestPlayer;
        }

        $wins = $games->where('winner_id', $team->id)->count();
        $losses = $games->count() - $wins;

        return view('lbs.teams.show', compact('team', 'averageStats', 'bestPlayers', 'wins', 'losses'));
    }

    public function games($id)
    {
        $team = Team::findOrFail($id);
        $parentLeagues = League::whereNull('parent_id')->get();
    
        $games = Game::where('team1_id', $team->id)
            ->orWhere('team2_id', $team->id)
            ->with(['team1', 'team2'])
            ->get()
            ->map(function ($game) {
                $game->score1 = $game->score2 = 0;
    
                if ($game->score) {
                    if (str_contains($game->score, '-')) {
                        $parts = explode('-', $game->score);
                    } elseif (str_contains($game->score, ':')) {
                        $parts = explode(':', $game->score);
                    } else {
                        $parts = [];
                    }
                    $game->score1 = isset($parts[0]) ? (int)$parts[0] : 0;
                    $game->score2 = isset($parts[1]) ? (int)$parts[1] : 0;
                } else {
                    $game->score1 = ($game->team1_q1 ?? 0) + ($game->team1_q2 ?? 0) + ($game->team1_q3 ?? 0) + ($game->team1_q4 ?? 0);
                    $game->score2 = ($game->team2_q1 ?? 0) + ($game->team2_q2 ?? 0) + ($game->team2_q3 ?? 0) + ($game->team2_q4 ?? 0);
                }
    
                return $game;
            });
    
        $upcomingGames = $games->filter(fn($g) => $g->date && $g->date->isFuture())
            ->sortBy('date')->values();
    
        $pastGames = $games->filter(fn($g) => $g->date && ($g->date->isPast() || $g->date->isToday()))
            ->values()
            ->map(function ($g) use ($team) {
                $winnerId = $g->winner_id;
                if (!$winnerId && ($g->score1 !== null && $g->score2 !== null)) {
                    if ($g->score1 > $g->score2) {
                        $winnerId = $g->team1_id;
                    } elseif ($g->score2 > $g->score1) {
                        $winnerId = $g->team2_id;
                    }
                }
                $g->is_win  = $winnerId && ((int)$winnerId === (int)$team->id);
                $g->is_loss = $winnerId && !$g->is_win;
                return $g;
            })
            ->sortByDesc('date')
            ->values();
    
        return view('lbs.teams.games', compact(
            'team',
            'parentLeagues',
            'games',
            'upcomingGames',
            'pastGames'
        ));
    }

    public function players($id)
    {
        $team = Team::with('players')->findOrFail($id);
        return view('lbs.teams.players', compact('team'));
    }

public function stats($id)
{

    $team = \App\Models\Team::with('players')->findOrFail($id);

    // Team games for W/L and team per-game avgs
    $games = \App\Models\Game::where('team1_id', $team->id)
        ->orWhere('team2_id', $team->id)
        ->get();

    $wins   = $games->where('winner_id', $team->id)->count();
    $losses = $games->count() - $wins;
    $totalGames = max($games->count(), 1);

    // Team per-game averages from player_game_stats
    $statKeys = [
        'points' => 'Punkti',
        'oreb'   => 'Atl. bumbas uzbrukumā',
        'dreb'   => 'Atl. bumbas aizsardzībā',
        'reb'    => 'Atl. bumbas',
        'ast'    => 'Piespēles',
        'pf'     => 'Fouls',
        'tov'    => 'Kļūdas',
        'stl'    => 'Pārķertās',
        'blk'    => 'Bloķētie metieni',
        'eff'    => 'Efektivitāte',
    ];

    $averageStats = [];
    foreach ($statKeys as $key => $label) {
        $total = DB::table('player_game_stats')
            ->where('team_id', $team->id)
            ->sum($key);
        $averageStats[$key] = ['label' => $label, 'avg' => $total / $totalGames];
    }

    // Helper: parse "MM:SS" or "HH:MM:SS" (or numeric seconds) → seconds
    $toSeconds = function ($val): int {
        if ($val === null) return 0;
        $s = trim((string)$val);
        if ($s === '') return 0;
        if (ctype_digit($s)) return (int)$s; // already seconds

        if (strpos($s, ':') !== false) {
            $parts = array_map('intval', explode(':', $s));
            if (count($parts) === 2) { [$m,$sec] = $parts; return max(0, $m*60 + $sec); }
            if (count($parts) === 3) { [$h,$m,$sec] = $parts; return max(0, $h*3600 + $m*60 + $sec); }
        }
        // last-chance: "32min 20 sec"
        if (preg_match('/(\d+)\D+(\d+)/', $s, $m)) {
            return max(0, ((int)$m[1])*60 + (int)$m[2]);
        }
        return 0;
    };

    // Pull raw stats rows for this team’s players
    $playerIds = $team->players->pluck('id')->all();
    $rows = DB::table('player_game_stats')
        ->where('team_id', $team->id)
        ->whereIn('player_id', $playerIds)
        ->select('player_id','points','reb','ast','minutes','status')
        ->get();

    // Aggregate per player (exclude DNP entirely)
    $agg = [];
    foreach ($rows as $r) {
        $pid = (int)$r->player_id;
        $status = strtolower((string)($r->status ?? 'played'));

        if (!isset($agg[$pid])) {
            $agg[$pid] = [
                'gp'    => 0,
                'pts'   => 0,
                'reb'   => 0,
                'ast'   => 0,
                'min_s' => 0,
            ];
        }

        if ($status === 'dnp') {
            continue; // did not play → do not count in average or minutes
        }

        $agg[$pid]['gp']    += 1;
        $agg[$pid]['pts']   += (int)$r->points;
        $agg[$pid]['reb']   += (int)$r->reb;
        $agg[$pid]['ast']   += (int)$r->ast;
        $agg[$pid]['min_s'] += $toSeconds($r->minutes);
    }

    // Build players table rows, preserving photo/jersey
    $playersStats = $team->players->map(function ($p) use ($agg) {
        $pid = (int)$p->id;
        $gp  = $agg[$pid]['gp'] ?? 0;
        $safe = fn($n,$d) => $d ? ($n / $d) : 0;

        return [
            'id'            => $pid,
            'name'          => $p->name,
            'photo'         => $p->photo,
            'jersey_number' => $p->jersey_number,
            'gamesPlayed'   => $gp,
            'ppg'           => $safe($agg[$pid]['pts']   ?? 0, $gp),
            'rpg'           => $safe($agg[$pid]['reb']   ?? 0, $gp),
            'apg'           => $safe($agg[$pid]['ast']   ?? 0, $gp),
            // average seconds per played game (this is what the view formats & sorts by)
            'minutes'       => (int) round($safe($agg[$pid]['min_s'] ?? 0, $gp)),
        ];
    });

    return view('lbs.teams.stats', compact(
        'team', 'games', 'wins', 'losses', 'averageStats', 'playersStats'
    ));
}

}
