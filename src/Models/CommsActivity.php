<?php

namespace Platform\Comms\Models;

use Illuminate\Database\Eloquent\Model;

class CommsActivity extends Model
{
    protected $table = 'comms_activities';

    protected $fillable = [
        'team_id',
        'channel_id',
        'context_type',
        'context_id',
        'thread_ref',
        'direction',
        'occurred_at',
        'summary',
        'payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];
}


