<?php

namespace Platform\Comms\Registry;

use Platform\Comms\Contracts\ChannelProviderInterface;

/**
 * Verwaltung aller Channel-Provider (Email, WhatsApp, ...).
 * Provider werden per Typ registriert und bei Bedarf über den Container resolved.
 */
class ChannelProviderRegistry
{
    /**
     * @var array<string, class-string<ChannelProviderInterface>>
     */
    protected static array $providers = [];

    /**
     * Registriert einen Provider für einen Channel-Typ.
     */
    public static function addProvider(string $type, string $providerClass): void
    {
        static::$providers[$type] = $providerClass;
    }

    /**
     * Prüft, ob ein Provider für den Typ existiert.
     */
    public static function has(string $type): bool
    {
        return isset(static::$providers[$type]);
    }

    /**
     * Liefert alle registrierten Provider-Typen.
     */
    public static function types(): array
    {
        return array_keys(static::$providers);
    }

    /**
     * Legt einen Channel über den passenden Provider an und gibt die Channel-ID zurück.
     *
     * @throws \InvalidArgumentException wenn kein Provider für den Typ registriert ist.
     */
    public static function create(string $type, array $data): string
    {
        if (!isset(static::$providers[$type])) {
            throw new \InvalidArgumentException("No provider registered for type '{$type}'.");
        }

        /** @var ChannelProviderInterface $provider */
        $provider = app(static::$providers[$type]);

        return $provider->createChannel($data);
    }

    /**
     * Löscht einen Channel über den passenden Provider.
     */
    public static function delete(string $type, string $channelId): void
    {
        if (!isset(static::$providers[$type])) {
            throw new \InvalidArgumentException("No provider registered for type '{$type}'.");
        }

        /** @var ChannelProviderInterface $provider */
        $provider = app(static::$providers[$type]);

        $provider->deleteChannel($channelId);
    }
}

