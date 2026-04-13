<?php

namespace App\Console\Commands;

use App\Services\NbaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugPlayerGamelog extends Command
{
    protected $signature = 'nba:debug-player-gamelog
                            {playerId : External API player ID}
                            {--date= : Filter by game date YYYY-MM-DD}
                            {--event= : Filter by event ID}
                            {--log-full : Also write the full gamelog response to laravel.log}';

    protected $description = 'Debug raw player gamelog payload and inspect team/opponent data';

    public function handle(NbaService $nbaService): int
    {
        $playerId = (int) $this->argument('playerId');
        $targetDate = $this->option('date');
        $targetEvent = $this->option('event');
        $logFull = (bool) $this->option('log-full');

        $this->info("Fetching gamelog for player ID: {$playerId}");

        Log::info('NBA debug player gamelog started', [
            'player_id' => $playerId,
            'date_filter' => $targetDate,
            'event_filter' => $targetEvent,
            'log_full' => $logFull,
        ]);

        $gamelog = $nbaService->playerGameLog($playerId);

        if (empty($gamelog)) {
            $this->error('Empty gamelog response.');

            Log::warning('NBA debug player gamelog empty response', [
                'player_id' => $playerId,
                'date_filter' => $targetDate,
                'event_filter' => $targetEvent,
            ]);

            return self::FAILURE;
        }

        $events = $gamelog['events'] ?? [];
        $seasonTypes = $gamelog['seasonTypes'] ?? [];
        $labels = $gamelog['labels'] ?? [];

        Log::info('NBA debug player gamelog summary', [
            'player_id' => $playerId,
            'events_count' => count($events),
            'season_types_count' => count($seasonTypes),
            'labels' => $labels,
            'event_ids' => array_keys($events),
        ]);

        if ($logFull) {
            Log::info('NBA debug player gamelog full response', [
                'player_id' => $playerId,
                'response' => $gamelog,
            ]);
        }

        $this->line('----------------------------------------');
        $this->info('Top-level summary');
        $this->line('Events count: ' . count($events));
        $this->line('SeasonTypes count: ' . count($seasonTypes));
        $this->line('Labels: ' . implode(', ', $labels));
        $this->line('----------------------------------------');

        if (empty($events)) {
            $this->warn('No events returned.');
            $this->warn('Check storage/logs/laravel.log for details.');

            Log::warning('NBA debug player gamelog has no events', [
                'player_id' => $playerId,
                'response' => $gamelog,
            ]);

            return self::SUCCESS;
        }

        $found = false;

        foreach ($seasonTypes as $season) {
            $seasonName = $season['displayName'] ?? 'unknown season';

            foreach ($season['categories'] ?? [] as $category) {
                if (($category['type'] ?? null) !== 'event' || empty($category['events'])) {
                    continue;
                }

                foreach ($category['events'] as $event) {
                    $eventId = $event['eventId'] ?? null;

                    if (!$eventId) {
                        continue;
                    }

                    $meta = $events[$eventId] ?? [];
                    $gameDate = isset($meta['gameDate'])
                        ? Carbon::parse($meta['gameDate'])->toDateString()
                        : null;

                    if ($targetDate && $gameDate !== $targetDate) {
                        continue;
                    }

                    if ($targetEvent && (string) $eventId !== (string) $targetEvent) {
                        continue;
                    }

                    $found = true;

                    $summary = [
                        'player_id' => $playerId,
                        'season' => $seasonName,
                        'event_id' => $eventId,
                        'game_date' => $gameDate,
                        'team_id' => $meta['team']['id'] ?? null,
                        'team_abbreviation' => $meta['team']['abbreviation'] ?? null,
                        'team_logo' => $meta['team']['logo'] ?? null,
                        'opponent_id' => $meta['opponent']['id'] ?? null,
                        'opponent_name' => $meta['opponent']['displayName'] ?? null,
                        'opponent_abbreviation' => $meta['opponent']['abbreviation'] ?? null,
                        'opponent_logo' => $meta['opponent']['logo'] ?? null,
                        'at_vs' => $meta['atVs'] ?? null,
                        'home_team_id' => $meta['homeTeamId'] ?? null,
                        'away_team_id' => $meta['awayTeamId'] ?? null,
                        'score' => $meta['score'] ?? null,
                        'game_result' => $meta['gameResult'] ?? null,
                        'event_payload' => $event,
                        'meta_payload' => $meta,
                    ];

                    Log::info('NBA debug player gamelog matched event', $summary);

                    $this->newLine();
                    $this->warn('========================================');
                    $this->warn("Season: {$seasonName}");
                    $this->warn("Event ID: {$eventId}");
                    $this->warn("Game date: " . ($gameDate ?? 'null'));
                    $this->warn('========================================');

                    $this->info('Quick summary:');
                    $this->line('team.id: ' . ($meta['team']['id'] ?? 'null'));
                    $this->line('team.abbreviation: ' . ($meta['team']['abbreviation'] ?? 'null'));
                    $this->line('team.logo: ' . ($meta['team']['logo'] ?? 'null'));
                    $this->line('opponent.id: ' . ($meta['opponent']['id'] ?? 'null'));
                    $this->line('opponent.displayName: ' . ($meta['opponent']['displayName'] ?? 'null'));
                    $this->line('opponent.logo: ' . ($meta['opponent']['logo'] ?? 'null'));
                    $this->line('atVs: ' . ($meta['atVs'] ?? 'null'));
                    $this->line('homeTeamId: ' . ($meta['homeTeamId'] ?? 'null'));
                    $this->line('awayTeamId: ' . ($meta['awayTeamId'] ?? 'null'));
                    $this->line('score: ' . ($meta['score'] ?? 'null'));
                    $this->line('gameResult: ' . ($meta['gameResult'] ?? 'null'));

                    $this->line('Logged matched event to storage/logs/laravel.log');
                }
            }
        }

        if (! $found) {
            $availableEvents = [];

            foreach ($events as $eventId => $meta) {
                $gameDate = isset($meta['gameDate'])
                    ? Carbon::parse($meta['gameDate'])->toDateString()
                    : null;

                $availableEvents[] = [
                    'event_id' => $eventId,
                    'game_date' => $gameDate,
                    'team_id' => $meta['team']['id'] ?? null,
                    'team_abbreviation' => $meta['team']['abbreviation'] ?? null,
                    'opponent_id' => $meta['opponent']['id'] ?? null,
                    'opponent_abbreviation' => $meta['opponent']['abbreviation'] ?? null,
                ];
            }

            Log::warning('NBA debug player gamelog no matching event found', [
                'player_id' => $playerId,
                'date_filter' => $targetDate,
                'event_filter' => $targetEvent,
                'available_events' => $availableEvents,
            ]);

            $this->warn('No matching event found for the provided filters.');

            if ($targetDate) {
                $this->line("Tried date filter: {$targetDate}");
            }

            if ($targetEvent) {
                $this->line("Tried event filter: {$targetEvent}");
            }

            $this->newLine();
            $this->info('Available event IDs and dates:');

            foreach ($availableEvents as $row) {
                $this->line(
                    ($row['event_id'] ?? 'null')
                    . ' | '
                    . ($row['game_date'] ?? 'null')
                    . ' | '
                    . ($row['team_abbreviation'] ?? '?')
                    . ' vs '
                    . ($row['opponent_abbreviation'] ?? '?')
                );
            }

            $this->line('Logged available events to storage/logs/laravel.log');
        }

        return self::SUCCESS;
    }
}