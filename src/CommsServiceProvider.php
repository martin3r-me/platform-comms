<?php

namespace Platform\Comms;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CommsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        

        // Konfigurationsdatei veröffentlichen
        $this->publishes([
            __DIR__ . '/../config/comms.php' => config_path('comms.php'),
        ], 'config');

        // Migrationen laden & publishen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Views laden & publishen
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'comms');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/comms'),
        ], 'views');

        // Alle Livewire-Komponenten (inkl. Unterordner) registrieren
        $this->registerLivewireComponents();
    }

    public function register(): void
    {
        // Config zusammenführen (Standardwerte + overrides)
        $this->mergeConfigFrom(
            __DIR__ . '/../config/comms.php',
            'comms'
        );
    }

    /**
     * Registriert alle Livewire-Komponenten im Package,
     * inklusive Unterordner, automatisch.
     */

    protected function registerLivewireComponents(): void
    {
        $componentPath = __DIR__ . '/Http/Livewire';
        $namespace = 'Platform\\Comms\\Http\\Livewire';
        $prefix = 'comms';

        if (!is_dir($componentPath)) {
            Log::warning("[Comms] Kein Livewire-Ordner gefunden: {$componentPath}");
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($componentPath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            // Relativen Pfad ermitteln und führenden Backslash entfernen
            $relativePath = ltrim(str_replace([$componentPath, '/', '.php'], ['', '\\', ''], $file->getPathname()), '\\');

            $class = $namespace . '\\' . $relativePath;

            // Alias korrekt generieren
            $aliasPath = str_replace('\\', '.', $relativePath);
            $segments = explode('.', $aliasPath);
            $segments = array_map(fn($s) => Str::kebab($s), $segments);
            $alias = $prefix . '.' . implode('.', $segments);

            Log::info('[Comms] Livewire-Check', [
                'file' => $file->getPathname(),
                'relativePath' => $relativePath,
                'class' => $class,
                'alias' => $alias,
                'exists' => class_exists($class),
            ]);

            if (class_exists($class)) {
                Livewire::component($alias, $class);
            } else {
                Log::warning("[Comms] Klasse nicht gefunden: {$class}");
            }
        }
    }
}