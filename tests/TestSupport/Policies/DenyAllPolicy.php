<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\Policies;

class DenyAllPolicy
{
    public function before(): bool
    {
        return false;
    }
}
