<?php

namespace App\Http\Controllers\Nba\Standings;

use App\Http\Controllers\Controller;
use App\Models\NbaStanding;
use App\Models\NbaTeam;
use Illuminate\Http\Request;

class StandingsController extends Controller
{
    // /nba/standings/explorer
    public function explorer(Request $request)
    {
        $seasons = NbaStanding::query()
            ->select('season')->distinct()->orderBy('season', 'desc')
            ->pluck('season')->toArray();

        $minSeason = $seasons ? min($seasons) : 2021;
        $maxSeason = $seasons ? max($seasons) : (int) date('Y');

        $from = (int) $request->input('from', $minSeason);
        $to   = (int) $request->input('to', $maxSeason);
        if ($from > $to) { [$from, $to] = [$to, $from]; }

        $teamQuery = trim((string) $request->input('team', ''));

        $q = NbaStanding::query()
            ->when($from, fn($qq) => $qq->where('season', '>=', $from))
            ->when($to,   fn($qq) => $qq->where('season', '<=', $to))
            ->when($teamQuery !== '', function ($qq) use ($teamQuery) {
                $qq->where(function ($sub) use ($teamQuery) {
                    $sub->where('team_name', 'like', "%{$teamQuery}%")
                        ->orWhere('abbreviation', 'like', "%{$teamQuery}%");
                });
            })
            ->orderBy('season', 'desc')->orderBy('team_name');

        $collection = $q->get();

        // map team_id -> logo url
        $teamIds = $collection->pluck('team_id')->unique()->values();
        $teams   = NbaTeam::whereIn('external_id', $teamIds)->get(['external_id','abbreviation','logo','logo_dark']);

        $logoMap = [];
        foreach ($teams as $t) {
            $abbr = strtolower($t->abbreviation ?? '');
            $fallback = $abbr ? "https://a.espncdn.com/i/teamlogos/nba/500/{$abbr}.png" : null;
            $logoMap[$t->external_id] = $t->logo ?: $fallback;
        }

        // helper: decode clincher codes → badges
        $decodeClincher = function (?string $raw) {
            $raw = strtolower(trim((string)$raw));
            if ($raw === '') return [];

            $map = [
                'z'  => ['Best record in conference', 'bg-purple-500/20 text-purple-300 border-purple-500/30'],
                '*'  => ['Best record in league',     'bg-amber-500/20 text-amber-300 border-amber-500/30'],
                'y'  => ['Clinched division title',   'bg-teal-500/20 text-teal-300 border-teal-500/30'],
                'x'  => ['Clinched playoff berth',    'bg-green-500/20 text-green-300 border-green-500/30'],
                'pb' => ['Clinched Play-In',          'bg-blue-500/20 text-blue-300 border-blue-500/30'],
                'pi' => ['Play-In eligible',          'bg-blue-500/20 text-blue-300 border-blue-500/30'],
                'e'  => ['Eliminated from playoffs',  'bg-red-500/20 text-red-300 border-red-500/30'],
            ];

            $tokens = [];

            // detect 2-letter tokens first
            foreach (['pb','pi'] as $two) {
                if (str_contains($raw, $two)) {
                    $tokens[] = ['code' => $two, 'label' => $map[$two][0], 'cls' => $map[$two][1]];
                    $raw = str_replace($two, '', $raw);
                }
            }

            // then 1-letter tokens
            foreach (str_split($raw) as $ch) {
                if (isset($map[$ch])) {
                    $tokens[] = ['code' => $ch, 'label' => $map[$ch][0], 'cls' => $map[$ch][1]];
                }
            }

            return $tokens;
        };

        $rows = $collection->map(function (NbaStanding $r) use ($logoMap, $decodeClincher) {
            $winPercentDisp = $r->win_percent !== null? (string) round($r->win_percent * 100) . '%': '—';
            $ppgFmt    = $r->avg_points_for      !== null ? number_format($r->avg_points_for, 1)      : '—';
            $oppPpgFmt = $r->avg_points_against  !== null ? number_format($r->avg_points_against, 1)  : '—';

            $diffTxt   = '—';
            $diffClass = 'text-gray-300';
            if ($r->point_differential !== null) {
                $diffTxt   = ($r->point_differential >= 0 ? '+' : '') . $r->point_differential;
                $diffClass = $r->point_differential >= 0 ? 'text-[#84CC16]' : 'text-[#F97316]';
            }

            $streakTxt = '—';
            if (is_int($r->streak)) {
                $streakTxt = $r->streak > 0 ? 'W' . $r->streak
                    : ($r->streak < 0 ? 'L' . abs($r->streak) : '—');
            }

            $teamLogo = $logoMap[$r->team_id] ?? null;

            // decode clincher for table + human text for cards
            $badges = $decodeClincher($r->clincher);
            $clincherHuman = implode(', ', array_map(fn($t) => $t['label'], $badges));

            $payload = json_encode([
                'season'      => $r->season,
                'team'        => $r->team_name,
                'abbr'        => $r->abbreviation,
                'logo'        => $teamLogo,
                'wins'        => $r->wins,
                'losses'      => $r->losses,
                'win_percent' => $r->win_percent,
                'seed'        => $r->playoff_seed,
                'gb'          => $r->games_behind,
                'ppg'         => $r->avg_points_for,
                'opp_ppg'     => $r->avg_points_against,
                'diff'        => $r->point_differential,
                'win_percent_fmt' => $winPercentDisp,
                'home'        => $r->home_record,
                'road'        => $r->road_record,
                'l10'         => $r->last_ten,
                'streak'      => $r->streak,
                'clincher'       => $r->clincher,
                'clincher_human' => $clincherHuman ?: null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return [
                'team_id'            => $r->team_id,
                'team_name'          => $r->team_name,
                'abbreviation'       => $r->abbreviation,
                'season'             => $r->season,
                'wins'               => $r->wins,
                'losses'             => $r->losses,
                'win_percent'        => $r->win_percent,
                'playoff_seed'       => $r->playoff_seed,
                'games_behind'       => $r->games_behind,
                'avg_points_for'     => $r->avg_points_for,
                'avg_points_against' => $r->avg_points_against,
                'point_differential' => $r->point_differential,
                'home_record'        => $r->home_record,
                'road_record'        => $r->road_record,
                'last_ten'           => $r->last_ten,
                'streak'             => $r->streak,
                'clincher'           => $r->clincher,
                'clincher_badges'    => $badges,
                'clincher_human'     => $clincherHuman,
                'data_team'          => strtolower(trim(($r->team_name ?? '') . ' ' . ($r->abbreviation ?? ''))),
                'win_percent_fmt'    => $winPercentDisp,
                'ppg_fmt'            => $ppgFmt,
                'opp_ppg_fmt'        => $oppPpgFmt,
                'diff_txt'           => $diffTxt,
                'diff_class'         => $diffClass,
                'streak_txt'         => $streakTxt,
                'team_logo'          => $teamLogo,
                'payload'            => $payload,
            ];
        })->values();

        return view('nba.teams.compare', [
            'seasons'    => $seasons,
            'from'       => $from,
            'to'         => $to,
            'teamQuery'  => $teamQuery,
            'rows'       => $rows,
            'legend'     => [
                ['Record',     'Wins–Losses for the season.'],
                ['Win%',       'Winning percentage (wins ÷ total games).'],
                ['Seed',       'Projected/Final playoff seed.'],
                ['GB',         'Games behind the conference/league leader.'],
                ['PPG',        'Average points scored per game.'],
                ['OPP PPG',    'Average points allowed per game.'],
                ['Diff',       'Points For − Points Against (total).'],
                ['Home/Road',  'Win–loss records at home and away.'],
                ['L10',        'Record in the last 10 games.'],
                ['Streak',     'Current win or loss streak.'],
                ['Clincher',   'Status markers like x, y, z, pb, e.'],
            ],
        ]);
    }
}
