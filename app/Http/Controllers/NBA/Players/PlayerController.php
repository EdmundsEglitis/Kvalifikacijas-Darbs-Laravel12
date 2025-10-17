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
    public function index(Request $request)
    {
        $page    = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('perPage', 50), 10), 200);
        $q       = trim((string) $request->query('q', ''));
        $sort    = (string) $request->query('sort', 'name');
        $dir     = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = NbaPlayer::query();

        if ($q !== '') {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder
                    ->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($q) . '%'])
                    ->orWhereRaw('LOWER(team_name) LIKE ?', ['%' . strtolower($q) . '%']);
            });
        }

        switch ($sort) {
            case 'team':   $query->orderBy('team_name', $dir); break;
            case 'height': $query->orderByRaw('CAST(display_height as UNSIGNED) ' . $dir); break;
            case 'weight': $query->orderByRaw('CAST(display_weight as UNSIGNED) ' . $dir); break;
            case 'name':
            default:       $query->orderBy('full_name', $dir); break;
        }

        $players = $query->paginate($perPage, ['*'], 'page', $page);

        return view('nba.players.index', ['players' => $players]);
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
