<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\NbaStanding;
use App\Models\NbaTeam;

class CrossLeagueCompareController extends Controller
{
    public function explorer(Request $request)
    {
        // Request params
        $searchTerm       = trim((string) $request->query('q', ''));
        $seasonFrom       = (int) $request->query('from', 0);
        $seasonTo         = (int) $request->query('to', 0);
        $nbaPerPage       = min(max((int) $request->query('nba_per', 25), 10), 200);
        $lbsPerPage       = min(max((int) $request->query('lbs_per', 25), 10), 200);
        $nbaCurrentPage   = max((int) $request->query('nba_page', 1), 1);
        $lbsCurrentPage   = max((int) $request->query('lbs_page', 1), 1);

        // Season lists
        $nbaSeasonYears = DB::table('nba_player_game_logs')
            ->whereNotNull('game_date')
            ->selectRaw('DISTINCT YEAR(game_date) AS year_val')
            ->pluck('year_val')->toArray();

        $lbsSeasonYears = DB::table('games')
            ->whereNotNull('date')
            ->selectRaw('DISTINCT YEAR(date) AS year_val')
            ->pluck('year_val')->toArray();

        $allSeasons = collect(array_unique(array_merge($nbaSeasonYears, $lbsSeasonYears)))
            ->sortDesc()->values();

        $latestSeason = (int) ($allSeasons->first() ?? date('Y'));
        if (!$seasonFrom) $seasonFrom = $latestSeason;
        if (!$seasonTo)   $seasonTo   = $latestSeason;
        if ($seasonFrom > $seasonTo) { [$seasonFrom, $seasonTo] = [$seasonTo, $seasonFrom]; }

        // NBA query (logs -> aggregated per player/season)
        $nbaLogsQuery = DB::table('nba_player_game_logs as logs')
            ->join('nba_players as players', 'players.external_id', '=', 'logs.player_external_id')
            ->whereNotNull('logs.game_date')
            ->whereBetween(DB::raw('YEAR(logs.game_date)'), [$seasonFrom, $seasonTo]);

        if ($searchTerm !== '') {
            $searchLike = '%' . strtolower($searchTerm) . '%';
            $nbaLogsQuery->where(function ($query) use ($searchLike) {
                $query->whereRaw('LOWER(CONCAT(players.first_name," ",players.last_name)) LIKE ?', [$searchLike])
                      ->orWhereRaw('LOWER(players.team_name) LIKE ?', [$searchLike]);
            });
        }

        $nbaAggregatedQuery = $nbaLogsQuery
            ->selectRaw("
                players.external_id                        AS player_id,
                CONCAT(players.first_name,' ',players.last_name) AS player_name,
                players.image                              AS player_photo,
                players.team_id                            AS team_id,
                players.team_name                          AS team_name,
                players.team_logo                          AS team_logo,
                YEAR(logs.game_date)                       AS season,

                COUNT(*)                                   AS g,
                SUM(CASE WHEN logs.result='W' THEN 1 ELSE 0 END) AS wins,

                SUM(COALESCE(logs.points,0))    AS pts,
                SUM(COALESCE(logs.rebounds,0))  AS reb,
                SUM(COALESCE(logs.assists,0))   AS ast,
                SUM(COALESCE(logs.steals,0))    AS stl,
                SUM(COALESCE(logs.blocks,0))    AS blk,
                SUM(COALESCE(logs.turnovers,0)) AS tov,

                /* parsed from 'x-y' strings */
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.fg,'0-0'), '-', 1) AS UNSIGNED))  AS fgm,
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.fg,'0-0'), '-', -1) AS UNSIGNED)) AS fga,
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.three_pt,'0-0'), '-', 1) AS UNSIGNED))  AS tpm,
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.three_pt,'0-0'), '-', -1) AS UNSIGNED)) AS tpa,
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.ft,'0-0'), '-', 1) AS UNSIGNED))  AS ftm,
                SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.ft,'0-0'), '-', -1) AS UNSIGNED)) AS fta
            ")
            ->groupBy('player_id','player_name','player_photo','team_id','team_name','team_logo','season')
            ->orderByDesc('season')->orderBy('player_name');

        $nbaAggregatedRows   = $nbaAggregatedQuery->get();
        $nbaTotalCount       = $nbaAggregatedRows->count();
        $nbaPaginatedSegment = $nbaAggregatedRows
            ->slice(($nbaCurrentPage-1)*$nbaPerPage, $nbaPerPage)
            ->values();

        $nbaMappedRows = $nbaPaginatedSegment->map(function ($row) {
            $gamesPlayed = max((int)$row->g, 0);

            $pointsPerGame   = $gamesPlayed ? $row->pts / $gamesPlayed : null;
            $reboundsPerGame = $gamesPlayed ? $row->reb / $gamesPlayed : null;
            $assistsPerGame  = $gamesPlayed ? $row->ast / $gamesPlayed : null;
            $stealsPerGame   = $gamesPlayed ? $row->stl / $gamesPlayed : null;
            $blocksPerGame   = $gamesPlayed ? $row->blk / $gamesPlayed : null;
            $turnoversPerGame= $gamesPlayed ? $row->tov / $gamesPlayed : null;

            $fgPct = ($row->fga ?? 0) > 0 ? $row->fgm / $row->fga : null;
            $tpPct = ($row->tpa ?? 0) > 0 ? $row->tpm / $row->tpa : null;
            $ftPct = ($row->fta ?? 0) > 0 ? $row->ftm / $row->fta : null;

            $formatStat = fn($value, $isPercent=false)
                => $value===null ? '—' : ($isPercent ? number_format($value*100,1).'%' : number_format($value,1));

            return (object)[
                'season'      => (int)$row->season,
                'player_id'   => (int)$row->player_id,
                'team_id'     => (int)$row->team_id,
                'player_name' => $row->player_name,
                'headshot'    => $row->player_photo,
                'team_name'   => $row->team_name,
                'team_logo'   => $row->team_logo,
                'g'           => (int)$row->g,
                'wins'        => (int)$row->wins,
                'ppg'         => $formatStat($pointsPerGame),
                'rpg'         => $formatStat($reboundsPerGame),
                'apg'         => $formatStat($assistsPerGame),
                'spg'         => $formatStat($stealsPerGame),
                'bpg'         => $formatStat($blocksPerGame),
                'tpg'         => $formatStat($turnoversPerGame),
                'fg_pct'      => $formatStat($fgPct,true),
                'tp_pct'      => $formatStat($tpPct,true),
                'ft_pct'      => $formatStat($ftPct,true),

                '_raw_ppg' => $pointsPerGame,
                '_raw_rpg' => $reboundsPerGame,
                '_raw_apg' => $assistsPerGame,
                '_raw_spg' => $stealsPerGame,
                '_raw_bpg' => $blocksPerGame,
                '_raw_tpg' => $turnoversPerGame,
                '_raw_fg'  => $fgPct,
                '_raw_tp'  => $tpPct,
                '_raw_ft'  => $ftPct,
            ];
        });

        // LBS query (stats -> aggregated per player/season)
        $lbsStatsQuery = DB::table('player_game_stats as pgs')
            ->join('games as games', 'games.id', '=', 'pgs.game_id')
            ->join('players as players', 'players.id', '=', 'pgs.player_id')
            ->join('teams as teams', 'teams.id', '=', 'pgs.team_id')
            ->whereBetween(DB::raw('YEAR(games.date)'), [$seasonFrom, $seasonTo]);

        if ($searchTerm !== '') {
            $searchLike = '%' . strtolower($searchTerm) . '%';
            $lbsStatsQuery->where(function ($query) use ($searchLike) {
                $query->whereRaw('LOWER(players.name) LIKE ?', [$searchLike])
                      ->orWhereRaw('LOWER(teams.name) LIKE ?', [$searchLike]);
            });
        }

       $lbsAggregatedQuery = $lbsStatsQuery
    ->selectRaw("
        players.id   AS player_id,
        players.name AS player_name,
        players.photo AS player_photo,
        teams.id     AS team_id,
        teams.name   AS team_name,
        teams.logo   AS team_logo,
        YEAR(games.date) AS season,

        COUNT(*) AS g,
        SUM(CASE WHEN games.winner_id = pgs.team_id THEN 1 ELSE 0 END) AS wins,

        SUM(pgs.points) AS pts,
        SUM(pgs.reb)    AS reb,
        SUM(pgs.ast)    AS ast,
        SUM(pgs.stl)    AS stl,
        SUM(pgs.blk)    AS blk,
        SUM(pgs.tov)    AS tov,

        SUM(pgs.fgm2 + pgs.fgm3) AS fgm,
        SUM(pgs.fga2 + pgs.fga3) AS fga,
        SUM(pgs.fgm3)            AS tpm,
        SUM(pgs.fga3)            AS tpa,
        SUM(pgs.ftm)             AS ftm,
        SUM(pgs.fta)             AS fta
    ")
    /* group by the real columns/expressions, NOT the aliases */
    ->groupByRaw('
        players.id,
        players.name,
        players.photo,
        teams.id,
        teams.name,
        teams.logo,
        YEAR(games.date)
    ')
    /* safe ordering */
    ->orderByRaw('YEAR(games.date) DESC')
    ->orderBy('players.name');

$lbsAggregatedRows = $lbsAggregatedQuery->get();
        $lbsTotalCount       = $lbsAggregatedRows->count();
        $lbsPaginatedSegment = $lbsAggregatedRows
            ->slice(($lbsCurrentPage-1)*$lbsPerPage, $lbsPerPage)
            ->values();

        $lbsMappedRows = $lbsPaginatedSegment->map(function ($row) {
            $gamesPlayed = max((int)$row->g, 0);

            $pointsPerGame   = $gamesPlayed ? $row->pts / $gamesPlayed : null;
            $reboundsPerGame = $gamesPlayed ? $row->reb / $gamesPlayed : null;
            $assistsPerGame  = $gamesPlayed ? $row->ast / $gamesPlayed : null;
            $stealsPerGame   = $gamesPlayed ? $row->stl / $gamesPlayed : null;
            $blocksPerGame   = $gamesPlayed ? $row->blk / $gamesPlayed : null;
            $turnoversPerGame= $gamesPlayed ? $row->tov / $gamesPlayed : null;

            $fgPct = ($row->fga ?? 0) > 0 ? $row->fgm / $row->fga : null;
            $tpPct = ($row->tpa ?? 0) > 0 ? $row->tpm / $row->tpa : null;
            $ftPct = ($row->fta ?? 0) > 0 ? $row->ftm / $row->fta : null;

            $formatStat = fn($value, $isPercent=false)
                => $value===null ? '—' : ($isPercent ? number_format($value*100,1).'%' : number_format($value,1));

            return (object)[
                'season'      => (int)$row->season,
                'player_id'   => (int)$row->player_id,
                'team_id'     => (int)$row->team_id,
                'player_name' => $row->player_name,
                'headshot'    => $row->player_photo,
                'team_name'   => $row->team_name,
                'team_logo'   => $row->team_logo,
                'g'           => (int)$row->g,
                'wins'        => (int)$row->wins,
                'ppg'         => $formatStat($pointsPerGame),
                'rpg'         => $formatStat($reboundsPerGame),
                'apg'         => $formatStat($assistsPerGame),
                'spg'         => $formatStat($stealsPerGame),
                'bpg'         => $formatStat($blocksPerGame),
                'tpg'         => $formatStat($turnoversPerGame),
                'fg_pct'      => $formatStat($fgPct,true),
                'tp_pct'      => $formatStat($tpPct,true),
                'ft_pct'      => $formatStat($ftPct,true),

                '_raw_ppg' => $pointsPerGame,
                '_raw_rpg' => $reboundsPerGame,
                '_raw_apg' => $assistsPerGame,
                '_raw_spg' => $stealsPerGame,
                '_raw_bpg' => $blocksPerGame,
                '_raw_tpg' => $turnoversPerGame,
                '_raw_fg'  => $fgPct,
                '_raw_tp'  => $tpPct,
                '_raw_ft'  => $ftPct,
            ];
        });

        $nbaPaginationMeta = [
            'total' => $nbaTotalCount,
            'per'   => $nbaPerPage,
            'page'  => $nbaCurrentPage,
            'last'  => max((int)ceil($nbaTotalCount / $nbaPerPage), 1),
        ];
        $lbsPaginationMeta = [
            'total' => $lbsTotalCount,
            'per'   => $lbsPerPage,
            'page'  => $lbsCurrentPage,
            'last'  => max((int)ceil($lbsTotalCount / $lbsPerPage), 1),
        ];

        return view('nba-lbs_compare', [
            'seasons' => $allSeasons,
            'from'    => $seasonFrom,
            'to'      => $seasonTo,
            'q'       => $searchTerm,
            'nba'     => $nbaMappedRows,
            'lbs'     => $lbsMappedRows,
            'nbaMeta' => $nbaPaginationMeta,
            'lbsMeta' => $lbsPaginationMeta,
        ]);
    }

    public function teamsExplorer(Request $request)
    {
        // Seasons
        $nbaSeasonValues = NbaStanding::query()
            ->select('season')->distinct()->pluck('season')->toArray();

        $lbsSeasonValues = DB::table('games')
            ->selectRaw('DISTINCT YEAR(date) as year_val')->pluck('year_val')->toArray();

        $allSeasons = collect(array_unique(array_merge($nbaSeasonValues, $lbsSeasonValues)))
            ->filter()->sortDesc()->values();

        $minSeason = $allSeasons->isNotEmpty() ? $allSeasons->min() : (int)date('Y');
        $maxSeason = $allSeasons->isNotEmpty() ? $allSeasons->max() : (int)date('Y');

        $seasonFrom = (int) $request->input('from', $minSeason);
        $seasonTo   = (int) $request->input('to',   $maxSeason);
        if ($seasonFrom > $seasonTo) { [$seasonFrom, $seasonTo] = [$seasonTo, $seasonFrom]; }

        $teamSearchTerm = trim((string) $request->input('q', ''));

        // NBA standings block
        $nbaStandings = NbaStanding::query()
            ->when($seasonFrom, fn($query)=>$query->where('season','>=',$seasonFrom))
            ->when($seasonTo,   fn($query)=>$query->where('season','<=',$seasonTo))
            ->when($teamSearchTerm !== '', function ($query) use ($teamSearchTerm) {
                $query->where(function ($subQuery) use ($teamSearchTerm) {
                    $subQuery->where('team_name', 'like', "%{$teamSearchTerm}%")
                             ->orWhere('abbreviation', 'like', "%{$teamSearchTerm}%");
                });
            })
            ->orderBy('season','desc')->orderBy('team_name')
            ->get();

        $standingTeamIds = $nbaStandings->pluck('team_id')->unique()->values();
        $standingTeams   = NbaTeam::whereIn('external_id', $standingTeamIds)->get(['external_id','abbreviation','logo']);

        $teamLogoByExternalId = [];
        foreach ($standingTeams as $team) {
            $abbr = strtolower($team->abbreviation ?? '');
            $fallback = $abbr ? "https://a.espncdn.com/i/teamlogos/nba/500/{$abbr}.png" : null;
            $teamLogoByExternalId[$team->external_id] = $team->logo ?: $fallback;
        }

        $nbaTeamsMapped = $nbaStandings->map(function ($standing) use ($teamLogoByExternalId) {
            $winPct = $standing->win_percent;
            $ppg    = $standing->avg_points_for;
            $oppPpg = $standing->avg_points_against;
            $diff   = $standing->point_differential;

            return (object)[
                'season'      => (int)$standing->season,
                'team_id'     => (int)$standing->team_id,
                'team_name'   => $standing->team_name,
                'team_logo'   => $teamLogoByExternalId[$standing->team_id] ?? null,
                'wins'        => (int)$standing->wins,
                'losses'      => (int)$standing->losses,
                'win_percent' => $winPct,
                'ppg'         => $ppg,
                'opp_ppg'     => $oppPpg,
                'diff'        => $diff,

                'win_percent_fmt' => $winPct !== null ? number_format($winPct * 100, 1) . '%' : '—',
                'ppg_fmt'         => $ppg    !== null ? number_format($ppg, 1)    : '—',
                'opp_ppg_fmt'     => $oppPpg !== null ? number_format($oppPpg, 1) : '—',
                'diff_txt'        => $diff !== null ? (($diff >= 0 ? '+' : '') . $diff) : '—',
                'diff_class'      => $diff !== null ? ($diff >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]') : 'text-gray-300',

                '_key' => "NBA:T:{$standing->team_id}:{$standing->season}",
            ];
        });

        // LBS union (team1/team2 perspective)
        $team1PointsExpr = "COALESCE(team1_q1+team1_q2+team1_q3+team1_q4, CAST(SUBSTRING_INDEX(score,'-',1) AS UNSIGNED))";
        $team2PointsExpr = "COALESCE(team2_q1+team2_q2+team2_q3+team2_q4, CAST(SUBSTRING_INDEX(score,'-',-1) AS UNSIGNED))";

        $team1PerspectiveQuery = DB::table('games as games')
            ->join('teams as teams', 'teams.id', '=', 'games.team1_id')
            ->selectRaw("
                teams.id as team_id, teams.name as team_name, teams.logo as team_logo,
                YEAR(games.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN games.winner_id = teams.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN games.winner_id IS NOT NULL AND games.winner_id <> teams.id THEN 1 ELSE 0 END) as losses,
                SUM($team1PointsExpr) as points_for,
                SUM($team2PointsExpr) as points_against
            ")
            ->when($seasonFrom, fn($query)=>$query->whereRaw('YEAR(games.date) >= ?',[$seasonFrom]))
            ->when($seasonTo,   fn($query)=>$query->whereRaw('YEAR(games.date) <= ?',[$seasonTo]))
            ->groupBy('team_id','team_name','team_logo','season');

        $team2PerspectiveQuery = DB::table('games as games')
            ->join('teams as teams', 'teams.id', '=', 'games.team2_id')
            ->selectRaw("
                teams.id as team_id, teams.name as team_name, teams.logo as team_logo,
                YEAR(games.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN games.winner_id = teams.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN games.winner_id IS NOT NULL AND games.winner_id <> teams.id THEN 1 ELSE 0 END) as losses,
                SUM($team2PointsExpr) as points_for,
                SUM($team1PointsExpr) as points_against
            ")
            ->when($seasonFrom, fn($query)=>$query->whereRaw('YEAR(games.date) >= ?',[$seasonFrom]))
            ->when($seasonTo,   fn($query)=>$query->whereRaw('YEAR(games.date) <= ?',[$seasonTo]))
            ->groupBy('team_id','team_name','team_logo','season');

        $lbsUnionAgg = DB::query()->fromSub($team1PerspectiveQuery->unionAll($team2PerspectiveQuery), 'u')
            ->selectRaw("
                team_id, team_name, team_logo, season,
                SUM(games) as games, SUM(wins) as wins, SUM(losses) as losses,
                SUM(points_for) as points_for, SUM(points_against) as points_against
            ")
            ->groupBy('team_id','team_name','team_logo','season');

        if ($teamSearchTerm !== '') {
            $like = '%'.strtolower($teamSearchTerm).'%';
            $lbsUnionAgg->whereRaw('LOWER(team_name) LIKE ?', [$like]);
        }

        $lbsTeamRows = $lbsUnionAgg->orderByDesc('season')->orderBy('team_name')->get();

        $lbsTeamsMapped = $lbsTeamRows->map(function ($row) {
            $games   = max((int)$row->games,0);
            $wins    = (int)$row->wins;
            $losses  = (int)$row->losses;
            $pointsF = (int)$row->points_for;
            $pointsA = (int)$row->points_against;

            $winPct  = ($wins+$losses)>0 ? $wins/($wins+$losses) : null;
            $ppg     = $games>0 ? $pointsF/$games : null;
            $oppPpg  = $games>0 ? $pointsA/$games : null;
            $diff    = ($wins+$losses)>0 ? ($pointsF - $pointsA) : null;

            return (object)[
                'season'      => (int)$row->season,
                'team_id'     => (int)$row->team_id,
                'team_name'   => $row->team_name,
                'team_logo'   => $row->team_logo,
                'wins'        => $wins,
                'losses'      => $losses,
                'win_percent' => $winPct,
                'ppg'         => $ppg,
                'opp_ppg'     => $oppPpg,
                'diff'        => $diff,

                'win_percent_fmt' => $winPct !== null ? number_format($winPct*100,1).'%' : '—',
                'ppg_fmt'         => $ppg   !== null ? number_format($ppg,1)       : '—',
                'opp_ppg_fmt'     => $oppPpg!== null ? number_format($oppPpg,1)    : '—',
                'diff_txt'        => $diff  !== null ? (($diff>=0?'+':'').$diff)   : '—',
                'diff_class'      => $diff  !== null ? ($diff>=0?'text-[#84CC16]':'text-[#F97316]') : 'text-gray-300',

                '_key' => "LBS:T:{$row->team_id}:{$row->season}",
            ];
        });

        // Pagination (manual, same as your original)
        $nbaPerPage       = min(max((int)$request->query('nba_per', 25), 10), 200);
        $lbsPerPage       = min(max((int)$request->query('lbs_per', 25), 10), 200);
        $nbaCurrentPage   = max((int)$request->query('nba_page', 1), 1);
        $lbsCurrentPage   = max((int)$request->query('lbs_page', 1), 1);

        $nbaTotalCount = $nbaTeamsMapped->count();
        $lbsTotalCount = $lbsTeamsMapped->count();

        $nbaTeamsPage = $nbaTeamsMapped->slice(($nbaCurrentPage-1)*$nbaPerPage, $nbaPerPage)->values();
        $lbsTeamsPage = $lbsTeamsMapped->slice(($lbsCurrentPage-1)*$lbsPerPage, $lbsPerPage)->values();

        $nbaPaginationMeta = [
            'total' => $nbaTotalCount,
            'per'   => $nbaPerPage,
            'page'  => $nbaCurrentPage,
            'last'  => max((int)ceil($nbaTotalCount/$nbaPerPage),1)
        ];
        $lbsPaginationMeta = [
            'total' => $lbsTotalCount,
            'per'   => $lbsPerPage,
            'page'  => $lbsCurrentPage,
            'last'  => max((int)ceil($lbsTotalCount/$lbsPerPage),1)
        ];

        return view('nba-lbs_teams_compare', [
            'seasons' => $allSeasons,
            'from'    => $seasonFrom,
            'to'      => $seasonTo,
            'q'       => $teamSearchTerm,
            'nba'     => $nbaTeamsPage,
            'lbs'     => $lbsTeamsPage,
            'nbaMeta' => $nbaPaginationMeta,
            'lbsMeta' => $lbsPaginationMeta,
        ]);
    }
}
