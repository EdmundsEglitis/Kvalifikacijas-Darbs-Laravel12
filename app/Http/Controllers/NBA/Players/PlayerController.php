<?php

namespace App\Http\Controllers\Nba\Players;

use App\Http\Controllers\Controller;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\NbaPlayerGamelog;

class PlayerController extends Controller
{
    /**
     * Parse "6' 7\"" or "6-7" etc. into total inches.
     */
    private static function parseHeightInches(?string $s): ?int
    {
        if (!$s) return null;
        $t = trim($s);

        // Try common "6' 7\"" pattern
        if (preg_match('/^\s*(\d+)\s*[\'ft]\s*(\d+)?\s*(?:["in])?\s*$/i', $t, $m)) {
            $feet  = (int)($m[1] ?? 0);
            $inch  = (int)($m[2] ?? 0);
            return $feet * 12 + $inch;
        }

        // Try "6-7"
        if (preg_match('/^\s*(\d+)\s*[-–]\s*(\d+)\s*$/', $t, $m)) {
            $feet = (int)$m[1];
            $inch = (int)$m[2];
            return $feet * 12 + $inch;
        }

        // Try just a number that already looks like inches
        if (preg_match('/^\s*(\d+)\s*in(?:ches)?\s*$/i', $t, $m)) {
            return (int)$m[1];
        }

        // As a last resort, read first two numbers as feet & inches
        if (preg_match_all('/\d+/', $t, $m) && !empty($m[0])) {
            $feet = (int)$m[0][0];
            $inch = isset($m[0][1]) ? (int)$m[0][1] : 0;
            return $feet * 12 + $inch;
        }

        return null;
    }

    /**
     * Parse "215 lbs" (or any string with a number) into pounds.
     */
    private static function parseWeightLbs(?string $s): ?float
    {
        if (!$s) return null;
        if (preg_match('/(\d+(?:\.\d+)?)/', $s, $m)) {
            return (float)$m[1];
        }
        return null;
    }

