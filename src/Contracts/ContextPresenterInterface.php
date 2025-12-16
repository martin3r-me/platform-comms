<?php

namespace Platform\Comms\Contracts;

interface ContextPresenterInterface
{
    /**
     * @return array{title:string,subtitle?:string,url?:string}|null
     */
    public function present(string $contextType, int $contextId): ?array;
}


