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
    $winnerLike = trim((string) $request->query('winner', ''));    // winner team text (UI only; server now derives winner strictly from score_str)
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

    // -------- Order & paginate at the DB level (preserves counts) --------
    // NOTE: We no longer compute or filter winner in SQL because winner must come strictly from score_str.
    $base = DB::query()->fromSub($eventsSub, 'e')
        ->orderByDesc('e.game_date');

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
    // (2) per-team total points to build/align scores (DISPLAY ONLY; NOT for winner),
    // (3) winner strictly from score_str (parsed), never from calculated totals.
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
            // Map team_name => pts for quick lookup (display fallback only)
            return $rows->mapWithKeys(fn($r) => [$r->team_name => (int)$r->pts]);
        });

    // Build rows aligned to the two derived teams
    $rows = collect($events->items())->map(function ($e) use ($teamsByEvent, $ptsByEvent) {
        [$t1, $t2] = $teamsByEvent->get($e->event_id, [null, null]);

        $homeName = $t1->team_name ?? '—';
        $awayName = $t2->team_name ?? '—';

        // Totals are used ONLY to display a score if score_str is missing.
        $homePtsFromTotals = $ptsByEvent->get($e->event_id, collect())->get($homeName, null);
        $awayPtsFromTotals = $ptsByEvent->get($e->event_id, collect())->get($awayName, null);

        // Prefer score from logs
        $scoreStr = $e->score_str;
        if (!$scoreStr && is_int($homePtsFromTotals) && is_int($awayPtsFromTotals)) {
            // Display fallback: reconstruct score for UI only
            $scoreStr = "{$homePtsFromTotals}-{$awayPtsFromTotals}";
        }
        if (!$scoreStr) $scoreStr = '—';

        // Winner MUST come from score_str parsing only.
        // We never infer winner from calculated totals.
        $winner = '—';
        if (preg_match('/(\d+)\s*-\s*(\d+)/', (string)$e->score_str, $m)) {
            $left  = (int)$m[1]; // assumed Home
            $right = (int)$m[2]; // assumed Away

            if ($left > $right) {
                $winner = $homeName;
            } elseif ($right > $left) {
                $winner = $awayName;
            } else {
                // NBA cannot tie; equal parsed scores indicate bad data.
                // Do not pick a winner; leave as '—'.
                $winner = '—';
            }
        } // else: malformed/missing score_str => no winner

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

    // (Optional) If you want to apply a winner text filter, it must be client-side now,
    // because winner is derived strictly from score_str. This only filters the current page.
    if ($winnerLike !== '') {
        $like = mb_strtolower($winnerLike);
        $rows = $rows->filter(function ($r) use ($like) {
            return $r['winner'] !== '—' && str_contains(mb_strtolower($r['winner']), $like);
        })->values();
    }

    // Rewrap into paginator that mirrors the DB paginator (keeps your custom links)
    $rows = new LengthAwarePaginator(
        $rows,
        $events->total(),            // NOTE: If you applied client-side winner filter above, totals won't reflect it.
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
