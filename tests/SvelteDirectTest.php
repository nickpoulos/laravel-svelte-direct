<?php

namespace Nickpoulos\SvelteDirect\Tests;

use Illuminate\Filesystem\Filesystem;
use Nickpoulos\SvelteDirect\SvelteDirectServiceProvider;

class SvelteDirectTest extends TestCase
{
    /**
     * @var SvelteDirectServiceProvider
     */
    protected $svelteDirect;

    public function setUp() : void
    {
        parent::setUp();

        $this->svelteDirect = new SvelteDirectServiceProvider($this->app);
    }

    public function testLoadManifestFile()
    {
        $testManifest = ['test-tag' => '/js/TestTag.js'];
        $testManifestString = "<?php return ['test-tag' => '/js/TestTag.js']; ?>";
        $testManifestPath = './manifest.php';

        $files = new Filesystem();

        $files->put($testManifestPath, $testManifestString);
        $this->svelteDirect->loadManifestFile($testManifestPath);
        $files->delete($testManifestPath);
        self::assertEquals($this->svelteDirect->manifest, $testManifest);
    }
}
