<?php

namespace Platform\Comms\Models;

use Illuminate\Database\Eloquent\Model;

class CommsActivityRead extends Model
{
    protected $table = 'comms_activity_reads';

    protected $fillable = [
        'team_id',
        'user_id',
        'channel_id',
        'context_type',
        'context_id',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}


