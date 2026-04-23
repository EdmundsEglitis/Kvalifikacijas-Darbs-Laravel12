<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE nba_games MODIFY home_team_id BIGINT NULL');
        DB::statement('ALTER TABLE nba_games MODIFY away_team_id BIGINT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE nba_games MODIFY home_team_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE nba_games MODIFY away_team_id BIGINT UNSIGNED NULL');
    }
};