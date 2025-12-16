<?php

namespace Platform\Comms\Registry;

use Platform\Comms\Contracts\ContextPresenterInterface;

class ContextPresenterRegistry
{
    /** @var array<class-string<ContextPresenterInterface>> */
    protected static array $presenters = [];

    public static function add(string $presenterClass): void
    {
        if (!in_array($presenterClass, static::$presenters, true)) {
            static::$presenters[] = $presenterClass;
        }
    }

    /**
     * @return array{title:string,subtitle?:string,url?:string}|null
     */
    public static function present(string $contextType, int $contextId): ?array
    {
        foreach (static::$presenters as $cls) {
            if (!class_exists($cls)) {
                continue;
            }
            $presenter = app($cls);
            if (!$presenter instanceof ContextPresenterInterface) {
                continue;
            }
            $res = $presenter->present($contextType, $contextId);
            if ($res) {
                return $res;
            }
        }

        return null;
    }
}


