<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nba_player_game_logs', function (Blueprint $table) {
            $table->dropForeign(['player_external_id']);

            $table->bigInteger('team_external_id')->nullable()->after('game_date');
            $table->string('team_abbreviation')->nullable()->after('team_external_id');
            $table->string('team_logo')->nullable()->after('team_abbreviation');

            $table->bigInteger('opponent_external_id')->nullable()->after('opponent_name');

            $table->boolean('is_home')->nullable()->after('opponent_logo');

            $table->index(['team_external_id', 'game_date'], 'npgl_team_date_idx');
            $table->index(['event_id', 'team_external_id'], 'npgl_event_team_idx');
        });
    }

    public function down(): void
    {
        Schema::table('nba_player_game_logs', function (Blueprint $table) {
            $table->dropIndex('npgl_team_date_idx');
            $table->dropIndex('npgl_event_team_idx');

            $table->dropColumn([
                'team_external_id',
                'team_abbreviation',
                'team_logo',
                'opponent_external_id',
                'is_home',
            ]);
        });
    }
};