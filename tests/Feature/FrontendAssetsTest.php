<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Javaabu\FormBuilder\Assets\Assets;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\Manifest\Manifest;
use PHPUnit\Framework\Attributes\Test;

class FrontendAssetsTest extends TestCase
{
    #[Test]
    public function it_registers_the_assets_with_manifest(): void
    {
        $manifest = $this->app->make(Manifest::class);

        $this->assertTrue($manifest->hasPackage(Assets::PACKAGE));
        $this->assertTrue($manifest->has(Assets::STYLES));
        $this->assertTrue($manifest->has(Assets::BUILDER));
        $this->assertTrue($manifest->has(Assets::REPEATER));

        $package = $manifest->package(Assets::PACKAGE);

        $this->assertEquals('@javaabu/form-builder', $package->importAlias());
        $this->assertEquals('js/Pages', $package->pagesPath());
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());
        $this->assertFileExists($manifest->entryPath($manifest->find(Assets::BUILDER)));
        $this->assertFileExists(dirname(__DIR__, 2) . '/dist/.vite/manifest.json');
    }

    #[Test]
    public function it_ships_an_inertia_page_for_every_blade_page(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (glob("{$root}/resources/views/{forms,sections,fields,entries,fill}/*.blade.php", GLOB_BRACE) as $view) {
            $page = str(basename(dirname($view)))->studly() . '/' . str(basename($view, '.blade.php'))->studly();

            if ($page === 'Entries/Print') {
                continue;
            }

            $this->assertFileExists("{$root}/resources/js/Pages/{$page}.tsx");
        }
    }
}
