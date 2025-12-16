<?php

namespace Platform\Comms\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\Comms\Models\CommsActivity;

class CommsActivityService
{
    public static function enabled(): bool
    {
        return Schema::hasTable('comms_activities') && Schema::hasTable('comms_activity_reads');
    }

    public static function recordInbound(
        string $channelId,
        string $contextType,
        int $contextId,
        ?int $teamId = null,
        ?string $threadRef = null,
        ?string $summary = null,
        array $payload = [],
        $occurredAt = null,
    ): void {
        if (!static::enabled()) {
            return;
        }

        CommsActivity::create([
            'team_id' => $teamId,
            'channel_id' => $channelId,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'thread_ref' => $threadRef,
            'direction' => 'inbound',
            'occurred_at' => $occurredAt ?? now(),
            'summary' => $summary,
            'payload' => $payload ?: null,
        ]);
    }

    public static function markSeen(
        int $userId,
        string $channelId,
        string $contextType,
        int $contextId,
        ?int $teamId = null,
        $seenAt = null,
    ): void {
        if (!static::enabled()) {
            return;
        }

        DB::table('comms_activity_reads')->updateOrInsert(
            [
                'user_id' => $userId,
                'channel_id' => $channelId,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ],
            [
                'team_id' => $teamId,
                'last_seen_at' => $seenAt ?? now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public static function unreadCountForContext(
        int $userId,
        string $channelId,
        string $contextType,
        int $contextId,
        ?int $teamId = null,
    ): int {
        if (!static::enabled()) {
            return 0;
        }

        $lastSeen = DB::table('comms_activity_reads')
            ->where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->value('last_seen_at');

        $q = DB::table('comms_activities')
            ->where('channel_id', $channelId)
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->where('direction', 'inbound');

        if ($teamId) {
            $q->where('team_id', $teamId);
        }

        if ($lastSeen) {
            $q->where('occurred_at', '>', $lastSeen);
        }

        return (int) $q->count();
    }

    /**
     * Liefert Inbox-Items: Kontexte mit ungelesenen inbound Activities (über alle Channels).
     * Returns: array<array{context_type:string,context_id:int,unread_count:int,last_occurred_at:string|null}>
     */
    public static function unreadContexts(int $userId, ?int $teamId = null, int $limit = 50): array
    {
        if (!static::enabled()) {
            return [];
        }

        // Subselect: last_seen pro user+channel+context
        $reads = DB::table('comms_activity_reads')
            ->select('channel_id', 'context_type', 'context_id', 'last_seen_at')
            ->where('user_id', $userId);

        $q = DB::table('comms_activities as a')
            ->leftJoinSub($reads, 'r', function ($join) {
                $join->on('a.channel_id', '=', 'r.channel_id')
                    ->on('a.context_type', '=', 'r.context_type')
                    ->on('a.context_id', '=', 'r.context_id');
            })
            ->where('a.direction', 'inbound')
            ->where(function ($w) {
                $w->whereNull('r.last_seen_at')
                  ->orWhereColumn('a.occurred_at', '>', 'r.last_seen_at');
            });

        if ($teamId) {
            $q->where('a.team_id', $teamId);
        }

        return $q->groupBy('a.context_type', 'a.context_id')
            ->selectRaw('a.context_type, a.context_id, COUNT(*) as unread_count, MAX(a.occurred_at) as last_occurred_at')
            ->orderByDesc('last_occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'context_type' => (string) $r->context_type,
                'context_id' => (int) $r->context_id,
                'unread_count' => (int) $r->unread_count,
                'last_occurred_at' => $r->last_occurred_at ? (string) $r->last_occurred_at : null,
            ])
            ->all();
    }

    /**
     * Letzte inbound Activity für einen Kontext (für Inbox-Preview).
     * @return array{channel_id:string,summary:?string,occurred_at:?string,payload:?array}|null
     */
    public static function lastInboundForContext(string $contextType, int $contextId, ?int $teamId = null): ?array
    {
        if (!static::enabled()) {
            return null;
        }

        $q = DB::table('comms_activities')
            ->where('direction', 'inbound')
            ->where('context_type', $contextType)
            ->where('context_id', $contextId);

        if ($teamId) {
            $q->where('team_id', $teamId);
        }

        $r = $q->orderByDesc('occurred_at')->first();
        if (!$r) return null;

        return [
            'channel_id' => (string) $r->channel_id,
            'summary' => $r->summary ? (string) $r->summary : null,
            'occurred_at' => $r->occurred_at ? (string) $r->occurred_at : null,
            'payload' => $r->payload ? (array) json_decode($r->payload, true) : null,
        ];
    }
}


