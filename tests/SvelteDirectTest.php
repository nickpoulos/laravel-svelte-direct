<?php

namespace Nickpoulos\SvelteDirect\Tests;

use Illuminate\Filesystem\Filesystem;
use Nickpoulos\SvelteDirect\SvelteDirectServiceProvider;

/**
 * Class SvelteDirectTest
 * @package Nickpoulos\SvelteDirect\Tests
 */
class SvelteDirectTest extends TestCase
{
    /**
     * @var SvelteDirectServiceProvider
     */
    protected SvelteDirectServiceProvider $svelteDirect;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * Define a test manifest path
     */
    public const TEST_MANIFEST_FILE_PATH = './manifest.php';

    /**
     * Define a test manifest of tags
     *
     * @var array|string[]
     */
    protected array $testManifest = [
        'test-tag' => '/js/TestTag.js',
        'nick-poulos' => '/js/NickPoulos.js'
    ];

    /**
     * Test setup
     */
    public function setUp() : void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->svelteDirect = new SvelteDirectServiceProvider($this->app);
        $this->createTestManifestFile($this->testManifest);
    }

    /**
     * Tear Down/Clean Up
     */
    public function tearDown() : void
    {
        $this->filesystem->delete(self::TEST_MANIFEST_FILE_PATH);
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testLoadManifestFile() : void
    {
        $this->svelteDirect->loadManifestFile(self::TEST_MANIFEST_FILE_PATH);
        self::assertEquals($this->svelteDirect->manifest, $this->testManifest);
    }


    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testGenerateScriptHtml() : void
    {
        $tagsToLoad = array_keys($this->testManifest);

        $expected = array_reduce($tagsToLoad, function ($previous, $current) {
            return $previous . '<script src="{{ mix("' . $this->testManifest[$current] . '") }}"></script>' . PHP_EOL;
        }, '');

        $this->svelteDirect->loadManifestFile(self::TEST_MANIFEST_FILE_PATH);

        $actual = $this->svelteDirect->generateScriptHtml($tagsToLoad);

        self::assertEquals($expected, $actual);
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testAppendPushDirective()
    {
        $tagsToLoad = array_keys($this->testManifest);

        $scriptHtml = array_reduce($tagsToLoad, function ($previous, $current) {
            return $previous . '<script src="{{ mix("' . $this->testManifest[$current] . '") }}"></script>' . PHP_EOL;
        }, '');

        $expected = "@push('sveltedirect')" . PHP_EOL . $scriptHtml . PHP_EOL . "@endpush";

        $this->svelteDirect->loadManifestFile(self::TEST_MANIFEST_FILE_PATH);

        $actual = $this->svelteDirect->appendPushDirective($tagsToLoad);

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider bladeTemplateCodeDataProvider
     */
    public function testFindSvelteComponentTagsInBlade(array $expectedResult, string $testBladeTemplateCode)
    {
        $this->svelteDirect->loadManifestFile(self::TEST_MANIFEST_FILE_PATH);
        $actual = $this->svelteDirect->findSvelteComponentTagsInBlade($testBladeTemplateCode);
        self::assertEquals($expectedResult, $actual);
    }

    /**
     * @return array[]
     */
    public function bladeTemplateCodeDataProvider() : array
    {
        $expectedResultWhenFound = array_keys($this->testManifest);
        $expectedResultWhenNotFound = [];

        $regularStyleTags = <<<BLADE1
            <html>
            <head>
                <title>Testing Regular Blade</title>
            </head>
            <body>
                <div>
                    @include('some fake directive stuff')
                </div>
                <test-tag>This is a regular tag test</test-tag>
                <nick-poulos has="attributes for this one">Nothing fancy here</nick-poulos>
            </body>
            </html>
        BLADE1;

        $singleClosingStyleTags = <<<BLADE2
            <html>
            <head>
                <title>Single Tag Closing Style</title>
            </head>
            <body>
                <div>
                    @include('some fake directive stuff')
                </div>
                <test-tag />
                <nick-poulos data-has="some fake attributes here" />
            </body>
            </html>
        BLADE2;

        $nestedRegularStyleTags = <<<BLADE3
            <html>
            <head>
                <title>Testing Nested/Slot Style Blade</title>
            </head>
            <body>
                <div>
                    @include('some fake directive stuff')
                </div>
                <test-tag>
                    <nick-poulos some="fake attributes" annoying="true"></nick-poulos>
                </test-tag>
            </body>
            </html>
        BLADE3;

        $nestedSingleClosingStyleTags = <<<BLADE4
            <html>
            <head>
                <title>Testing Nested/Slot Style Single/Closing Only Blade</title>
            </head>
            <body>
                    <div>
                        @include('some fake directive stuff')
                    </div>
                    <test-tag>
                      <nick-poulos fake="another attribute" />
                    </test-tag>
                </body>
                </html>
            BLADE4;

        $brokenTagsWithCloseName = <<<BLADE5
                <html>
                <head>
                    <title>Testing Broken Tag Name</title>
                </head>
                <body>
                    <div>
                        @include('some fake directive stuff')
                    </div>
                    <test-tagg>
                      <nick--poulos fake="another attribute" />
                    </test-tagg>
                </body>
                </html>
            BLADE5;

        return [
            "Regular Style Tags" => [
                $expectedResultWhenFound,
                $regularStyleTags
            ],
            "Single Closing Style Tags" => [
                $expectedResultWhenFound,
                $singleClosingStyleTags
            ],
            "Nested Regular Style Tags" => [
                $expectedResultWhenFound,
                $nestedRegularStyleTags
            ],
            "Nested Single Closing Style Tags" => [
                $expectedResultWhenFound,
                $nestedSingleClosingStyleTags
            ],
            "Broken Tag That Is Close" => [
                $expectedResultWhenNotFound,
                $brokenTagsWithCloseName
            ],
        ];
    }

    /**
     * @param array $testManifest
     * @return string
     */
    protected function buildTestManifestCode(array $testManifest) : string
    {
        $result = "<?php return [" . PHP_EOL;

        array_walk($testManifest, function(string $jsFile, string $tag) use(&$result) {
            $result .= "'" . $tag . "' => '" . $jsFile . "'," . PHP_EOL;
        });

        return $result . "]; ?>";
    }

    /**
     * @param array $testManifest
     */
    protected function createTestManifestFile(array $testManifest)
    {
        $this->filesystem->put(self::TEST_MANIFEST_FILE_PATH, $this->buildTestManifestCode($testManifest));
    }
}
