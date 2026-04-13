<?php

namespace App\Http\Controllers\Lbs;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\News;

class NewsController extends Controller
{
    public function show($id)
    {
        $news = News::with('league')->findOrFail($id);
        $parentLeagues = League::whereNull('parent_id')->get();

        $cleanContent = preg_replace('/<figcaption.*?<\/figcaption>/is', '', $news->content);
        $news->clean_content = $cleanContent;

        return view('lbs.news.show', compact('news', 'parentLeagues'));
    }
}
