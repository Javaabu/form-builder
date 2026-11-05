<?php

namespace Javaabu\FormBuilder\Tests\Unit;

use Javaabu\FormBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BoostTest extends TestCase
{
    #[Test]
    public function it_ships_the_boost_guideline_and_skill(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/boost';

        $this->assertFileExists("{$root}/guidelines/core.blade.php");

        $skill = file_get_contents("{$root}/skills/javaabu-form-builder/SKILL.md");

        $this->assertMatchesRegularExpression('/^---\nname: javaabu-form-builder\ndescription: .+\n/', $skill);

        preg_match_all('/\]\((references\/[^)]+)\)/', $skill, $matches);

        foreach ($matches[1] as $reference) {
            $this->assertFileExists("{$root}/skills/javaabu-form-builder/{$reference}");
        }

        $this->assertNotEmpty($matches[1]);
    }

    #[Test]
    public function it_compiles_the_guideline(): void
    {
        $html = view()->file(dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php')->render();

        $this->assertStringContainsString('FormBuilder::fill(\'key\')', $html);
        $this->assertStringContainsString('javaabu-form-builder', $html);
    }
}
