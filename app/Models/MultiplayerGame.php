<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultiplayerGame extends Model
{
    protected $dates = ['start_time', 'end_time'];

    protected $appends = ['start_time_unix', 'end_time_unix'];

    public function provablyFairGame()
    {
        return $this->belongsTo(ProvablyFairGame::class);
    }

    public function gameable()
    {
        return $this->morphTo();
    }

    /**
     * Getter for start_time_unix attribute
     *
     * @return int|null
     */
    public function getStartTimeUnixAttribute(): ?int
    {
        return $this->start_time ? $this->start_time->timestamp : NULL;
    }

    /**
     * Getter for end_time_unix attribute
     *
     * @return int|null
     */
    public function getEndTimeUnixAttribute(): ?int
    {
        return $this->end_time ? $this->end_time->timestamp : NULL;
    }
}
