<?php

namespace App\Http\Controllers\Nba\Games;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GameShowController extends Controller
{
    public function show(int $eventId)
    {
        $payload = Cache::remember("nba_game:$eventId", 3600, function () use ($eventId) {
            $rows = DB::table('nba_player_game_logs as l')
                ->leftJoin('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
                ->leftJoin('nba_teams   as t', 't.external_id', '=', 'p.team_id')
                ->where('l.event_id', $eventId)
                ->select([
                    'l.event_id',
                    'l.game_date',
                    'l.result',        // 'W' / 'L'
                    'l.score',
                    'l.opponent_name',
                    'l.opponent_logo',

                    'p.external_id as player_id', // <-- will be used for route('nba.player.show', ['player' => ...])
                    DB::raw("TRIM(CONCAT(COALESCE(p.first_name,''),' ',COALESCE(p.last_name,''))) as player_name"),
                    'p.image as headshot',
                    'p.team_id as player_team_id',

                    'l.minutes',
                    'l.fg', 'l.fg_pct',
                    'l.three_pt', 'l.three_pt_pct',
                    'l.ft', 'l.ft_pct',
                    'l.rebounds', 'l.assists', 'l.steals', 'l.blocks', 'l.turnovers', 'l.fouls', 'l.points',
                ])
                ->orderByDesc('l.points')
                ->get();

            if ($rows->isEmpty()) return null;

            $byResult = $rows->groupBy(fn($r) => strtoupper(trim((string)$r->result)) === 'W' ? 'W' : 'L');
            $W = $byResult->get('W', collect());
            $L = $byResult->get('L', collect());

            if ($W->isEmpty() || $L->isEmpty()) {
                $half = (int) ceil($rows->count() / 2);
                $W = $rows->slice(0, $half);
                $L = $rows->slice($half);
            }

            $winnerName = $L->first()->opponent_name ?? 'Uzvarētāji';
            $winnerLogo = $L->first()->opponent_logo ?? null;
            $loserName  = $W->first()->opponent_name ?? 'Zaudētāji';
            $loserLogo  = $W->first()->opponent_logo ?? null;

            $pair = function ($s) {
                if (!is_string($s) || strpos($s, '-') === false) return [0, 0];
                [$m, $a] = explode('-', $s, 2);
                return [max((int)$m, 0), max((int)$a, 0)];
            };
            $pct = fn($m, $a) => $a > 0 ? round(($m / $a) * 100, 1) : null;

            $buildTeam = function ($group, string $teamName, ?string $teamLogo) use ($pair, $pct) {
                $fgM = $fgA = $tpM = $tpA = $ftM = $ftA = 0;

                // Robust side team_id = mode of players’ team IDs (ignoring nulls)
                $teamId = optional($group->pluck('player_team_id')->filter()->countBy()->sortDesc())->keys()->first();

                return [
                    'team'    => $teamName,
                    'team_id' => $teamId,         // used for route('nba.team.show', ['team' => ...])
                    'logo'    => $teamLogo,
                    'players' => $group->map(function ($r) {
                        return [
                            'player_id' => $r->player_id,  // used for route('nba.player.show', ['player' => ...])
                            'name'      => $r->player_name ?: '—',
                            'img'       => $r->headshot,
                            'min'       => $r->minutes,
                            'fg'        => $r->fg,       'fgp'  => $r->fg_pct,
                            'tp'        => $r->three_pt, 'tpp'  => $r->three_pt_pct,
                            'ft'        => $r->ft,       'ftp'  => $r->ft_pct,
                            'reb'       => $r->rebounds, 'ast'  => $r->assists, 'stl' => $r->steals,
                            'blk'       => $r->blocks,   'tov'  => $r->turnovers, 'pf'  => $r->fouls,
                            'pts'       => $r->points,
                        ];
                    })->values(),
                    'totals' => (function () use ($group, $pair, &$fgM, &$fgA, &$tpM, &$tpA, &$ftM, &$ftA, $pct) {
                        foreach ($group as $r) {
                            [$m1, $a1] = $pair($r->fg);       $fgM += $m1; $fgA += $a1;
                            [$m2, $a2] = $pair($r->three_pt); $tpM += $m2; $tpA += $a2;
                            [$m3, $a3] = $pair($r->ft);       $ftM += $m3; $ftA += $a3;
                        }
                        return [
                            'pts' => (int)$group->sum('points'),
                            'reb' => (int)$group->sum('rebounds'),
                            'ast' => (int)$group->sum('assists'),
                            'stl' => (int)$group->sum('steals'),
                            'blk' => (int)$group->sum('blocks'),
                            'tov' => (int)$group->sum('turnovers'),
                            'pf'  => (int)$group->sum('fouls'),
                            'fg'  => ['m' => $fgM, 'a' => $fgA, 'pct' => $pct($fgM, $fgA)],
                            'tp'  => ['m' => $tpM, 'a' => $tpA, 'pct' => $pct($tpM, $tpA)],
                            'ft'  => ['m' => $ftM, 'a' => $ftA, 'pct' => $pct($ftM, $ftA)],
                        ];
                    })(),
                ];
            };

            $A = $buildTeam($W, $winnerName, $winnerLogo);
            $B = $buildTeam($L, $loserName,  $loserLogo);

            $sumScore = "{$A['totals']['pts']}-{$B['totals']['pts']}";
            $anyRow   = $rows->first();
            $scoreStr = $anyRow->score ?: $sumScore;

            $winnerIdx = $A['totals']['pts'] === $B['totals']['pts']
                ? null
                : ($A['totals']['pts'] > $B['totals']['pts'] ? 0 : 1);

            return [
                'game' => [
                    'event_id' => $eventId,
                    'date'     => $anyRow->game_date,
                    'score'    => $scoreStr,
                    'winner'   => $winnerIdx,
                ],
                'A' => $A,
                'B' => $B,
            ];
        });

        abort_if(!$payload, 404);

        return view('nba.games.show', $payload);
    }
}
