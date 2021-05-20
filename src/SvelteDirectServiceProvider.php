<?php

namespace Nickpoulos\SvelteDirect;

use Illuminate\Support\ServiceProvider;

class SvelteDirectServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $svelteDirect = new SvelteDirect();
        $svelteDirect->loadManifestFile();
        $svelteDirect->loadBladePreCompiler();
        $svelteDirect->loadBladeDirective();
    }
}
