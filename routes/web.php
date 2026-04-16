<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;

use App\Http\Controllers\Nba\HomeController as NbaHomeController;
use App\Http\Controllers\Nba\Players\PlayerController as NbaPlayerController;
use App\Http\Controllers\Nba\Teams\TeamController as NbaTeamController;
use App\Http\Controllers\Nba\Games\GameController as NbaGameController;
use App\Http\Controllers\Nba\Games\GameShowController as NbaGameShowController;
use App\Http\Controllers\Nba\Standings\StandingsController as NbaStandingsController;


use App\Http\Controllers\Lbs\HomeController as LbsHomeController;
use App\Http\Controllers\Lbs\NewsController as LbsNewsController;
use App\Http\Controllers\Lbs\Leagues\ParentLeagueController;
use App\Http\Controllers\Lbs\Leagues\SubleagueController;
use App\Http\Controllers\Lbs\Teams\TeamController as LbsTeamController;
use App\Http\Controllers\Lbs\Games\GameController as LbsGameController;
use App\Http\Controllers\Lbs\Players\PlayerController as LbsPlayerController;
use App\Http\Controllers\Lbs\Teams\CompareController as LbsTeamsCompare;
use App\Http\Controllers\Lbs\Players\CompareController as LbsplayersCompare;
use App\Http\Controllers\CrossLeagueCompareController as CrossLeagueCompareController;
use App\Http\Controllers\CronController;

Route::get('/', [HomeController::class, 'index'])->name('home');


Route::get('/compare/nba-vs-lbs', [CrossLeagueCompareController::class, 'explorer'])
    ->name('compare.nba-lbs');

    Route::get('/compare/nba-vs-lbs-teams', [CrossLeagueCompareController::class, 'teamsExplorer'])
    ->name('compare.nba-lbs-teams');

// NBA sections
Route::prefix('nba')->name('nba.')->group(function () {
    Route::get('/',               [NbaHomeController::class, 'home'])->name('home');

    // Players
    Route::get('/players',        [NbaPlayerController::class, 'index'])->name('players');
    Route::get('/players/{id}',   [NbaPlayerController::class, 'show'])->name('player.show');
    Route::get('/compare/players',[NbaPlayerController::class, 'compare'])->name('compare');

    // Teams
    Route::get('/teams',          [NbaTeamController::class, 'index'])->name('teams');
    Route::get('/teams/{id}',     [NbaTeamController::class, 'show'])->name('team.show');

    // Games
    Route::get('/games',          [NbaGameController::class, 'upcoming'])->name('games.upcoming');
    Route::get('/all-games',      [NbaGameController::class, 'all'])->name('games.all');
    Route::get('/games/{eventId}', [NbaGameShowController::class, 'show'])->name('games.show');
    
    // Standings
    Route::get('/standings/explorer', [NbaStandingsController::class, 'explorer'])->name('standings.explorer');

    // Stats static page (if you have it)
    Route::get('/stats', fn () => view('nba.stats'))->name('stats');
});
















Route::prefix('lbs')->name('lbs.')->group(function () {
    Route::get('/', [LbsHomeController::class, 'home'])->name('home');

    Route::get('/news/{id}', [LbsNewsController::class, 'show'])->name('news.show');
    Route::get('/league/{id}', [ParentLeagueController::class, 'show'])->name('league.show');

    Route::prefix('/sub-league/{id}')->group(function () {
        Route::get('/',         [SubleagueController::class, 'show'])->name('subleague.show');
        Route::get('/news',     [SubleagueController::class, 'news'])->name('subleague.news');
        Route::get('/calendar', [SubleagueController::class, 'calendar'])->name('subleague.calendar');
        Route::get('/teams',    [SubleagueController::class, 'teams'])->name('subleague.teams');
        Route::get('/stats',    [SubleagueController::class, 'stats'])->name('subleague.stats');
    });

    Route::get('/team/{team}',         [LbsTeamController::class, 'show'])->name('team.show');
    Route::get('/team/{team}/games',   [LbsTeamController::class, 'games'])->name('team.games');
    Route::get('/team/{team}/players', [LbsTeamController::class, 'players'])->name('team.players');
    Route::get('/team/{team}/stats',   [LbsTeamController::class, 'stats'])->name('team.stats');

    Route::get('/game/{id}',    [LbsGameController::class, 'show'])->name('game.detail');
    Route::get('/players/{id}', [LbsPlayerController::class, 'show'])->name('player.show');

    // NEW: Compare Teams (no "team" prefix in path)
    Route::get('/compare/teams', [LbsTeamsCompare::class, 'explorer'])->name('compare.teams');
    Route::get('/compare/players', [LbsPlayersCompare::class, 'explorer'])->name('compare.players');
});











//cronjob route
Route::get('/cron/run/{token}', [CronController::class, 'run'])->name('cron.run');
