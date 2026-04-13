<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPlayerGameLog extends Model
{
    use HasFactory;

protected $fillable = [
    'player_external_id',
    'event_id',

    'game_date',
    'team_external_id',
    'team_abbreviation',
    'team_logo',

    'opponent_name',
    'opponent_external_id',
    'opponent_logo',

    'is_home',
    'result',
    'score',

    'minutes',
    'fg',
    'fg_pct',
    'three_pt',
    'three_pt_pct',
    'ft',
    'ft_pct',
    'rebounds',
    'assists',
    'steals',
    'blocks',
    'turnovers',
    'fouls',
    'points',
];

    public function player()
    {
        return $this->belongsTo(NbaPlayer::class, 'player_external_id', 'external_id');
    }
    
}
