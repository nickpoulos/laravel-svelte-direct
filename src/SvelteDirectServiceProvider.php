<?php

namespace Nickpoulos\SvelteDirect;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class SvelteDirectServiceProvider extends ServiceProvider
{
    /**
     * @var array|null
     */
    public ?array $manifest = [];

    /**
     * Main class entrypoint
     */
    public function boot(): void
    {
        $this->loadManifestFile();
        $this->loadBladePreCompiler();
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
        $this->manifest = file_exists($manifestPath) ? $files->getRequire($manifestPath):null;
    }

    /**
     * Register our precompiler function within the Blade compiler engine
     *
     * @internal
     */
    public function loadBladePreCompiler() : void
    {
        if (! $this->manifest) {
            return;
        }

        $this->app['blade.compiler']->precompiler([$this, 'precompiler']);
    }


    /**
     * Our precompiler function that finds any Svelte component tags
     * and then appends the proper call to the @stack Blade directive
     * to our existing Blade template code
     *
     * @param string $viewTemplateCode
     * @return string
     */
    public function precompiler(string $viewTemplateCode)
    {
        $tagsToLoad = $this->findSvelteComponentTagsInBlade($viewTemplateCode);

        return $viewTemplateCode . $this->appendPushDirective($tagsToLoad);
    }


    /**
     * Given Blade template code, find any of our Svelte component tags
     * that were used within the template
     *
     * @internal
     * @param string $view
     * @return array
     */
    public function findSvelteComponentTagsInBlade(string $view) : array
    {
        $tagPattern = implode('|', array_keys($this->manifest));
        $pattern = "/(?<=<)\s*(?:{$tagPattern})(?=\s|>|\/)+/";
        preg_match_all($pattern, $view, $matches);

        return array_intersect(array_keys($this->manifest), array_unique($matches[0]));
    }

    /**
     * Create the @push directive code for the given Svelte tags
     *
     * @internal
     * @param array $tagsToLoad
     * @return string
     */
    public function appendPushDirective(array $tagsToLoad) : string
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
