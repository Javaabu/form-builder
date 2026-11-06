<?php

namespace Javaabu\FormBuilder\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Javaabu\FormBuilder\Assets\Assets;
use Javaabu\FormBuilder\Tests\TestCase;
use Javaabu\Manifest\Enums\NpmScopes;
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
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertArrayHasKey('react', $package->npmPeerDependencies(NpmScopes::PAGES));
        $this->assertSame([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertFileExists($manifest->entryPath($manifest->find(Assets::BUILDER)));
        $this->assertFileExists(dirname(__DIR__, 2) . '/dist/.vite/manifest.json');
    }

    #[Test]
    public function blade_only_apps_arent_asked_for_react_packages(): void
    {
        $neededBy = function (array $options): array {
            Artisan::call('manifest:check', ['--json' => true, ...$options]);

            $rows = json_decode(Artisan::output(), true) ?? [];

            return array_column(array_filter($rows, fn (array $row) => in_array(Assets::PACKAGE, $row['needed_by'], true)), 'name');
        };

        $react = ['@javaabu/forms', '@javaabu/onu', 'react', 'react-dom', '@inertiajs/react'];

        // The testbench app has no Inertia adapter in its package.json: assets only.
        // Building the Blade scripts needs Vite, but nothing React.
        $this->assertSame([], array_intersect($react, $neededBy([])));
        $this->assertSame([], array_intersect($react, $neededBy(['--scope' => ['assets']])));
        $this->assertContains('vite', $neededBy(['--scope' => ['assets']]));

        $pages = $neededBy(['--scope' => ['pages']]);

        $this->assertContains('@javaabu/forms', $pages);
        $this->assertContains('react', $pages);
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
