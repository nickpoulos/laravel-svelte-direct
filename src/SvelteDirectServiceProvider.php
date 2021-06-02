<?php

namespace Nickpoulos\SvelteDirect;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SvelteDirectServiceProvider extends ServiceProvider
{
    public ?array $manifest = [];
    public array $tagsBeingUsed = [];

    public function boot(): void
    {
        $this->loadManifestFile();
        $this->loadBladePreCompiler();
    }

    public function defaultManifestPath() : string
    {
        return App::bootstrapPath('cache/svelte-direct-components.php');
    }

    public function loadManifestFile(?string $manifestFilePath = null) : void
    {
        $files = new Filesystem();
        $manifestPath = $manifestFilePath ?? $this->defaultManifestPath();
        $this->manifest = file_exists($manifestPath) ? $files->getRequire($manifestPath):null;
    }

    public function loadBladePreCompiler() : void
    {
        if (! $this->manifest) {
            return;
        }

        $this->app['blade.compiler']->precompiler([$this, 'findTagsInBladeTemplate']);
    }

    /** @internal */
    public function findTagsInBladeTemplate(string $view) : string
    {
        $tagPattern = implode('|', array_keys($this->manifest));
        $pattern = "/(?<=<)\s*{$tagPattern}/";
        preg_match_all($pattern, $view, $matches);
        $this->tagsBeingUsed = array_merge(array_unique($matches[0]), $this->tagsBeingUsed);

        return $view;
    }

    /** @internal */
    public function generateDirectiveHtml(string $expression) : string
    {
        $tagsToLoad = array_intersect(array_keys($this->manifest), $this->tagsBeingUsed);

        return array_reduce($tagsToLoad, function ($previous, $current) {
            return $previous . '<script src="{{ mix("' . $this->manifest[$current] . '") }}"></script>' . PHP_EOL;
        }, '');
    }
}
