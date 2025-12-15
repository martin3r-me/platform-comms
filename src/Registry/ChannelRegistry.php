<?php

namespace Platform\Comms\Registry;

class ChannelRegistry
{
    /**
     * @var bool Merker, ob Registrare in diesem Request bereits ausgeführt wurden.
     */
    protected static bool $registrarsExecuted = false;

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
        $required = ['id', 'type', 'label', 'component'];

        foreach ($required as $key) {
            if (empty($channelConfig[$key]) || !is_string($channelConfig[$key])) {
                throw new \InvalidArgumentException("Channel field '{$key}' is required and must be a non-empty string.");
            }
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
    public static function runRegistrars(bool $force = false): void
    {
        // Nur einmal pro Request/Job ausführen, außer force=true
        if (static::$registrarsExecuted && !$force) {
            return;
        }

        static::$registrarsExecuted = true;

        foreach (static::$registrars as $registrarClass) {
            if (method_exists($registrarClass, 'registerChannels')) {
                $registrarClass::registerChannels();
            }
        }
    }
}