<?php

namespace Javaabu\FormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Javaabu\FormBuilder\Models\FormEntry;

/**
 * An entry was submitted (or saved as a draft)
 */
class EntrySubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly FormEntry $entry,
    ) {}
}
