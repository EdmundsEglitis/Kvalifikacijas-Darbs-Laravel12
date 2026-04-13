<?php

namespace App\Http\Controllers\Lbs\Players;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompareController extends Controller
{
    public function explorer(Request $request)
    {
        $leagueParam = $request->integer('league') ?: null;
        $subParam    = $request->integer('sub') ?: null;

        $selectedParentId = null;
        $selectedSubId    = null;

        if ($subParam) {
            $row = DB::table('leagues')->select('id','parent_id')->where('id', $subParam)->first();
            if ($row) {
                $selectedSubId    = (int) $row->id;
                $selectedParentId = $row->parent_id ? (int) $row->parent_id : null;
            }
        } elseif ($leagueParam) {
            $row = DB::table('leagues')->select('id','parent_id')->where('id', $leagueParam)->first();
            if ($row) {
                if ($row->parent_id) {
                    $selectedSubId    = (int) $row->id;
                    $selectedParentId = (int) $row->parent_id;
                } else {
                    $selectedParentId = (int) $row->id;
                    $selectedSubId = DB::table('leagues')
                        ->where('parent_id', $selectedParentId)
                        ->orderBy('name')
                        ->value('id');
                    $selectedSubId = $selectedSubId ? (int) $selectedSubId : null;
                }
            }
        }

        $seasons = DB::table('games')
            ->selectRaw('DISTINCT YEAR(date) AS season')
            ->orderByDesc('season')
            ->pluck('season')
            ->toArray();

        $minSeason = $seasons ? min($seasons) : (int) date('Y');
        $maxSeason = $seasons ? max($seasons) : (int) date('Y');

        $from = (int) $request->input('from', $minSeason);
        $to   = (int) $request->input('to',   $maxSeason);
        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $parents = DB::table('leagues')
            ->select('id','name')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $subs = DB::table('leagues')
            ->select('id','name','parent_id')
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();

        $subLeague = $selectedSubId
            ? DB::table('leagues')->select('id','name','parent_id')->where('id', $selectedSubId)->first()
            : null;

        $base = DB::table('player_game_stats as pgs')
            ->join('games as g', 'g.id', '=', 'pgs.game_id')
            ->join('players as p', 'p.id', '=', 'pgs.player_id')
            ->join('teams as t', 't.id', '=', 'pgs.team_id')     
            ->leftJoin('leagues as ls', 'ls.id', '=', 't.league_id') 
            ->selectRaw("
                p.id  as player_id,
                p.name as player_name,
                p.photo as player_photo,
                t.id  as team_id,
                t.name as team_name,
                t.logo as team_logo,
                t.league_id as subleague_id,
                ls.parent_id as parent_league_id,
                YEAR(g.date) as season,

                COUNT(*) as games,

                SUM(pgs.points) as pts,
                SUM(pgs.reb)    as reb,
                SUM(pgs.ast)    as ast,
                SUM(pgs.stl)    as stl,
                SUM(pgs.blk)    as blk,
                SUM(pgs.tov)    as tov,

                SUM(pgs.fgm2 + pgs.fgm3) as fgm_total,
                SUM(pgs.fga2 + pgs.fga3) as fga_total,
                SUM(pgs.fgm3) as tpm,
                SUM(pgs.fga3) as tpa,
                SUM(pgs.ftm)  as ftm,
                SUM(pgs.fta)  as fta,

                SUM(CASE WHEN g.winner_id = pgs.team_id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN g.winner_id IS NOT NULL AND g.winner_id <> pgs.team_id THEN 1 ELSE 0 END) as losses
            ")
            ->when($from, fn($q) => $q->whereRaw('YEAR(g.date) >= ?', [$from]))
            ->when($to,   fn($q) => $q->whereRaw('YEAR(g.date) <= ?', [$to]))
            ->groupBy('player_id','player_name','player_photo','team_id','team_name','team_logo','subleague_id','parent_league_id','season')
            ->orderByDesc('season')
            ->orderBy('player_name');

        $collection = $base->get();

        $rows = $collection->map(function ($r) {
            $g = max((int)$r->games, 0);
            $ppg = $g > 0 ? (float)$r->pts / $g : null;
            $rpg = $g > 0 ? (float)$r->reb / $g : null;
            $apg = $g > 0 ? (float)$r->ast / $g : null;
            $spg = $g > 0 ? (float)$r->stl / $g : null;
            $bpg = $g > 0 ? (float)$r->blk / $g : null;
            $tpg = $g > 0 ? (float)$r->tov / $g : null;

            $fg_pct = ($r->fga_total ?? 0) > 0 ? (float)$r->fgm_total / (float)$r->fga_total : null;
            $tp_pct = ($r->tpa       ?? 0) > 0 ? (float)$r->tpm       / (float)$r->tpa       : null;
            $ft_pct = ($r->fta       ?? 0) > 0 ? (float)$r->ftm       / (float)$r->fta       : null;

            $wl_text = is_numeric($r->wins) && is_numeric($r->losses) ? "{$r->wins}–{$r->losses}" : '—';

            $payload = json_encode([
                'season'   => (int)$r->season,
                'player'   => $r->player_name,
                'team'     => $r->team_name,
                'abbr'     => null,
                'logo'     => $r->team_logo,   
                'headshot' => $r->player_photo, 
                'ppg'      => $ppg,
                'rpg'      => $rpg,
                'apg'      => $apg,
                'spg'      => $spg,
                'bpg'      => $bpg,
                'tpg'      => $tpg,
                'fg_pct'   => $fg_pct,
                'tp_pct'   => $tp_pct,
                'ft_pct'   => $ft_pct,
                'games'    => (int)$r->games,
                'wins'     => (int)($r->wins ?? 0),
                'losses'   => (int)($r->losses ?? 0),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $pct = function($v) { return $v !== null ? number_format($v * 100, 1) . '%' : '—'; };
            $one = function($v) { return $v !== null ? number_format($v, 1) : '—'; };

            return [
                'player_id'   => (int)$r->player_id,
                'player'      => $r->player_name,
                'player_photo'=> $r->player_photo,
                'team_id'     => (int)$r->team_id,
                'team'        => $r->team_name,
                'team_logo'   => $r->team_logo,

                'season'      => (int)$r->season,
                'games'       => (int)$r->games,
                'wl_text'     => $wl_text,

                'ppg'         => $one($ppg),
                'rpg'         => $one($rpg),
                'apg'         => $one($apg),
                'spg'         => $one($spg),
                'bpg'         => $one($bpg),
                'tpg'         => $one($tpg),
                'fg_pct'      => $pct($fg_pct),
                'tp_pct'      => $pct($tp_pct),
                'ft_pct'      => $pct($ft_pct),

                'data_player' => strtolower(trim($r->player_name . ' ' . $r->team_name)),
                'parent_league_id' => $r->parent_league_id ? (int)$r->parent_league_id : '',
                'subleague_id'     => $r->subleague_id ? (int)$r->subleague_id : '',

                'payload'     => $payload,
            ];
        })->values();

        return view('lbs.players.compare', [
            'seasons'        => $seasons,
            'from'           => $from,
            'to'             => $to,
            'parents'        => $parents,
            'subs'           => $subs,
            'selectedParent' => $selectedParentId,
            'selectedSub'    => $selectedSubId,
            'subLeague'      => $subLeague, 
            'rows'           => $rows,
            'legend'         => [
                ['G',     'Spēļu skaits.'],
                ['W/L',   'Uzvaras / zaudējumi (komanda, kurā spēlētājs piedalījās).'],
                ['PPG',   'Vidējie punkti spēlē.'],
                ['RPG',   'Vidējās atlēkušās bumbas spēlē.'],
                ['APG',   'Vidējās rezultatīvās piespēles spēlē.'],
                ['SPG',   'Vidējās pārtvertās bumbas spēlē.'],
                ['BPG',   'Vidējie bloķētie metieni spēlē.'],
                ['TOV',   'Kļūdas spēlē. (zemāk – labāk)'],
                ['FG%',   'Metienu precizitāte kopā (2P+3P).'],
                ['3P%',   'Tālmētiens precizitāte.'],
                ['FT%',   'Sodiņu precizitāte.'],
            ],
        ]);
    }
}
