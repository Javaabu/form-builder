<?php

namespace Javaabu\FormBuilder\Tests\Unit;

use Javaabu\FormBuilder\Facades\FormBuilder;
use Javaabu\FormBuilder\FormBuilderManager;
use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_manager_and_config(): void
    {
        $this->assertInstanceOf(FormBuilderManager::class, FormBuilder::getFacadeRoot());
        $this->assertSame(app(FormBuilderManager::class), app('form-builder'));
        $this->assertIsArray(config('form-builder'));
    }
}
