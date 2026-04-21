<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\NbaStanding;
use App\Models\NbaTeam;
use Illuminate\Support\Collection;

class CrossLeagueCompareController extends Controller
{
    public function explorer(Request $request)
{
    [$allSeasons, $seasonFrom, $seasonTo, $searchTerm] = $this->resolvePlayerFilters($request);

    $nbaPlayersMapped = $this->buildNbaPlayers($seasonFrom, $seasonTo, $searchTerm);
    $lbsPlayersMapped = $this->buildLbsPlayers($seasonFrom, $seasonTo, $searchTerm);

    $nbaPerPage     = min(max((int) $request->query('nba_per', 25), 10), 200);
    $lbsPerPage     = min(max((int) $request->query('lbs_per', 25), 10), 200);
    $nbaCurrentPage = max((int) $request->query('nba_page', 1), 1);
    $lbsCurrentPage = max((int) $request->query('lbs_page', 1), 1);

    $nbaPage = $this->paginateCollection($nbaPlayersMapped, $nbaPerPage, $nbaCurrentPage);
    $lbsPage = $this->paginateCollection($lbsPlayersMapped, $lbsPerPage, $lbsCurrentPage);

    return view('nba-lbs_compare', [
        'seasons' => $allSeasons,
        'from'    => $seasonFrom,
        'to'      => $seasonTo,
        'q'       => $searchTerm,

        'nba'     => $nbaPage['items'],
        'lbs'     => $lbsPage['items'],

        // full filtered datasets for instant client-side pagination
        'nbaAll'  => $nbaPlayersMapped->values(),
        'lbsAll'  => $lbsPlayersMapped->values(),

        'nbaMeta' => $nbaPage['meta'],
        'lbsMeta' => $lbsPage['meta'],
    ]);
}

protected function resolvePlayerFilters(Request $request): array
{
    $nbaSeasonYears = DB::table('nba_player_game_logs')
        ->whereNotNull('game_date')
        ->selectRaw('DISTINCT YEAR(game_date) AS year_val')
        ->pluck('year_val')
        ->toArray();

    $lbsSeasonYears = DB::table('games')
        ->whereNotNull('date')
        ->selectRaw('DISTINCT YEAR(date) AS year_val')
        ->pluck('year_val')
        ->toArray();

    $allSeasons = collect(array_unique(array_merge($nbaSeasonYears, $lbsSeasonYears)))
        ->filter(fn ($v) => $v !== null && $v !== '')
        ->map(fn ($v) => (int) $v)
        ->sortDesc()
        ->values();

    $minSeason = $allSeasons->isNotEmpty() ? (int) $allSeasons->min() : (int) date('Y');
    $maxSeason = $allSeasons->isNotEmpty() ? (int) $allSeasons->max() : (int) date('Y');

    $seasonFrom = (int) $request->input('from', $minSeason);
    $seasonTo   = (int) $request->input('to', $maxSeason);

    if ($seasonFrom > $seasonTo) {
        [$seasonFrom, $seasonTo] = [$seasonTo, $seasonFrom];
    }

    $searchTerm = trim((string) $request->input('q', ''));

    return [$allSeasons, $seasonFrom, $seasonTo, $searchTerm];
}

protected function buildNbaPlayers(int $seasonFrom, int $seasonTo, string $searchTerm): Collection
{
    $query = DB::table('nba_player_game_logs as logs')
        ->join('nba_players as players', 'players.external_id', '=', 'logs.player_external_id')
        ->whereNotNull('logs.game_date')
        ->whereBetween(DB::raw('YEAR(logs.game_date)'), [$seasonFrom, $seasonTo]);

    if ($searchTerm !== '') {
        $searchLike = '%' . strtolower($searchTerm) . '%';

        $query->where(function ($q) use ($searchLike) {
            $q->whereRaw('LOWER(CONCAT(players.first_name, " ", players.last_name)) LIKE ?', [$searchLike])
              ->orWhereRaw('LOWER(players.team_name) LIKE ?', [$searchLike]);
        });
    }

    $rows = $query
        ->selectRaw("
            players.external_id AS player_id,
            CONCAT(players.first_name, ' ', players.last_name) AS player_name,
            players.image AS player_photo,
            players.team_id AS team_id,
            players.team_name AS team_name,
            players.team_logo AS team_logo,
            YEAR(logs.game_date) AS season,

            COUNT(*) AS g,
            SUM(CASE WHEN logs.result = 'W' THEN 1 ELSE 0 END) AS wins,

            SUM(COALESCE(logs.points, 0))    AS pts,
            SUM(COALESCE(logs.rebounds, 0))  AS reb,
            SUM(COALESCE(logs.assists, 0))   AS ast,
            SUM(COALESCE(logs.steals, 0))    AS stl,
            SUM(COALESCE(logs.blocks, 0))    AS blk,
            SUM(COALESCE(logs.turnovers, 0)) AS tov,

            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.fg, '0-0'), '-', 1) AS UNSIGNED))  AS fgm,
            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.fg, '0-0'), '-', -1) AS UNSIGNED)) AS fga,
            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.three_pt, '0-0'), '-', 1) AS UNSIGNED))  AS tpm,
            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.three_pt, '0-0'), '-', -1) AS UNSIGNED)) AS tpa,
            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.ft, '0-0'), '-', 1) AS UNSIGNED))  AS ftm,
            SUM(CAST(SUBSTRING_INDEX(COALESCE(logs.ft, '0-0'), '-', -1) AS UNSIGNED)) AS fta
        ")
        ->groupByRaw("
            players.external_id,
            CONCAT(players.first_name, ' ', players.last_name),
            players.image,
            players.team_id,
            players.team_name,
            players.team_logo,
            YEAR(logs.game_date)
        ")
        ->orderByRaw('YEAR(logs.game_date) DESC')
        ->orderByRaw("CONCAT(players.first_name, ' ', players.last_name) ASC")
        ->get();

    return $rows->map(function ($row) {
        $gamesPlayed = max((int) $row->g, 0);

        $pointsPerGame    = $gamesPlayed ? $row->pts / $gamesPlayed : null;
        $reboundsPerGame  = $gamesPlayed ? $row->reb / $gamesPlayed : null;
        $assistsPerGame   = $gamesPlayed ? $row->ast / $gamesPlayed : null;
        $stealsPerGame    = $gamesPlayed ? $row->stl / $gamesPlayed : null;
        $blocksPerGame    = $gamesPlayed ? $row->blk / $gamesPlayed : null;
        $turnoversPerGame = $gamesPlayed ? $row->tov / $gamesPlayed : null;

        $fgPct = ($row->fga ?? 0) > 0 ? $row->fgm / $row->fga : null;
        $tpPct = ($row->tpa ?? 0) > 0 ? $row->tpm / $row->tpa : null;
        $ftPct = ($row->fta ?? 0) > 0 ? $row->ftm / $row->fta : null;

        $fmt = fn ($value, $isPercent = false)
            => $value === null ? '—' : ($isPercent ? number_format($value * 100, 1) . '%' : number_format($value, 1));

        return (object) [
            'season'      => (int) $row->season,
            'player_id'   => (int) $row->player_id,
            'team_id'     => (int) $row->team_id,
            'player_name' => $row->player_name,
            'headshot'    => $row->player_photo,
            'team_name'   => $row->team_name,
            'team_logo'   => $row->team_logo,
            'g'           => (int) $row->g,
            'wins'        => (int) $row->wins,
            'ppg'         => $fmt($pointsPerGame),
            'rpg'         => $fmt($reboundsPerGame),
            'apg'         => $fmt($assistsPerGame),
            'spg'         => $fmt($stealsPerGame),
            'bpg'         => $fmt($blocksPerGame),
            'tpg'         => $fmt($turnoversPerGame),
            'fg_pct'      => $fmt($fgPct, true),
            'tp_pct'      => $fmt($tpPct, true),
            'ft_pct'      => $fmt($ftPct, true),

            '_raw_ppg' => $pointsPerGame,
            '_raw_rpg' => $reboundsPerGame,
            '_raw_apg' => $assistsPerGame,
            '_raw_spg' => $stealsPerGame,
            '_raw_bpg' => $blocksPerGame,
            '_raw_tpg' => $turnoversPerGame,
            '_raw_fg'  => $fgPct,
            '_raw_tp'  => $tpPct,
            '_raw_ft'  => $ftPct,

            '_key' => "NBA:{$row->player_id}:{$row->season}",
        ];
    })->values();
}

