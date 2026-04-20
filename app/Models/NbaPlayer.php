<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class NbaPlayer extends Model
{
    protected $fillable = [
        'external_id',
        'guid',
        'uid',
        'first_name',
        'last_name',
        'full_name',
        'display_weight',
        'display_height',
        'age',
        'salary',
        'image',
        'team_id',
        'team_name',
        'team_logo',
    ];


public function gamelogs()
{
    return $this->hasMany(NbaPlayerGameLog::class, 'player_external_id', 'external_id');
}

public function getRouteKeyName()
{
    return 'external_id'; 
}
public function team()
{
    return $this->belongsTo(NbaTeam::class, 'team_id', 'external_id');
}
public function details()
{
    return $this->hasOne(NbaPlayerDetail::class, 'external_id', 'external_id');
}
}

