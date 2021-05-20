<?php

namespace Nickpoulos\SvelteDirect;

use PHPHtmlParser\Dom;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;

class SvelteDirect
{
    public ?array $manifest;
    public array $tagsBeingUsed;

    public function buildManifestFile()
    {
        $defaultManifestPath = app()->bootstrapPath('cache/svelte-direct-components.php');

        $files = new Filesystem();

        $svelteFiles = array_filter(
            $files->allFiles(resource_path('js')),
            static fn ($file) => $file->getExtension() === 'svelte'
        );

        $tagNames = collect($svelteFiles)
            ->keyBy(fn($file) => $file->getRealPath())
            ->map(function(\SplFileInfo $file) use ($files) {
                return $files->get($file->getRealPath());
            })
            ->filter(function(string $code) {
                $pattern = '/<\s*svelte:options\s.+>/';
                return preg_match($pattern, $code, $matches);
            })
            ->map(function(string $code) use ($files) {
                $pattern = '/<\s*svelte:options\s.+>/';
                preg_match_all($pattern, $code, $matches);
                return (new Dom)->loadStr($matches[0][0])->find('svelte:options')[0]?->tag;
            })
            ->flip();

        $files->put($defaultManifestPath, var_export($tagNames));

    }

    public function defaultManifestPath() : string
    {
       return app()->bootstrapPath('cache/svelte-direct-components.php');
    }

    public function loadManifestFile(?string $manifestFilePath = null) : void
    {
        $files = new Filesystem();
        $manifestPath = $manifestFilePath ?? $this->defaultManifestPath();
        $this->manifest = !file_exists($manifestPath) ? $files->getRequire($manifestPath):null;
    }

    public function loadBladePreCompiler() : void
    {
        if (!$this->manifest) {
            return;
        }

        $this->app['blade.compiler']->precompiler([$this, 'findTagsInBladeTemplate']);
    }

    protected function findTagsInBladeTemplate(string $view) : string
    {
        $tagPattern = implode('|', array_keys($this->manifest));
        $pattern = "/(?<=<)\s*{$tagPattern}/";
        preg_match_all($pattern, $view, $matches);
        $this->tagsBeingUsed = array_merge(array_unique($matches[0]), $this->tagsBeingUsed);
        return $view;
    }

    public function loadBladeDirective() : void
    {
        if (!$this->manifest) {
            return;
        }

        Blade::directive('sveltedirect', [$this, 'generateDirectiveHtml']);
    }

    protected function generateDirectiveHtml(string $expression) : string
    {
        $tagsToLoad = array_intersect(array_keys($this->manifest), $this->tagsBeingUsed);
        return array_reduce($tagsToLoad, function($previous, $current) {
            return $previous . '<script src="{{ mix("' . $this->manifest[$current] . '") }}"></script>' . PHP_EOL;
        }, '');
    }
}