protected function buildLbsPlayers(int $seasonFrom, int $seasonTo, string $searchTerm): Collection
{
    $query = DB::table('player_game_stats as pgs')
        ->join('games as g', 'g.id', '=', 'pgs.game_id')
        ->join('players as p', 'p.id', '=', 'pgs.player_id')
        ->join('teams as t', 't.id', '=', 'pgs.team_id')
        ->whereBetween(DB::raw('YEAR(g.date)'), [$seasonFrom, $seasonTo]);

    if ($searchTerm !== '') {
        $searchLike = '%' . strtolower($searchTerm) . '%';

        $query->where(function ($q) use ($searchLike) {
            $q->whereRaw('LOWER(p.name) LIKE ?', [$searchLike])
              ->orWhereRaw('LOWER(t.name) LIKE ?', [$searchLike]);
        });
    }

    $rows = $query
        ->selectRaw("
            p.id AS player_id,
            p.name AS player_name,
            p.photo AS player_photo,
            t.id AS team_id,
            t.name AS team_name,
            t.logo AS team_logo,
            YEAR(g.date) AS season,

            COUNT(*) AS g,
            SUM(CASE WHEN g.winner_id = pgs.team_id THEN 1 ELSE 0 END) AS wins,

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
        ->groupByRaw("
            p.id,
            p.name,
            p.photo,
            t.id,
            t.name,
            t.logo,
            YEAR(g.date)
        ")
        ->orderByRaw('YEAR(g.date) DESC')
        ->orderBy('p.name')
        ->get();

    return $rows->map(function ($row) {
        $gamesPlayed = max((int) $row->g, 0);

        $pointsPerGame    = $gamesPlayed ? $row->pts / $gamesPlayed : null;
        $reboundsPerGame  = $gamesPlayed ? $row->reb / $gamesPlayed : null;
        $assistsPerGame   = $gamesPlayed ? $row->ast / $gamesPlayed : null;
        $stealsPerGame    = $gamesPlayed ? $row->stl / $gamesPlayed : null;
        $blocksPerGame    = $gamesPlayed ? $row->blk / $gamesPlayed : null;
        $turnoversPerGame = $gamesPlayed ? $row->tov / $gamesPlayed : null;

        $fgPct = ($row->fga ?? 0) > 0 ? $row->fgm / $row->fga : null;
        $tpPct = ($row->tpa ?? 0) > 0 ? $row->tpm / $row->tpa : null;
        $ftPct = ($row->fta ?? 0) > 0 ? $row->ftm / $row->fta : null;

        $fmt = fn ($value, $isPercent = false)
            => $value === null ? '—' : ($isPercent ? number_format($value * 100, 1) . '%' : number_format($value, 1));

        return (object) [
            'season'      => (int) $row->season,
            'player_id'   => (int) $row->player_id,
            'team_id'     => (int) $row->team_id,
            'player_name' => $row->player_name,
            'headshot'    => $row->player_photo,
            'team_name'   => $row->team_name,
            'team_logo'   => $row->team_logo,
            'g'           => (int) $row->g,
            'wins'        => (int) $row->wins,
            'ppg'         => $fmt($pointsPerGame),
            'rpg'         => $fmt($reboundsPerGame),
            'apg'         => $fmt($assistsPerGame),
            'spg'         => $fmt($stealsPerGame),
            'bpg'         => $fmt($blocksPerGame),
            'tpg'         => $fmt($turnoversPerGame),
            'fg_pct'      => $fmt($fgPct, true),
            'tp_pct'      => $fmt($tpPct, true),
            'ft_pct'      => $fmt($ftPct, true),

            '_raw_ppg' => $pointsPerGame,
            '_raw_rpg' => $reboundsPerGame,
            '_raw_apg' => $assistsPerGame,
            '_raw_spg' => $stealsPerGame,
            '_raw_bpg' => $blocksPerGame,
            '_raw_tpg' => $turnoversPerGame,
            '_raw_fg'  => $fgPct,
            '_raw_tp'  => $tpPct,
            '_raw_ft'  => $ftPct,

            '_key' => "LBS:{$row->player_id}:{$row->season}",
        ];
    })->values();
}

    public function teamsExplorer(Request $request)
    {
        [$allSeasons, $seasonFrom, $seasonTo, $teamSearchTerm] = $this->resolveFilters($request);

        $nbaTeamsMapped = $this->buildNbaTeams($seasonFrom, $seasonTo, $teamSearchTerm);
        $lbsTeamsMapped = $this->buildLbsTeams($seasonFrom, $seasonTo, $teamSearchTerm);

        $nbaPerPage     = min(max((int) $request->query('nba_per', 25), 10), 200);
        $lbsPerPage     = min(max((int) $request->query('lbs_per', 25), 10), 200);
        $nbaCurrentPage = max((int) $request->query('nba_page', 1), 1);
        $lbsCurrentPage = max((int) $request->query('lbs_page', 1), 1);

        $nbaPage = $this->paginateCollection($nbaTeamsMapped, $nbaPerPage, $nbaCurrentPage);
        $lbsPage = $this->paginateCollection($lbsTeamsMapped, $lbsPerPage, $lbsCurrentPage);

        return view('nba-lbs_teams_compare', [
            'seasons' => $allSeasons,
            'from'    => $seasonFrom,
            'to'      => $seasonTo,
            'q'       => $teamSearchTerm,

            // current page rows (for initial render / deep links)
            'nba'     => $nbaPage['items'],
            'lbs'     => $lbsPage['items'],

            // full filtered datasets for instant client-side pagination
            'nbaAll'  => $nbaTeamsMapped->values(),
            'lbsAll'  => $lbsTeamsMapped->values(),

            'nbaMeta' => $nbaPage['meta'],
            'lbsMeta' => $lbsPage['meta'],
        ]);
    }

    protected function resolveFilters(Request $request): array
    {
        $nbaSeasonValues = NbaStanding::query()
            ->select('season')
            ->distinct()
            ->pluck('season')
            ->toArray();

        $lbsSeasonValues = DB::table('games')
            ->selectRaw('DISTINCT YEAR(date) as year_val')
            ->pluck('year_val')
            ->toArray();

        $allSeasons = collect(array_unique(array_merge($nbaSeasonValues, $lbsSeasonValues)))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->sortDesc()
            ->values();

        $minSeason = $allSeasons->isNotEmpty() ? (int) $allSeasons->min() : (int) date('Y');
        $maxSeason = $allSeasons->isNotEmpty() ? (int) $allSeasons->max() : (int) date('Y');

        $seasonFrom = (int) $request->input('from', $minSeason);
        $seasonTo   = (int) $request->input('to', $maxSeason);

        if ($seasonFrom > $seasonTo) {
            [$seasonFrom, $seasonTo] = [$seasonTo, $seasonFrom];
        }

        $teamSearchTerm = trim((string) $request->input('q', ''));

        return [$allSeasons, $seasonFrom, $seasonTo, $teamSearchTerm];
    }

    protected function buildNbaTeams(int $seasonFrom, int $seasonTo, string $teamSearchTerm): Collection
    {
        $nbaStandings = NbaStanding::query()
            ->when($seasonFrom, fn ($query) => $query->where('season', '>=', $seasonFrom))
            ->when($seasonTo, fn ($query) => $query->where('season', '<=', $seasonTo))
            ->when($teamSearchTerm !== '', function ($query) use ($teamSearchTerm) {
                $query->where(function ($subQuery) use ($teamSearchTerm) {
                    $subQuery->where('team_name', 'like', "%{$teamSearchTerm}%")
                        ->orWhere('abbreviation', 'like', "%{$teamSearchTerm}%");
                });
            })
            ->orderBy('season', 'desc')
            ->orderBy('team_name')
            ->get();

        $standingTeamIds = $nbaStandings->pluck('team_id')->unique()->values();

        $standingTeams = NbaTeam::query()
            ->whereIn('external_id', $standingTeamIds)
            ->get(['external_id', 'abbreviation', 'logo']);

        $teamLogoByExternalId = [];
        foreach ($standingTeams as $team) {
            $abbr = strtolower($team->abbreviation ?? '');
            $fallback = $abbr ? "https://a.espncdn.com/i/teamlogos/nba/500/{$abbr}.png" : null;
            $teamLogoByExternalId[$team->external_id] = $team->logo ?: $fallback;
        }

        return $nbaStandings->map(function ($standing) use ($teamLogoByExternalId) {
            $winPct = $standing->win_percent;
            $ppg    = $standing->avg_points_for;
            $oppPpg = $standing->avg_points_against;
            $diff   = $standing->point_differential;

            $wins   = (int) $standing->wins;
            $losses = (int) $standing->losses;
            $games  = $wins + $losses;

            return (object) [
                'season'      => (int) $standing->season,
                'team_id'     => (int) $standing->team_id,
                'team_name'   => $standing->team_name,
                'team_logo'   => $teamLogoByExternalId[$standing->team_id] ?? null,
                'games'       => $games,
                'wins'        => $wins,
                'losses'      => $losses,
                'win_percent' => $winPct,
                'ppg'         => $ppg,
                'opp_ppg'     => $oppPpg,
                'diff'        => $diff,

                'win_percent_fmt' => $winPct !== null ? number_format($winPct * 100, 1) . '%' : '—',
                'ppg_fmt'         => $ppg    !== null ? number_format($ppg, 1) : '—',
                'opp_ppg_fmt'     => $oppPpg !== null ? number_format($oppPpg, 1) : '—',
                'diff_txt'        => $diff   !== null ? (($diff >= 0 ? '+' : '') . number_format($diff, 1)) : '—',
                'diff_class'      => $diff   !== null ? ($diff >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]') : 'text-gray-300',

                '_key' => "NBA:T:{$standing->team_id}:{$standing->season}",
            ];
        })->values();
    }

    protected function buildLbsTeams(int $seasonFrom, int $seasonTo, string $teamSearchTerm): Collection
    {
        $team1PointsExpr = "COALESCE(team1_q1+team1_q2+team1_q3+team1_q4, CAST(SUBSTRING_INDEX(score,'-',1) AS UNSIGNED))";
        $team2PointsExpr = "COALESCE(team2_q1+team2_q2+team2_q3+team2_q4, CAST(SUBSTRING_INDEX(score,'-',-1) AS UNSIGNED))";

        $team1PerspectiveQuery = DB::table('games as games')
            ->join('teams as teams', 'teams.id', '=', 'games.team1_id')
            ->selectRaw("
                teams.id as team_id,
                teams.name as team_name,
                teams.logo as team_logo,
                YEAR(games.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN games.winner_id = teams.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN games.winner_id IS NOT NULL AND games.winner_id <> teams.id THEN 1 ELSE 0 END) as losses,
                SUM($team1PointsExpr) as points_for,
                SUM($team2PointsExpr) as points_against
            ")
            ->when($seasonFrom, fn ($query) => $query->whereRaw('YEAR(games.date) >= ?', [$seasonFrom]))
            ->when($seasonTo, fn ($query) => $query->whereRaw('YEAR(games.date) <= ?', [$seasonTo]))
            ->groupBy('team_id', 'team_name', 'team_logo', 'season');

        $team2PerspectiveQuery = DB::table('games as games')
            ->join('teams as teams', 'teams.id', '=', 'games.team2_id')
            ->selectRaw("
                teams.id as team_id,
                teams.name as team_name,
                teams.logo as team_logo,
                YEAR(games.date) as season,
                COUNT(*) as games,
                SUM(CASE WHEN games.winner_id = teams.id THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN games.winner_id IS NOT NULL AND games.winner_id <> teams.id THEN 1 ELSE 0 END) as losses,
                SUM($team2PointsExpr) as points_for,
                SUM($team1PointsExpr) as points_against
            ")
            ->when($seasonFrom, fn ($query) => $query->whereRaw('YEAR(games.date) >= ?', [$seasonFrom]))
            ->when($seasonTo, fn ($query) => $query->whereRaw('YEAR(games.date) <= ?', [$seasonTo]))
            ->groupBy('team_id', 'team_name', 'team_logo', 'season');

        $lbsUnionAgg = DB::query()
            ->fromSub($team1PerspectiveQuery->unionAll($team2PerspectiveQuery), 'u')
            ->selectRaw("
                team_id,
                team_name,
                team_logo,
                season,
                SUM(games) as games,
                SUM(wins) as wins,
                SUM(losses) as losses,
                SUM(points_for) as points_for,
                SUM(points_against) as points_against
            ")
            ->groupBy('team_id', 'team_name', 'team_logo', 'season');

        if ($teamSearchTerm !== '') {
            $like = '%' . strtolower($teamSearchTerm) . '%';
            $lbsUnionAgg->whereRaw('LOWER(team_name) LIKE ?', [$like]);
        }

        $lbsTeamRows = $lbsUnionAgg
            ->orderByDesc('season')
            ->orderBy('team_name')
            ->get();

        return $lbsTeamRows->map(function ($row) {
            $games   = max((int) $row->games, 0);
            $wins    = (int) $row->wins;
            $losses  = (int) $row->losses;
            $pointsF = (int) $row->points_for;
            $pointsA = (int) $row->points_against;

            $winPct  = ($wins + $losses) > 0 ? $wins / ($wins + $losses) : null;
            $ppg     = $games > 0 ? $pointsF / $games : null;
            $oppPpg  = $games > 0 ? $pointsA / $games : null;
            $diff    = ($wins + $losses) > 0 ? ($pointsF - $pointsA) : null;

            return (object) [
                'season'      => (int) $row->season,
                'team_id'     => (int) $row->team_id,
                'team_name'   => $row->team_name,
                'team_logo'   => $row->team_logo,
                'games'       => $games,
                'wins'        => $wins,
                'losses'      => $losses,
                'win_percent' => $winPct,
                'ppg'         => $ppg,
                'opp_ppg'     => $oppPpg,
                'diff'        => $diff,

                'win_percent_fmt' => $winPct !== null ? number_format($winPct * 100, 1) . '%' : '—',
                'ppg_fmt'         => $ppg    !== null ? number_format($ppg, 1) : '—',
                'opp_ppg_fmt'     => $oppPpg !== null ? number_format($oppPpg, 1) : '—',
                'diff_txt'        => $diff   !== null ? (($diff >= 0 ? '+' : '') . number_format($diff, 1)) : '—',
                'diff_class'      => $diff   !== null ? ($diff >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]') : 'text-gray-300',

                '_key' => "LBS:T:{$row->team_id}:{$row->season}",
            ];
        })->values();
    }

    protected function paginateCollection(Collection $collection, int $perPage, int $currentPage): array
    {
        $total = $collection->count();
        $last  = max((int) ceil(max($total, 1) / $perPage), 1);
        $page  = min(max($currentPage, 1), $last);

        $items = $collection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'items' => $items,
            'meta'  => [
                'total' => $total,
                'per'   => $perPage,
                'page'  => $page,
                'last'  => $last,
            ],
        ];
    }
}