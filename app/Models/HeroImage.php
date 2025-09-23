<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroImage extends Model
{
    protected $fillable = ['title', 'image_path', 'location', 'league_id'];

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function getLocationAttribute(): string
    {
        if (is_null($this->league_id)) {
            return 'home';
        }

        return $this->league->parent_id ? 'subleague' : 'league';
    }

    public function getDisplayOnAttribute(): string
    {
        if (is_null($this->league_id)) {
            return '🏠 Homepage';
        }

        $name = $this->league->name;
        return $this->league->parent_id
            ? "↳ Sub-League: {$name}"
            : "🏀 League: {$name}";
    }
}


