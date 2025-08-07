<?php

namespace Platform\Comms\Registry;

class ChannelRegistry
{
    /**
     * @var array<string, array> Alle registrierten Channel-Instanzen (z. B. email:1)
     */
    protected static array $channels = [];

    /**
     * @var array<class-string> Liste registrierter Registrar-Klassen
     */
    protected static array $registrars = [];

    /**
     * Registriert eine Channel-Instanz.
     */
    public static function register(array $channelConfig): void
    {
        if (empty($channelConfig['id'])) {
            throw new \InvalidArgumentException('Channel ID is required.');
        }

        static::$channels[$channelConfig['id']] = $channelConfig;
    }

    /**
     * Holt die Config einer bestimmten Channel-Instanz.
     */
    public static function get(string $id): ?array
    {
        return static::$channels[$id] ?? null;
    }

    /**
     * Gibt alle registrierten Channel-Instanzen zurück.
     */
    public static function all(): array
    {
        return static::$channels;
    }

    /**
     * Filtert alle Channels nach Typ (z. B. "email", "sms", "push").
     */
    public static function allOfType(string $type): array
    {
        return array_filter(static::$channels, fn($c) => $c['type'] === $type);
    }

    /**
     * Fügt einen ChannelRegistrar hinzu.
     */
    public static function addRegistrar(string $registrarClass): void
    {
        if (!in_array($registrarClass, static::$registrars, true)) {
            static::$registrars[] = $registrarClass;
        }
    }

    /**
     * Führt alle bekannten Registrar-Klassen aus.
     */
    public static function runRegistrars(): void
    {
        foreach (static::$registrars as $registrarClass) {
            if (method_exists($registrarClass, 'registerChannels')) {
                $registrarClass::registerChannels();
            }
        }
    }
}