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
            cache()->put('cron_status', 'running');
            cache()->put('processed', 0);
            cache()->put('failed', 0);
            cache()->put('total', 0);

        cache()->put('cron_status', [
            'status' => 'running',
            'processed' => 0,
            'failed' => 0,
            'total' => 0,
        ], 600);

        dispatch(function () {
            app(ApiSyncService::class)->sync();
            cache()->put('cron_status', [
                'status' => 'finished',
                'processed' => cache()->get('processed', 0),
                'failed' => cache()->get('failed', 0),
                'total' => cache()->get('total', 0),
            ], 600);
        });

        return view('cron.loader');
    }
}