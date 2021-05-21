<?php

namespace Nickpoulos\SvelteDirect\Tests;

use Illuminate\Filesystem\Filesystem;
use Nickpoulos\SvelteDirect\SvelteDirect;

class SvelteDirectTest extends TestCase
{
    public SvelteDirect $svelteDirect;

    public function setUp() : void
    {
        parent::setUp();

        $this->svelteDirect = new SvelteDirect();
    }

    public function testLoadManifestFile()
    {
        $testManifest = ['test-tag' => '/js/TestTag.js'];
        $testManifestString = "<?php return ['test-tag' => '/js/TestTag.js']; ?>";
        $testManifestPath ='./manifest.php';

        $files = new Filesystem();

        $files->put($testManifestPath, $testManifestString);
        $this->svelteDirect->loadManifestFile($testManifestPath);
        $files->delete($testManifestPath);
        self::assertEquals($this->svelteDirect->manifest, $testManifest);
    }
}
