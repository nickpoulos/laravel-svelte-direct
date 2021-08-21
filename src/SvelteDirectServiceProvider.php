<?php

namespace Nickpoulos\SvelteDirect;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class SvelteDirectServiceProvider extends ServiceProvider
{
    /**
     * Our internal "tag-to-js file" mapping
     *
     * @var array
     */
    public array $manifest = [];

    /**
     * Keep track of already loaded tags to prevent duplicate imports
     *
     * @var array
     */
    public array $loadedTags = [];

    /**
     * Main class entrypoint
     */
    public function boot(): void
    {
        $this->loadManifestFile();
        $this->app['blade.compiler']->precompiler([$this, 'precompiler']);
    }

    /**
     * Provide the default path to the SvelteDirect manifest file
     *
     * @todo allow control via proper config file
     *
     * @internal
     * @return string
     */
    public function defaultManifestPath() : string
    {
        return App::bootstrapPath('cache/svelte-direct-components.php');
    }

    /**
     * Loads the "tag name to JavaScript file" mapping
     * aka manifest file
     *
     * @internal
     * @param string|null $manifestFilePath
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function loadManifestFile(?string $manifestFilePath = null) : void
    {
        $files = new Filesystem();
        $manifestPath = $manifestFilePath ?? $this->defaultManifestPath();
        $this->manifest = file_exists($manifestPath) ? $files->getRequire($manifestPath):[];
    }

    /**
     * Our precompiler function that finds any Svelte component tags
     * and then appends the proper call to the @stack Blade directive
     * to our existing Blade template code
     *
     * @param string $viewTemplateCode
     * @return string
     */
    public function precompiler(string $viewTemplateCode) : string
    {
        collect($this->manifest)->each(function(string $jsFile, string $tag) use (&$viewTemplateCode) {
            $check = $this->findPositionOfSvelteTagInBlade($viewTemplateCode, $tag);
            if (!$check || in_array($tag, $this->loadedTags, true)) {
                return; // skip
            }
            $pushDirective = $this->generatePushDirective([$tag]);
            $viewTemplateCode = substr_replace($viewTemplateCode, $pushDirective, $check - 1, 0);
            $this->loadedTags = array_merge($this->loadedTags, [$tag]);
        });

        return $viewTemplateCode;
    }


    /**
     * Given some Blade template code, and one of our Svelte component tags
     * Check if the tag exists in the code, if so, return the position of the first occurrence
     *
     * @param string $viewTemplateCode
     * @param string $tag
     * @return Collection
     * @internal
     */
    public function findPositionOfSvelteTagInBlade(string $viewTemplateCode, string $tag) : ?int
    {
        $pattern = "/(?<=<)\s*(?:{$tag})(?=\s|>|\/)+/";
        preg_match_all($pattern, $viewTemplateCode, $matches, PREG_OFFSET_CAPTURE);
        return collect($matches[0])->pluck(1)->first();
    }

    /**
     * Create the @push directive code for the given Svelte tags
     *
     * @internal
     * @param array $tagsToLoad
     * @return string
     */
    public function generatePushDirective(array $tagsToLoad) : string
    {
        return "@push('sveltedirect')" . PHP_EOL .
            $this->generateScriptHtml($tagsToLoad)
            . PHP_EOL . "@endpush";
    }


    /**
     * Generate the script tag HTML to load our component JavaScript file(s)
     * for a given set of tags
     *
     * @internal
     * @param array $tagsToLoad
     * @return string
     */
    public function generateScriptHtml(array $tagsToLoad) : string
    {
        return array_reduce($tagsToLoad, function ($previous, $current) {
            return $previous . '<script src="{{ mix("' . $this->manifest[$current] . '") }}"></script>' . PHP_EOL;
        }, '');
    }
}