    public function index(Request $request)
    {
        $page    = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('perPage', 50), 10), 200);
        $q       = trim((string) $request->query('q', ''));
        $sort    = (string) $request->query('sort', 'name');  // name|team|height|weight
        $dir     = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // ---- MySQL parse helpers (from display strings) ----
        // 1st number = feet, 2nd number (if present) = inches
        $feetExpr   = "REGEXP_SUBSTR(display_height, '[0-9]+', 1, 1)";
        $inchExpr   = "COALESCE(REGEXP_SUBSTR(display_height, '[0-9]+', 1, 2), '0')";
        $heightExpr = "CASE
            WHEN display_height IS NULL OR display_height = '' THEN NULL
            WHEN $feetExpr IS NULL THEN NULL
            ELSE CAST($feetExpr AS UNSIGNED) * 12 + CAST($inchExpr AS UNSIGNED)
        END";

        // first number found inside '215 lbs' etc.
        $weightExpr = "CASE
            WHEN display_weight IS NULL OR display_weight = '' THEN NULL
            ELSE CAST(REGEXP_SUBSTR(display_weight, '[0-9]+', 1, 1) AS UNSIGNED)
        END";

        // prefer Last, First; fallback to full_name
        $sortName   = "TRIM(CONCAT(COALESCE(last_name,''), ', ', COALESCE(first_name,'')))";

        $query = NbaPlayer::query()
            ->select('*')
            ->selectRaw("$heightExpr AS height_in")
            ->selectRaw("$weightExpr AS weight_num")
            ->selectRaw("$sortName  AS sort_name")
            // ---- Metric numbers (cm / kg) ----
            ->selectRaw("CASE WHEN ($heightExpr) IS NULL THEN NULL ELSE ROUND(($heightExpr) * 2.54) END AS height_cm")
            ->selectRaw("CASE WHEN ($weightExpr) IS NULL THEN NULL ELSE ROUND(($weightExpr) * 0.453592, 1) END AS weight_kg");

        // ---- Search (case-insensitive) ----
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($qb) use ($like) {
                $qb->whereRaw('LOWER(full_name)  LIKE ?', [$like])
                   ->orWhereRaw('LOWER(team_name) LIKE ?', [$like])
                   ->orWhereRaw('LOWER(first_name) LIKE ?', [$like])
                   ->orWhereRaw('LOWER(last_name)  LIKE ?', [$like]);
            });
        }

        // ---- Sorting with NULLS LAST emulation ----
        switch ($sort) {
            case 'team':
                $query->orderByRaw('team_name IS NULL ASC')
                      ->orderBy('team_name', $dir);
                break;

            case 'height':
                $query->orderByRaw('height_in IS NULL ASC')
                      ->orderByRaw('height_in '.$dir);
                break;

            case 'weight':
                $query->orderByRaw('weight_num IS NULL ASC')
                      ->orderByRaw('weight_num '.$dir);
                break;

            case 'name':
            default:
                $query->orderByRaw("(CASE WHEN sort_name = ',' OR sort_name = '' THEN 1 ELSE 0 END) ASC")
                      ->orderByRaw("CASE WHEN sort_name = ',' OR sort_name = '' THEN full_name ELSE sort_name END $dir");
                break;
        }

        $players = $query->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        return view('nba.players.index', [
            'players' => $players,

        ]);
    }

    public function show(Request $request, $external_id)
    {
        $player = NbaPlayer::with([
                'gamelogs' => fn ($q) => $q->orderBy('game_date', 'desc'),
                'details'
            ])
            ->where('external_id', $external_id)
            ->firstOrFail();

        $details = $player->details;

        $headerTeam = null;
        if ($details && ($details->team_id ?? null)) {
            $headerTeam = NbaTeam::where('external_id', $details->team_id)->first();
        }
        if (!$headerTeam && ($player->team_id ?? null)) {
            $headerTeam = NbaTeam::where('external_id', $player->team_id)->first();
        }
        if (!$headerTeam && ($player->team_name ?? null)) {
            $headerTeam = NbaTeam::where('name', $player->team_name)
                ->orWhere('short_name', $player->team_name)
                ->orWhere('abbreviation', $player->team_name)
                ->first();
        }

        $teams = NbaTeam::select('external_id','name','short_name','abbreviation','logo')->get();
        $norm = fn (?string $s) => Str::of((string)$s)->lower()
            ->replace(['.', ',', '-', '–', '—', '\''], ' ')
            ->squish()->toString();

        $byName  = $teams->keyBy(fn ($t) => $norm($t->name));
        $byShort = $teams->keyBy(fn ($t) => $norm($t->short_name));
        $byAbbr  = $teams->keyBy(fn ($t) => $norm($t->abbreviation));

        $gamelogs = $player->gamelogs->map(function ($log) use ($norm, $byName, $byShort, $byAbbr) {
            $key = $norm($log->opponent_name);
            $hit = $byName->get($key) ?? $byShort->get($key) ?? $byAbbr->get($key);

            if (!$hit && $key) {
                $hit = $byName->first(fn ($t) => Str::contains($key, $norm($t->name)))
                    ?? $byShort->first(fn ($t) => Str::contains($key, $norm($t->short_name)))
                    ?? $byAbbr->first(fn ($t) => Str::contains($key, $norm($t->abbreviation)));
            }

            if ($hit) {
                $log->setAttribute('opponent_team_id',   $hit->external_id);
                $log->setAttribute('opponent_team_name', $hit->name);
                $log->setAttribute('opponent_team_logo', $hit->logo);
            }

            return $log;
        });

        $cleanStatus = null;
        if ($details && !empty($details->status_name)) {
            $cleanStatus = $details->status_name;
        } elseif ($details && !is_null($details->active)) {
            $cleanStatus = $details->active ? 'Active' : 'Inactive';
        }

        // ---- Metric conversions from details' display strings ----
        $heightIn = self::parseHeightInches($details->display_height ?? null);
        $weightLb = self::parseWeightLbs($details->display_weight ?? null);

        $metrics = [
            'height_cm' => is_null($heightIn) ? null : (int) round($heightIn * 2.54),
            'weight_kg' => is_null($weightLb) ? null : round($weightLb * 0.453592, 1),
        ];

        $career = $player->gamelogs()
            ->selectRaw('COUNT(*) as games, AVG(points) as pts, AVG(rebounds) as reb, AVG(assists) as ast, AVG(steals) as stl, AVG(blocks) as blk, AVG(minutes) as min')
            ->first();

        $currentYear = now()->year;
        $season = $player->gamelogs()
            ->whereYear('game_date', $currentYear)
            ->selectRaw('COUNT(*) as games, AVG(points) as pts, AVG(rebounds) as reb, AVG(assists) as ast, AVG(steals) as stl, AVG(blocks) as blk, AVG(minutes) as min')
            ->first();

        return view('nba.players.show', [
            'player'        => $player,
            'details'       => $details,
            'teamHeader'    => $headerTeam,
            'gamelogs'      => $gamelogs,
            'cleanStatus'   => $cleanStatus,
            'career'        => $career,
            'season'        => $season,
            // Use in Blade e.g.:
            // Augums: {{ $details->display_height ?? '-' }} @if($metrics['height_cm']) ({{ $metrics['height_cm'] }} cm) @endif
            // Svars:  {{ $details->display_weight ?? '-' }} @if($metrics['weight_kg']) ({{ $metrics['weight_kg'] }} kg) @endif
            'metrics'       => $metrics,
        ]);
    }

    public function compare(Request $request)
    {
        $seasonRows = NbaPlayerGamelog::query()
            ->selectRaw('DISTINCT YEAR(game_date) AS season')
            ->orderByDesc('season')
            ->pluck('season')
            ->toArray();

        $minSeason = $seasonRows ? min($seasonRows) : 2021;
        $maxSeason = $seasonRows ? max($seasonRows) : (int) date('Y');

        $from = (int) $request->input('from', $minSeason);
        $to   = (int) $request->input('to', $maxSeason);
        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $teamQuery   = trim((string) $request->input('team', ''));
        $playerQuery = trim((string) $request->input('player', ''));

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(10, min($perPage, 100));

        $agg = NbaPlayerGamelog::query()
            ->join('nba_players as p', 'p.external_id', '=', 'nba_player_game_logs.player_external_id')
            ->leftJoin('nba_player_details as d', 'd.external_id', '=', 'p.external_id')
            ->leftJoin('nba_teams as t', 't.external_id', '=', 'p.team_id')
            ->selectRaw('
                YEAR(nba_player_game_logs.game_date) as season,
                p.external_id as player_id,
                COALESCE(p.full_name, CONCAT(p.first_name," ",p.last_name)) as player_name,
                p.team_id as team_id,
                p.team_name as team_name,
                t.abbreviation as team_abbr,
                p.team_logo as p_logo,
                t.logo as t_logo,
                COALESCE(d.headshot_href, p.image) as headshot,
                COUNT(*) as games,
                SUM(CASE WHEN UPPER(TRIM(result)) LIKE "W%" THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN UPPER(TRIM(result)) LIKE "L%" THEN 1 ELSE 0 END) as losses,
                AVG(points) as ppg, AVG(rebounds) as rpg, AVG(assists) as apg,
                AVG(steals) as spg, AVG(blocks) as bpg, AVG(turnovers) as tpg, AVG(minutes) as mpg,
                AVG(fg_pct) as fg_pct, AVG(three_pt_pct) as three_pt_pct, AVG(ft_pct) as ft_pct
            ')
            ->when($from, fn($q) => $q->whereRaw('YEAR(nba_player_game_logs.game_date) >= ?', [$from]))
            ->when($to,   fn($q) => $q->whereRaw('YEAR(nba_player_game_logs.game_date) <= ?', [$to]))
            ->when($teamQuery !== '', function ($q) use ($teamQuery) {
                $like = "%{$teamQuery}%";
                $q->where(function ($sub) use ($like) {
                    $sub->where('p.team_name', 'like', $like)
                        ->orWhere('t.abbreviation', 'like', $like);
                });
            })
            ->when($playerQuery !== '', function ($q) use ($playerQuery) {
                $like = "%{$playerQuery}%";
                $q->where(function ($sub) use ($like) {
                    $sub->where('p.full_name', 'like', $like)
                        ->orWhere('p.first_name', 'like', $like)
                        ->orWhere('p.last_name', 'like', $like);
                });
            })
            ->groupBy('season','player_id','player_name','team_id','team_name','team_abbr','p_logo','t_logo','headshot')
            ->orderByDesc('season')
            ->orderBy('player_name');

        $paginator = $agg->paginate($perPage)->withQueryString();

        $percentFmt = function ($v) {
            if ($v === null) return '—';
            $n = (float)$v;
            return $n <= 1 ? number_format($n * 100, 1) . '%' : number_format($n, 1) . '%';
        };

        $mapped = $paginator->getCollection()->map(function ($r) use ($percentFmt) {
            $one  = fn($v) => $v !== null ? number_format($v, 1) : '—';
            $logo = $r->p_logo ?: $r->t_logo;

            $payload = json_encode([
                'season'   => (int) $r->season,
                'player'   => $r->player_name,
                'player_id'=> (int) $r->player_id,
                'team'     => $r->team_name,
                'abbr'     => $r->team_abbr,
                'logo'     => $logo,
                'headshot' => $r->headshot,
                'games'    => (int) $r->games,
                'wins'     => (int) $r->wins,
                'losses'   => (int) $r->losses,
                'ppg'      => $r->ppg, 'rpg' => $r->rpg, 'apg' => $r->apg,
                'spg'      => $r->spg, 'bpg' => $r->bpg, 'tpg' => $r->tpg, 'mpg' => $r->mpg,
                'fg_pct'   => $r->fg_pct, 'tp_pct' => $r->three_pt_pct, 'ft_pct' => $r->ft_pct,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return [
                'season'     => (int) $r->season,
                'player_id'  => (int) $r->player_id,
                'player'     => $r->player_name,
                'team_id'    => (int) $r->team_id,
                'team'       => $r->team_name,
                'abbr'       => $r->team_abbr,
                'logo'       => $logo,
                'headshot'   => $r->headshot,
                'games'      => (int) $r->games,
                'wins'       => (int) $r->wins,
                'losses'     => (int) $r->losses,
                'wl_text'    => $r->wins.'–'.$r->losses,
                'ppg'        => $one($r->ppg),
                'rpg'        => $one($r->rpg),
                'apg'        => $one($r->apg),
                'spg'        => $one($r->spg),
                'bpg'        => $one($r->bpg),
                'tpg'        => $one($r->tpg),
                'mpg'        => $one($r->mpg),
                'fg_pct'     => $percentFmt($r->fg_pct),
                'tp_pct'     => $percentFmt($r->three_pt_pct),
                'ft_pct'     => $percentFmt($r->ft_pct),
                'data_text'  => strtolower(trim(($r->player_name ?? '').' '.$r->team_name.' '.($r->team_abbr ?? ''))),
                'payload'    => $payload,
            ];
        });

        $paginator->setCollection($mapped);

        return view('nba.players.compare', [
            'seasons'     => array_values($seasonRows),
            'from'        => $from,
            'to'          => $to,
            'teamQuery'   => $teamQuery,
            'playerQuery' => $playerQuery,
            'rows'        => $paginator,
            'legend'      => [
                ['U/Z', 'Komandas bilance spēlēs, kurās spēlētājs piedalījās.'],
                ['PPG / RPG / APG', 'Punkti / Atlēkušās bumbas / Rezultativās piespēles vidēji spēlē.'],
                ['SPG / BPG', 'Pārtvertās bumbas / Bloki vidēji spēlē.'],
                ['TOV', 'Kļūdas vidēji spēlē (jo mazāk, jo labāk).'],
                ['MPG', 'Minūtes vidēji spēlē.'],
                ['FG% / 3P% / FT%', 'Metienu precizitāte (aprēķināta no žurnālu ierakstiem).'],
            ],
        ]);
    }
}
