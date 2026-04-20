<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiSyncService;

class CronController extends Controller
{
    public function run(Request $request)
    {
        if ($request->token !== config('app.cron_token')) {
            abort(403, 'Unauthorized');
        }

        dispatch(function () {
            app(ApiSyncService::class)->sync();
        });

        return view('cron.loader');
    }
}