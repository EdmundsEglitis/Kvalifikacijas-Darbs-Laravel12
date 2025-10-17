<?php

namespace App\Http\Controllers\Nba\Games;

use App\Http\Controllers\Controller;
use App\Models\NbaGame;
use App\Services\NbaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class GameController extends Controller
{
    public function __construct(private NbaService $nba) {}

    public function upcoming()
    {
        $games = NbaGame::query()
            ->where('tipoff', '>=', Carbon::now())
            ->orderBy('tipoff')
            ->take(20)
            ->get();

        return view('nba.games.index', compact('games'));
    }


    public function all(Request $request)
    {
        // -------- Filters --------
        $team       = trim((string) $request->query('team', ''));      // team/opponent/player (text)
        $winnerLike = trim((string) $request->query('winner', ''));    // winner team text
        $fromY      = (int) $request->query('from', 0);
        $toY        = (int) $request->query('to',   0);
        $per        = min(max((int) $request->query('per_page', 25), 10), 100);
    
        // -------- Seasons (from logs) --------
        $seasons = DB::table('nba_player_game_logs')
            ->selectRaw('DISTINCT YEAR(game_date) AS season')
            ->orderByDesc('season')
            ->pluck('season')->toArray();
    
        if (!$seasons) $seasons = range((int) date('Y'), (int) date('Y') - 10);
        $maxSeason = max($seasons);
        $from = $fromY ?: $maxSeason;
        $to   = $toY   ?: $maxSeason;
        if ($from > $to) { [$from, $to] = [$to, $from]; }
    
        // -------- Base distinct events from logs --------
        // (optionally join players only when team/opponent/player filter is used)
        $eventsSub = DB::table('nba_player_game_logs as l')
            ->when($team !== '', fn($q) =>
                $q->join('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
            )
            ->when($from, fn($q) => $q->whereRaw('YEAR(l.game_date) >= ?', [$from]))
            ->when($to,   fn($q) => $q->whereRaw('YEAR(l.game_date) <= ?',   [$to]))
            ->when($team !== '', function ($q) use ($team) {
                $like = "%{$team}%";
                $q->where(function ($w) use ($like) {
                    $w->where('p.team_name', 'like', $like)
                      ->orWhere('l.opponent_name', 'like', $like)
                      ->orWhere(DB::raw("CONCAT(p.first_name,' ',p.last_name)"), 'like', $like);
                });
            })
            ->groupBy('l.event_id')
            ->selectRaw('
                l.event_id,
                MAX(l.game_date) as game_date,
                MAX(l.score)     as score_str
            ');
    
        // -------- Winner subquery (from summed player points) --------
        // Compute winner_name per event: top team by total points in logs.
        $totalsPerTeam = DB::table('nba_player_game_logs as l')
            ->join('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
            ->groupBy('l.event_id', 'p.team_name')
            ->selectRaw('l.event_id, p.team_name, SUM(COALESCE(l.points,0)) AS pts');
    
        // MySQL-only trick to pick the team with max pts per event (first in ORDER BY pts DESC)
        $winnerSub = DB::query()->fromSub($totalsPerTeam, 't')
            ->selectRaw(
                "t.event_id,
                 SUBSTRING_INDEX(
                    GROUP_CONCAT(t.team_name ORDER BY t.pts DESC SEPARATOR '||'),
                    '||', 1
                 ) AS winner_name"
            )
            ->groupBy('t.event_id');
    
        // Glue together: events + (optional) winner filter
        $base = DB::query()->fromSub($eventsSub, 'e')
            ->leftJoinSub($winnerSub, 'w', 'w.event_id', '=', 'e.event_id')
            ->when($winnerLike !== '', function ($q) use ($winnerLike) {
                $q->where('w.winner_name', 'like', '%'.$winnerLike.'%');
            })
            ->orderByDesc('e.game_date');
    
        // Paginate at the DB level (preserves counts)
        $events = $base->paginate($per)->withQueryString();
    
        if ($events->isEmpty()) {
            return view('nba.games.all', [
                'rows'      => $events,
                'seasons'   => $seasons,
                'from'      => $from,
                'to'        => $to,
                'teamQuery' => $team,
                'winnerQ'   => $winnerLike,
                'per'       => $per,
                'legend'    => [
                    ['Datums/Laiks', 'Spēles datums un laiks kurā tā notika'],
                    ['Rezultāts',     'Gala rezultāts'],
                    ['Mājinieki/Viesi', 'Kura komanda spēlēja mājas spēli un kura bija viesos'],
                    ['Uzvarētājs',    'Kura koamnda uzvarēja spēli'],
                ],
            ]);
        }
    
        // -------- For *current page* events, resolve:
        // (1) two “teams” (top-2 by number of player rows),
        // (2) per-team total points to build/align scores,
        // (3) winner (from winnerSub join, but also compute locally for safety).
        $eventIds = collect($events->items())->pluck('event_id')->all();
    
        $teamsByEvent = DB::table('nba_player_game_logs as l')
            ->join('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
            ->whereIn('l.event_id', $eventIds)
            ->groupBy('l.event_id', 'p.team_id', 'p.team_name', 'p.team_logo')
            ->selectRaw('l.event_id, p.team_id, p.team_name, p.team_logo, COUNT(*) as c')
            ->get()
            ->groupBy('event_id')
            ->map(function ($rows) {
                $top = $rows->sortByDesc('c')->values();
                return [$top[0] ?? null, $top[1] ?? null];
            });
    
        $ptsByEvent = DB::table('nba_player_game_logs as l')
            ->join('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
            ->whereIn('l.event_id', $eventIds)
            ->groupBy('l.event_id', 'p.team_name')
            ->selectRaw('l.event_id, p.team_name, SUM(COALESCE(l.points,0)) AS pts')
            ->get()
            ->groupBy('event_id')
            ->map(function ($rows) {
                // Map team_name => pts for quick lookup
                return $rows->mapWithKeys(fn($r) => [$r->team_name => (int)$r->pts]);
            });
    
        // Build rows aligned to the two derived teams
        $rows = collect($events->items())->map(function ($e) use ($teamsByEvent, $ptsByEvent) {
            [$t1, $t2] = $teamsByEvent->get($e->event_id, [null, null]);
    
            $homeName = $t1->team_name ?? '—';
            $awayName = $t2->team_name ?? '—';
            $homePts  = $ptsByEvent->get($e->event_id, collect())->get($homeName, null);
            $awayPts  = $ptsByEvent->get($e->event_id, collect())->get($awayName, null);
    
            // Prefer score from logs; if missing, rebuild using aligned (home/away) totals
            $scoreStr = $e->score_str;
            if (!$scoreStr && is_int($homePts) && is_int($awayPts)) {
                $scoreStr = "{$homePts}-{$awayPts}";
            }
            if (!$scoreStr) $scoreStr = '—';
    
            // Winner name (aligned to what we display)
            $winner = '—';
            if (is_int($homePts) && is_int($awayPts)) {
                $winner = ($homePts > $awayPts) ? $homeName : (($awayPts > $homePts) ? $awayName : 'Tie');
            } else {
                // Fallback: try to parse "124-94"
                if (preg_match('/(\d+)\s*-\s*(\d+)/', $scoreStr, $m)) {
                    $winner = ((int)$m[1] > (int)$m[2]) ? $homeName : ($homeName === '—' || (int)$m[2] > (int)$m[1] ? $awayName : 'Tie');
                }
            }
    
            return [
                'event_id'   => (int) $e->event_id,
                'date_iso'   => $e->game_date,
                'date_disp'  => $e->game_date ? Carbon::parse($e->game_date)->format('Y-m-d H:i') : '—',
    
                'home_id'    => $t1->team_id    ?? null,
                'home_name'  => $homeName,
                'home_logo'  => $t1->team_logo  ?? null,
    
                'away_id'    => $t2->team_id    ?? null,
                'away_name'  => $awayName,
                'away_logo'  => $t2->team_logo  ?? null,
    
                'score'      => $scoreStr,
                'winner'     => $winner,
            ];
        });
    
        // Rewrap into paginator that mirrors the DB paginator (keeps your custom links)
        $rows = new LengthAwarePaginator(
            $rows,
            $events->total(),
            $events->perPage(),
            $events->currentPage(),
            ['path' => $events->path(), 'pageName' => $events->getPageName()]
        );
    
        return view('nba.games.all', [
            'rows'      => $rows,
            'seasons'   => $seasons,
            'from'      => $from,
            'to'        => $to,
            'teamQuery' => $team,
            'winnerQ'   => $winnerLike,
            'per'       => $per,
            'legend'    => [
                ['Datums/Laiks', 'Spēles datums un laiks kurā tā notika'],
                ['Rezultāts',     'Gala rezultāts'],
                ['Mājinieki/Viesi', 'Kura komanda spēlēja mājas spēli un kura bija viesos'],
                ['Uzvarētājs',    'Kura koamnda uzvarēja spēli'],
            ],
        ]);
    }
    
    

    public function show($id)
    {
        $game = $this->nba->showGame($id);
        return view('nba.games.show', ['game' => $game['response'][0] ?? null]);
    }
}
