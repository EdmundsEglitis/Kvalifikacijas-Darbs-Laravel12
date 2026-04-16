<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RunCronWidget extends Widget
{
    protected static ?string $heading = 'Database Sync';
    protected static string $view = 'filament.widgets.run-cron-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }
}
