<?php

namespace App\Http\Controllers\Lbs\Teams;

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

        $t1_pts = "COALESCE(team1_q1+team1_q2+team1_q3+team1_q4, CAST(SUBSTRING_INDEX(score,'-',1) AS UNSIGNED))";
        $t2_pts = "COALESCE(team2_q1+team2_q2+team2_q3+team2_q4, CAST(SUBSTRING_INDEX(score,'-',-1) AS UNSIGNED))";

        $q1 = DB::table('games as g')
            ->join('teams as t', 't.id', '=', 'g.team1_id')
            ->leftJoin('leagues as ls', 'ls.id', '=', 't.league_id') 
            ->selectRaw("
                t.id as team_id,
                t.name as team_name,
                t.logo as team_logo,
                t.league_id as subleague_id,
                ls.parent_id as parent_league_id,
                YEAR(g.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN g.winner_id = t.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN g.winner_id IS NOT NULL AND g.winner_id <> t.id THEN 1 ELSE 0 END) as losses,
                SUM($t1_pts) as points_for,
                SUM($t2_pts) as points_against
            ")
            ->when($from, fn($q) => $q->whereRaw('YEAR(g.date) >= ?', [$from]))
            ->when($to,   fn($q) => $q->whereRaw('YEAR(g.date) <= ?', [$to]))
            ->groupBy('team_id','team_name','team_logo','subleague_id','parent_league_id','season');

        $q2 = DB::table('games as g')
            ->join('teams as t', 't.id', '=', 'g.team2_id')
            ->leftJoin('leagues as ls', 'ls.id', '=', 't.league_id')
            ->selectRaw("
                t.id as team_id,
                t.name as team_name,
                t.logo as team_logo,
                t.league_id as subleague_id,
                ls.parent_id as parent_league_id,
                YEAR(g.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN g.winner_id = t.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN g.winner_id IS NOT NULL AND g.winner_id <> t.id THEN 1 ELSE 0 END) as losses,
                SUM($t2_pts) as points_for,
                SUM($t1_pts) as points_against
            ")
            ->when($from, fn($q) => $q->whereRaw('YEAR(g.date) >= ?', [$from]))
            ->when($to,   fn($q) => $q->whereRaw('YEAR(g.date) <= ?', [$to]))
            ->groupBy('team_id','team_name','team_logo','subleague_id','parent_league_id','season');

        $collection = DB::query()
            ->fromSub($q1->unionAll($q2), 'u')
            ->selectRaw("
                team_id, team_name, team_logo, subleague_id, parent_league_id, season,
                SUM(games) as games,
                SUM(wins) as wins,
                SUM(losses) as losses,
                SUM(points_for) as points_for,
                SUM(points_against) as points_against
            ")
            ->groupBy('team_id','team_name','team_logo','subleague_id','parent_league_id','season')
            ->orderByDesc('season')
            ->orderBy('team_name')
            ->get();

        $rows = $collection->map(function ($r) {
            $games  = max((int)$r->games, 0);
            $wins   = (int)$r->wins;
            $losses = (int)$r->losses;
            $pf     = (int)$r->points_for;
            $pa     = (int)$r->points_against;

            $winPct = ($wins + $losses) > 0 ? $wins / ($wins + $losses) : null;
            $ppg    = $games > 0 ? $pf / $games : null;
            $oppPpg = $games > 0 ? $pa / $games : null;
            $diff   = ($wins + $losses) > 0 ? ($pf - $pa) : null;

            return [
                'team_id'          => (int)$r->team_id,
                'team_name'        => $r->team_name,
                'team_logo'        => $r->team_logo, 
                'season'           => (int)$r->season,
                'wins'             => $wins,
                'losses'           => $losses,
                'win_percent_fmt'  => $winPct !== null ? number_format($winPct * 100, 1) . '%' : '—',
                'ppg_fmt'          => $ppg    !== null ? number_format($ppg, 1)          : '—',
                'opp_ppg_fmt'      => $oppPpg !== null ? number_format($oppPpg, 1)       : '—',
                'diff_txt'         => $diff !== null ? (($diff >= 0 ? '+' : '') . $diff) : '—',
                'diff_class'       => $diff !== null ? ($diff >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]') : 'text-gray-300',
                'data_team'        => strtolower($r->team_name ?? ''),
                'parent_league_id' => $r->parent_league_id ? (int)$r->parent_league_id : '',
                'subleague_id'     => $r->subleague_id ? (int)$r->subleague_id : '',
 
                'win_percent'      => $winPct,
                'ppg'              => $ppg,
                'opp_ppg'          => $oppPpg,
                'diff'             => $diff,
            ];
        })->values();

        return view('lbs.teams.compare', [
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
                ['Record', 'Wins–Losses (aprēķināts no spēlēm).'],
                ['Win%',   'Uzvaru īpatsvars.'],
                ['PPG',    'Vidējie gūtie punkti spēlē.'],
                ['OPP PPG','Vidējie ielaistie punkti spēlē.'],
                ['Diff',   'Gūtie mīnus ielaistie punkti (sezonā).'],
            ],
        ]);
    }
}
