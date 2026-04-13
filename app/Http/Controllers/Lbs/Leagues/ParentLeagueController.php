<?php

namespace App\Http\Controllers\Lbs\Leagues;

use App\Http\Controllers\Controller;
use App\Models\HeroImage;
use App\Models\League;
use App\Models\News;
use Illuminate\Support\Str;

class ParentLeagueController extends Controller
{
    public function show($id)
    {
        $parent = League::with('children')->findOrFail($id);
        $subLeagues = $parent->children;

        $heroImage = HeroImage::where('league_id', $parent->id)
            ->latest('created_at')
            ->first();

        $news = News::whereIn('league_id', $subLeagues->pluck('id')->push($parent->id))
            ->latest()
            ->take(12)
            ->get()
            ->map(function ($item) {
                $clean = preg_replace('/<figure.*?<\/figure>/is', '', $item->content);
                $item->excerpt = Str::limit(strip_tags($clean), 150, '…');

               $item->preview_image = null;

$content = trim((string) ($item->content ?? ''));
if ($content !== '') {
    libxml_use_internal_errors(true);
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
    libxml_clear_errors();

    $imgNode = $doc->getElementsByTagName('img')->item(0);

    if ($imgNode) {
        $src = trim((string) $imgNode->getAttribute('src'));

        // If it's a relative path, convert to absolute (assuming it’s served locally)
        if ($src !== '' && !preg_match('~^https?://~i', $src)) {
            // handle leading slashes or storage paths gracefully
            $src = asset(ltrim($src, '/'));
        }

        $item->preview_image = $src ?: null;
    }
}


                return $item;
            });

        return view('lbs.leagues.parent', compact('parent', 'subLeagues', 'heroImage', 'news'));
    }
}
