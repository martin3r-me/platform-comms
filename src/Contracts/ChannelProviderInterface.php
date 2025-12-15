<?php

namespace Platform\Comms\Contracts;

/**
 * Definiert die Schnittstelle für Channel-Provider (E-Mail, WhatsApp, Teams, ...),
 * die neue Channel-Instanzen anlegen können.
 */
interface ChannelProviderInterface
{
    /**
     * Eindeutiger Typ des Channels, z. B. "email", "whatsapp", "teams".
     */
    public function getType(): string;

    /**
     * Legt einen neuen Channel an und gibt die Channel-ID (z. B. "email:123") zurück.
     *
     * @param array $data Provider-spezifische Daten (z. B. address, team_id, user_id, name, meta).
     */
    public function createChannel(array $data): string;
}

