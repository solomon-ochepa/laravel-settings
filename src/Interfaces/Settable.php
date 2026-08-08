<?php

declare(strict_types=1);

namespace SolomonOchepa\Settings\Interfaces;

/**
 * Contract for non-Eloquent entities that settings can be scoped to via
 * for(). Eloquent models don't need to implement this — they're already
 * supported natively through Model::getKey().
 */
interface Settable
{
    /**
     * The identifier stored in the settable_id morph column.
     */
    public function getSettableKey(): int|string;
}
