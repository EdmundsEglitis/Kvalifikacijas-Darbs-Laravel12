<?php

namespace App\Http\Controllers\Nba;

use App\Http\Controllers\Controller;
use App\Models\NbaGame;
use App\Models\NbaPlayerGameLog;
use App\Models\NbaTeam;
use App\Models\NbaStanding;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function home()
    {
        // Next 6 upcoming games (with team names + logos)
        $upcomingGames = NbaGame::query()
            ->where('tipoff', '>=', Carbon::now())
            ->orderBy('tipoff')
            ->take(6)
            ->get([
                'id',
                'tipoff',
                'home_team_id', 'home_team_name', 'home_team_logo',
                'away_team_id', 'away_team_name', 'away_team_logo',
            ]);

        // Top players by PPG this calendar year with names & photos (nba_players.image)
        $currentYear = (int) date('Y');
        $topPpg = NbaPlayerGameLog::query()
            ->from('nba_player_game_logs as l')
            ->leftJoin('nba_players as p', 'p.external_id', '=', 'l.player_external_id')
            ->select([
                'l.player_external_id',
                DB::raw('AVG(l.points) as ppg'),
                DB::raw('COUNT(*) as g'),
                'p.full_name as player_name',
                'p.image as player_photo',
            ])
            ->whereYear('l.game_date', $currentYear)
            ->groupBy('l.player_external_id', 'p.full_name', 'p.image')
            ->orderByDesc('ppg')
            ->take(6)
            ->get();

        // Standings snapshot (latest season) + join team logos for table and link
        $latestSeason = NbaStanding::max('season');
        $standings = NbaStanding::query()
            ->from('nba_standings as s')
            ->leftJoin('nba_teams as t', 't.external_id', '=', 's.team_id')
            ->where('s.season', $latestSeason)
            ->orderByDesc('s.win_percent')
            ->take(8)
            ->get([
                's.team_id',
                's.team_name',
                's.wins',
                's.losses',
                's.win_percent',
                's.avg_points_for',
                's.avg_points_against',
                's.point_differential',
                't.logo as team_logo',
            ]);

        // Featured teams (alphabetical) w/ logos
        $teams = NbaTeam::orderBy('name')->take(12)->get(['external_id', 'name', 'logo']);

        return view('nba.home', compact(
            'upcomingGames', 'topPpg', 'standings', 'latestSeason', 'teams'
        ));
    }
}
