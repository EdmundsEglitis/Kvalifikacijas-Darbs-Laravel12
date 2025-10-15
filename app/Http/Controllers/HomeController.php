<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\News;
use App\Models\Game;
use App\Models\NbaPlayerGamelog;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $desiredSlots = ['secondary-1', 'secondary-2'];

        $newsCandidates = News::query()
            ->whereNull('league_id')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id','title','content','created_at','position','hero_image'])
            ->map(function ($n) {
                $n->preview_image = $this->extractNewsImage($n->hero_image, $n->content) ?: asset('placeholder-news.jpg');
                $n->excerpt       = $this->makeExcerpt($n->content);   // now sanitized
                return $n;
            });

        $bySlot = [];
        foreach ($desiredSlots as $slot) {
            $slotItem = $newsCandidates->firstWhere('position', $slot);
            if (!$slotItem) {
                $slotItem = $newsCandidates->first(function ($x) use ($bySlot) {
                    $used = array_map(fn($it) => $it->id, $bySlot);
                    return !in_array($x->id, $used, true);
                });
            }
            if ($slotItem) {
                $bySlot[$slot] = $slotItem;
            }
        }

        $news = News::query()
            ->whereNull('league_id')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id','title','content','created_at','position','hero_image'])
            ->map(function ($n) {
                return [
                    'id'            => $n->id,
                    'title'         => $n->title,
                    'created_at'    => $n->created_at,
                    'preview_image' => $this->extractNewsImage($n->hero_image, $n->content) ?: asset('placeholder-news.jpg'),
                    'excerpt'       => $this->makeExcerpt($n->content, 160),  // sanitized
                ];
            });

        $latestDate = \App\Models\NbaPlayerGamelog::query()
            ->whereNotNull('score')
            ->whereNotNull('game_date')
            ->max('game_date');

        $nba = null;

        if ($latestDate) {
            $lastNba = \App\Models\NbaPlayerGamelog::query()
                ->join('nba_players as p', 'p.external_id', '=', 'nba_player_game_logs.player_external_id')
                ->where('nba_player_game_logs.game_date', $latestDate)
                ->whereNotNull('nba_player_game_logs.score')
                ->orderByDesc('nba_player_game_logs.updated_at')
                ->orderByDesc('nba_player_game_logs.id')
                ->selectRaw('
                    nba_player_game_logs.game_date as game_date,
                    nba_player_game_logs.score     as score,
                    p.team_name                    as team_name,
                    p.team_logo                    as team_logo,
                    nba_player_game_logs.opponent_name  as opp_name,
                    nba_player_game_logs.opponent_logo  as opp_logo
                ')
                ->first();

            if ($lastNba) {
                [$s1, $s2] = $this->splitScore(trim((string)$lastNba->score));

                $nba = [
                    'date'   => $lastNba->game_date,
                    'team1'  => ['name' => $lastNba->team_name, 'logo' => $lastNba->team_logo],
                    'team2'  => ['name' => $lastNba->opp_name,  'logo' => $lastNba->opp_logo],
                    'score1' => $s1,
                    'score2' => $s2,
                    'league' => 'NBA',
                ];
            }
        }

        $lastLbsGame = Game::query()
            ->with(['team1.league', 'team2.league'])
            ->whereNotNull('winner_id')
            ->whereNotNull('score')
            ->orderByDesc('date')
            ->first();

        $lbs = null;
        if ($lastLbsGame) {
            [$ls1, $ls2] = $this->splitScore($lastLbsGame->score);
            $leagueName  = $lastLbsGame->team1->league->name
                        ?? $lastLbsGame->team2->league->name
                        ?? 'LBS';

            $lbs = [
                'date'   => $lastLbsGame->date,
                'team1'  => [
                    'name' => $lastLbsGame->team1->name,
                    'logo' => $lastLbsGame->team1->logo ?? null,
                ],
                'team2'  => [
                    'name' => $lastLbsGame->team2->name,
                    'logo' => $lastLbsGame->team2->logo ?? null,
                ],
                'score1' => $ls1,
                'score2' => $ls2,
                'league' => $leagueName,
            ];
        }

        $year = (int) date('Y');

        $base = NbaPlayerGamelog::query()
            ->join('nba_players as p', 'p.external_id', '=', 'nba_player_game_logs.player_external_id')
            ->leftJoin('nba_player_details as d', 'd.external_id', '=', 'p.external_id')
            ->whereRaw('YEAR(nba_player_game_logs.game_date) = ?', [$year])
            ->groupBy('p.external_id','p.full_name','p.first_name','p.last_name','p.team_name','p.team_logo','d.headshot_href','p.image')
            ->selectRaw('
                p.external_id as player_id,
                COALESCE(p.full_name, CONCAT(p.first_name," ",p.last_name)) as name,
                p.team_name as team,
                p.team_logo as logo,
                COALESCE(d.headshot_href, p.image) as headshot,
                COUNT(*) as games,
                AVG(points)   as ppg,
                AVG(rebounds) as rpg,
                AVG(assists)  as apg,
                AVG(steals)   as spg,
                AVG(blocks)   as bpg,
                AVG(turnovers) as tpg
            ');

        $bestPpg  = (clone $base)->having('games','>=',10)->orderByDesc('ppg')->first();
        $worstPpg = (clone $base)->having('games','>=',10)->orderBy('ppg')->first();

        $baseOverall = (clone $base)->selectRaw('
            (AVG(points) + 2*AVG(rebounds) + 2*AVG(assists) + 1.5*AVG(steals) + 1.5*AVG(blocks) - 1.5*AVG(turnovers)) as overall
        ');
        $bestOverall  = (clone $baseOverall)->having('games','>=',10)->orderByDesc('overall')->first();
        $worstOverall = (clone $baseOverall)->having('games','>=',10)->orderBy('overall')->first();

        return view('home', [
            'bySlot'       => $bySlot,
            'news'         => $news,
            'nba'          => $nba,
            'lbs'          => $lbs,
            'bestOverall'  => $bestOverall,
            'worstOverall' => $worstOverall
        ]);
    }

    private function splitScore(?string $score): array
    {
        if (!$score) {
            return [null, null];
        }

        $norm = trim(str_replace(
            ['–', '—', '−', ':', '|', '/', '\\', 'to'],
            '-',
            $score
        ));

        if (preg_match_all('/\d+/', $norm, $m) >= 2) {
            return [ (int)$m[0][0], (int)$m[0][1] ];
        }

        $parts = array_map('trim', explode('-', $norm));
        $a = isset($parts[0]) && $parts[0] !== '' ? (int)preg_replace('/\D/', '', $parts[0]) : null;
        $b = isset($parts[1]) && $parts[1] !== '' ? (int)preg_replace('/\D/', '', $parts[1]) : null;

        if ($a !== null && $b === null && strlen((string)$a) >= 4 && strlen((string)$a) % 2 === 0) {
            $s   = (string)$a;
            $mid = (int)(strlen($s) / 2);
            $a   = (int)substr($s, 0, $mid);
            $b   = (int)substr($s, $mid);
        }

        return [$a, $b];
    }

    private function extractNewsImage(?string $heroImage, ?string $content): ?string
    {
        if ($heroImage) {
            return preg_match('~^https?://~i', $heroImage) ? $heroImage : asset($heroImage);
        }
        if (!$content) return null;

        if (preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', $content, $m)) {
            return $m[1];
        }

        $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~data-trix-attachment=["\']({.*?})["\']~i', $decoded, $mm)) {
            $json  = $mm[1];
            $data  = json_decode($json, true);
            if (is_array($data)) {
                if (!empty($data['url']))  return $data['url'];
                if (!empty($data['href'])) return $data['href'];
            }
        }

        if (preg_match('~https?://[^\s"\']+\.(png|jpe?g|webp|gif)~i', $decoded, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * Remove TRIX <figure> blocks (which include filename & KB) and any <img> tags.
     */
    private function sanitizeNewsHtml(string $html): string
    {
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 1) Drop entire TRIX attachments (figures) so captions like "file.png 131 KB" disappear
        $withoutFigures = preg_replace('/<figure\b[^>]*>.*?<\/figure>/is', '', $decoded);

        // 2) Drop any stray <img> tags that might remain
        $withoutImgs = preg_replace('/<img\b[^>]*>/i', '', $withoutFigures);

        return $withoutImgs ?? '';
    }

    private function makeExcerpt(?string $content, int $len = 140): string
    {
        if (!$content) return '';

        // Sanitize first to avoid showing filenames / sizes from TRIX captions.
        $clean = $this->sanitizeNewsHtml($content);

        // Then plain-text it.
        $text = strip_tags($clean);
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        return mb_strlen($text) <= $len ? $text : (mb_substr($text, 0, $len - 1) . '…');
    }
}
