<?php

namespace Javaabu\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Javaabu\FormBuilder\Models\FormEntry;

/**
 * An entry's answers were changed
 */
class EntryUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly FormEntry $entry,
    ) {}
}
