<?php

namespace Nickpoulos\SvelteDirect\Support;

use Illuminate\Filesystem\Filesystem;
use PHPHtmlParser\Dom;

class SvelteDirectManifestBuilder
{
    public function build()
    {
        $serverless = false;

        $defaultManifestPath = $serverless
            ? '/tmp/storage/bootstrap/cache/svelte-direct-components.php'
            : app()->bootstrapPath('cache/svelte-direct-components.php');

        $files = new Filesystem();

        $svelteFiles = array_filter(
            $files->allFiles(resource_path('js')),
            static fn ($file) => $file->getExtension() === 'svelte'
        );

        $tagNames = collect($svelteFiles)
            ->keyBy(fn ($file) => $file->getRealPath())
            ->map(function (\SplFileInfo $file) use ($files) {
                return $files->get($file->getRealPath());
            })
            ->filter(function (string $code) {
                $pattern = '/<\s*svelte:options\s.+>/';

                return preg_match($pattern, $code, $matches);
            })
            ->map(function (string $code) use ($files) {
                $pattern = '/<\s*svelte:options\s.+>/';
                preg_match_all($pattern, $code, $matches);

                return (new Dom)->loadStr($matches[0][0])->find('svelte:options')[0]?->tag;
            })
            ->flip();
    }
}
