<?php

namespace App\Http\Controllers\Lbs\Leagues;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\HeroImage;
use App\Models\League;
use App\Models\News;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubleagueController extends Controller
{
    public function show($id)
    {
        $subLeague = League::findOrFail($id);
        return view('lbs.leagues.subleagues.show', compact('subLeague'));
    }

    public function teams($id)
    {
        $subLeague = League::with('teams')->findOrFail($id);
        return view('lbs.leagues.subleagues.teams', [
            'subLeague' => $subLeague,
            'teams' => $subLeague->teams,
        ]);
    }

    public function news($id)
    {
        $subLeague = League::findOrFail($id);
        $parentLeagues = League::whereNull('parent_id')->get();

        $heroImage = HeroImage::where('league_id', $subLeague->id)
            ->latest('created_at')
            ->first();

        $slots = ['secondary-1','secondary-2','slot-1','slot-2','slot-3'];

        $bySlot = collect($slots)->mapWithKeys(function (string $slot) use ($subLeague) {
            $item = News::where('league_id', $subLeague->id)
                ->where('position', $slot)
                ->latest('created_at')
                ->first();

            if (! $item) return [];

            $clean = preg_replace('/<figure.*?<\/figure>/is', '', $item->content ?? '');
            $item->excerpt = Str::limit(strip_tags($clean), 150, '…');

            if (empty($item->preview_image)) {
                libxml_use_internal_errors(true);
                $doc = new \DOMDocument();
                $doc->loadHTML('<?xml encoding="utf-8" ?>' . ($item->content ?? ''));
                libxml_clear_errors();
                $img = $doc->getElementsByTagName('img')->item(0);
                $item->preview_image = $img?->getAttribute('src') ?: null;
            }

            return [$slot => $item];
        });

        return view('lbs.leagues.subleagues.news', compact(
            'subLeague',
            'parentLeagues',
            'heroImage',
            'bySlot',
        ));
    }

    public function calendar($id)
    {
        $subLeague     = League::with('teams')->findOrFail($id);
        $parentLeagues = League::whereNull('parent_id')->get();
    
        $teamIds = $subLeague->teams->pluck('id');
        $now     = now();
    
        // Base query scoped to this subleague's teams
        $base = Game::query()
            ->with(['team1', 'team2', 'winner'])
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('team1_id', $teamIds)
                  ->orWhereIn('team2_id', $teamIds);
            });
    
        // Up to 9 future games, soonest first
        $upcomingGames = (clone $base)
            ->where('date', '>=', $now)
            ->orderBy('date', 'asc')
            ->limit(9)
            ->get();
    
        // All past games (if you want, you can also paginate/limit these)
        $pastGames = (clone $base)
            ->where('date', '<', $now)
            ->orderBy('date', 'desc')
            ->get();
    
        // If your view still expects $games, combine both (optional)
        $games = $upcomingGames->concat($pastGames)->values();
    
        return view('lbs.leagues.subleagues.calendar', compact(
            'subLeague',
            'parentLeagues',
            'games',
            'upcomingGames',
            'pastGames'
        ));
    }

    public function stats($id)
    {
        $subLeague = League::with('teams')->findOrFail($id);
        $parentLeagues = League::whereNull('parent_id')->get();

        $teamIds = $subLeague->teams->pluck('id')->toArray();

        $teamsStats = $subLeague->teams->map(function (Team $team) {
            $games = Game::where('team1_id', $team->id)
                ->orWhere('team2_id', $team->id)
                ->get();

            $wins = $games->where('winner_id', $team->id)->count();
            $losses = $games->count() - $wins;

            return [
                'team' => $team,
                'wins' => $wins,
                'losses' => $losses,
                'games_played' => $games->count(),
            ];
        });

        if (empty($teamIds)) {
            $playersAgg = collect();
        } else {
            $playersAgg = DB::table('player_game_stats')
                ->select(
                    'player_id',
                    DB::raw('COUNT(*) as games_played'),
                    DB::raw('AVG(points) as avg_points'),
                    DB::raw('AVG(reb) as avg_reb'),
                    DB::raw('AVG(ast) as avg_ast'),
                    DB::raw('AVG(stl) as avg_stl'),
                    DB::raw('AVG(blk) as avg_blk'),
                    DB::raw('AVG(eff) as avg_eff')
                )
                ->whereIn('team_id', $teamIds)
                ->groupBy('player_id')
                ->get();
        }

        $playerIds = $playersAgg->pluck('player_id')->unique()->filter()->values()->all();
        $playersById = \App\Models\Player::whereIn('id', $playerIds)
            ->with('team')
            ->get()
            ->keyBy('id');

        $playersStats = $playersAgg->map(function ($row) use ($playersById) {
            $player = $playersById->get($row->player_id);

            return (object) [
                'id' => $row->player_id,
                'name' => $player ? $player->name : ('Player #' . $row->player_id),
                'team' => $player && $player->team ? $player->team : null,
                'games' => (int) $row->games_played,
                'avg_points' => $row->avg_points !== null ? round($row->avg_points, 1) : 0,
                'avg_rebounds' => $row->avg_reb !== null ? round($row->avg_reb, 1) : 0,
                'avg_assists' => $row->avg_ast !== null ? round($row->avg_ast, 1) : 0,
                'avg_steals' => $row->avg_stl !== null ? round($row->avg_stl, 1) : 0,
                'avg_blocks' => $row->avg_blk !== null ? round($row->avg_blk, 1) : 0,
                'avg_eff' => $row->avg_eff !== null ? round($row->avg_eff, 1) : 0,
            ];
        });

        $topPlayers = collect();

        if ($playersAgg->isNotEmpty()) {
            $aggCollection = $playersAgg->mapWithKeys(fn($r) => [$r->player_id => $r]);
            $pickBest = function ($col) use ($aggCollection, $playersById) {
                $best = $aggCollection->sortByDesc($col)->first();
                if (!$best) return null;
                $player = $playersById->get($best->player_id);
                return (object) [
                    'id' => $best->player_id,
                    'name' => $player ? $player->name : ('Player #' . $best->player_id),
                    'team' => $player && $player->team ? $player->team : null,
                    'avg_value' => $best->$col !== null ? round($best->$col, 1) : 0,
                ];
            };

            $topPlayers['points']   = $pickBest('avg_points');
            $topPlayers['rebounds'] = $pickBest('avg_reb');
            $topPlayers['assists']  = $pickBest('avg_ast');
            $topPlayers['steals']   = $pickBest('avg_stl');
            $topPlayers['blocks']   = $pickBest('avg_blk');
            $topPlayers['eff']      = $pickBest('avg_eff');
        }

        $teamsStats = $teamsStats->sortByDesc(fn($t) => $t['wins'])->values();

        return view('lbs.leagues.subleagues.stats', [
            'subLeague' => $subLeague,
            'parentLeagues' => $parentLeagues,
            'teamsStats' => $teamsStats,
            'topPlayers' => $topPlayers,
            'playersStats' => $playersStats,
        ]);
    }
}
