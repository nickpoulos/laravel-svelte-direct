let mix = require('laravel-mix');
const { resolve, basename } = require('path');
const { readdir, writeFile, readFile, unlink } = require('fs').promises;
const { findSvelteProps, findSvelteTagName } = require('./svelte-code-helper');

class SvelteDirect {

    /**
     * Constructor
     */
    constructor() {
        this.options = {
            componentMode: true,
            loaderOptions: {
                dev: !Mix.inProduction(),
                compilerOptions: {
                    customElement: false
                }
            }
        };

        this.manifest = [];
        this.tempJavascriptBootloaderFiles = [];
    }

    /**
     * Dependencies for Svelte webpack
     */
    dependencies() {
        this.requiresReload = true;
        return ["svelte", "svelte-loader"];
    }

    /**
     * Plugin entry point
     *
     * @param {string} inputPath
     * @param {string} outputPath
     * @param {object} options
     */
    register(inputPath, outputPath, options)
    {
        this.options = { ...this.options, ...options};

        this.options.loaderOptions.compilerOptions.customElement = !this.options.componentMode;

        this.handle(inputPath, outputPath);
    }

    /**
     * Webpack rules for building Svelte files
     */
    webpackRules() {
        return [
            {
                test: /\.(html|svelte)$/,
                use: [
                    { loader: 'babel-loader', options: Config.babel() },
                    { loader: 'svelte-loader', options: this.options.loaderOptions }
                ]
            },
            {
                test: /\.(mjs)$/,
                use: { loader: 'babel-loader', options: Config.babel() }
            }
        ];
    }

    /**
     * Prepare the provided path for processing.
     *
     * @param {object} webpackConfig
     */
    webpackConfig(webpackConfig) {
        webpackConfig.resolve.mainFields = [
            'svelte',
            'browser',
            'module',
            'main',
        ];
        webpackConfig.resolve.extensions = ['.mjs', '.js', '.svelte'];
        webpackConfig.resolve.alias = webpackConfig.resolve.alias || {};
        webpackConfig.resolve.alias.svelte = resolve(
            'node_modules',
            'svelte'
        );
    }

    /**
     * Import our dependencies
     */
    boot() {
        let svelte = require("svelte");
        let loader = require("svelte-loader");
    }

    /**
     * Main Plugin logic
     *
     * Load config, create temp bootloaders, add them to mix
     *
     * @param {string} inputPath
     * @param {string} outputPath
     */
    handle(inputPath, outputPath)
    {
        let enableSvelteComponentMode =this.options.componentMode;

        mix.before(async () => {
            try {
                await this.createSvelteBootloaders(inputPath, outputPath, enableSvelteComponentMode);
            } catch (error) {
                console.error('[SvelteDirect] Encountered error...');
                await this.cleanUp();
                throw error;
            }
        });

        mix.after(async () => {
            await this.writeManifest();
            await this.cleanUp();
        });
    }

    /**
     * Write a manifest file that our Blade pre-compiler uses
     * to determine which <tag> maps to which compiled JS file
     *
     * @todo make this an option via config
     */
    async writeManifest()
    {
        const bootstrapFile = resolve( 'bootstrap', 'cache') + '/svelte-direct-components.php';
        let phpCode = '<?php return [';

        let arrayContent = this.manifest.reduce(
            (previous, current) => previous + `'${current.tag}' => '${current.filename}', `
            , ''
        )

        phpCode = phpCode + arrayContent + '];';

        await writeFile(bootstrapFile, phpCode, 'utf8');
    }

    /**
     * Cleanup our generated bootstrap JS files
     */
    async cleanUp()
    {
        for (const f of this.tempJavascriptBootloaderFiles) {
            const compiledFilename = f.replace('.svelte', '.js');
            //await unlink(compiledFilename);
        }
    }

    /**
     * Locate all Svelte files in the given path
     *
     * @param {string} dir
     */
    async* fetchSvelteFiles(dir) {
        const dirents = await readdir(dir, { withFileTypes: true });
        for (const dirent of dirents) {
            const res = resolve(dir, dirent.name);
            if (dirent.isDirectory()) {
                yield* this.fetchSvelteFiles(res);
            } else if (res.toLowerCase().indexOf('.svelte') !== -1) {
                yield res;
            }
        }
    }

    /**
     * Generate bootstrap JS files that load our Svelte components
     *
     * @param {string} inputPath
     * @param {string} outputPath
     * @param {boolean} enableSvelteComponentMode
     */
    async createSvelteBootloaders(inputPath, outputPath, enableSvelteComponentMode) {
        this.tempJavascriptBootloaderFiles = [];

        for await (const f of this.fetchSvelteFiles(inputPath)) {
            const compiledFilename = f.replace('.svelte', '.js');
            const svelteAppData = enableSvelteComponentMode
                ? await this.generateBootstrapSvelteComponent(f)
                : await this.generateBootstrapWebComponent(f);

            await writeFile(compiledFilename, svelteAppData.code, 'utf8');

            mix.js(compiledFilename, outputPath);

            this.manifest.push({
                tag: svelteAppData.tag,
                filename: this.normalizePath(outputPath) + '/' + basename(compiledFilename)
            })

            this.tempJavascriptBootloaderFiles.push(compiledFilename);
        }
    };

    /**
     * Prepare the provided path for processing.
     *
     * Stolen from Laravel Mix to make sure we were compatible
     *
     * @param {string} filePath
     */
    normalizePath(filePath) {
        if (
            Mix.config.publicPath &&
            filePath.startsWith(Mix.config.publicPath)
        ) {
            filePath = filePath.substring(Mix.config.publicPath.length);
        }
        filePath = filePath.replace(/\\/g, '/');

        if (!filePath.startsWith('/')) {
            filePath = '/' + filePath;
        }

        return filePath;
    }

    /**
     * Generate bootstrap JS code for Svelte Component
     *
     * Using WebComponents/Svelte (customElement:true)
     *
     * @param {string} svelteComponentPath
     */
    async generateBootstrapWebComponent(svelteComponentPath)
    {
        let svelteCode = await readFile(svelteComponentPath).then();
        let svelteTagName = findSvelteTagName(svelteCode);

        if (!svelteTagName) {
            throw '[SvelteDirect] Cannot Determine Tag Name In: ' + svelteComponentPath
        }

        return {
            code: 'export { default as App } from "./' + basename(svelteComponentPath) + '";',
            tag: svelteTagName
        };
    }

    /**
     * Generate bootstrap JS code for Svelte Component
     *
     * Using Standard Svelte Component (customElement: false)
     *
     * @param {string} svelteComponentPath
     */
    async generateBootstrapSvelteComponent(svelteComponentPath)
    {
        let svelteCode = await readFile(svelteComponentPath).then();
        let svelteProps = findSvelteProps(svelteCode);
        let svelteTagName = findSvelteTagName(svelteCode);

        if (!svelteTagName) {
            throw '[SvelteDirect] Cannot Determine Tag Name In: ' + svelteComponentPath
        }

        return {
            code: `

        // Generated By SvelteDirect

        import component from "svelte-tag";
        import App from "./${basename(svelteComponentPath)}"
        const props = JSON.parse('${JSON.stringify(svelteProps)}');

        new component({component:App,tagname:"${svelteTagName}",attributes: props, shadow: false})

        `,
            tag: svelteTagName
        };
    }
}

mix.extend("svelteDirect", new SvelteDirect());
