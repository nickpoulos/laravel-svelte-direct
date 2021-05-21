<p align="center"><img src="readme.jpg" width="75%"></p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nickpoulos/laravel-svelte-direct.svg?style=flat-square)](https://packagist.org/packages/nickpoulos/laravel-svelte-direct)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/nickpoulos/laravel-svelte-direct/run-tests?label=tests)](https://github.com/nickpoulos/laravel-svelte-direct/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/nickpoulos/laravel-svelte-direct/Check%20&%20fix%20styling?label=code%20style)](https://github.com/nickpoulos/laravel-svelte-direct/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nickpoulos/laravel-svelte-direct.svg?style=flat-square)](https://packagist.org/packages/nickpoulos/laravel-svelte-direct)

## What? 
Use Svelte Components from within your Laravel Blade Templates -- totally seamlessly. 

## Why? 

Modern JavaScript has been a gift and a curse. An amazing gift for developing front ends, plus a whole lot of cursing trying to get it all configured and setup.   

Things that used to be very simple became increasingly complex overnight. With build steps, webpack, SSR, code-splitting, and everything else, it can get overwhelming quick. 

There have been several awesome attempts to get the best of both worlds, especially within the Laravel community. Projects like Livewire and Alpine.js are amazing and really inspired the creation of this project.    

Lately I have really taken a liking to Svelte, a different take on the typical React/Vue style application. It was refreshing to write less and do more with Javascript, but I still want my Blade templates and the old-school style of server side rendering.  

Normally in this situation, Laravel is just there to serve the shell of the DOM, and then have Svelte/Vue/React take over your entire body tag, or very large chunks of your DOM.

But I like eating my cake too, and so this little project was born. 


## How? 

This project consists of two pieces.  

- Laravel Mix plugin installed via NPM
  - Compiles Svelte components into bite-sized JS files
    

- Blade Pre-Compiler/Directive installed via Composer
  - Scans Blade templates and loads the right bite sized component JS

### Install Laravel Svelte Direct JavaScript
```bash
npm install laravel-svelte-direct
````

### Configure Laravel Mix
webpack.mix.js
```javascript
const mix = require('laravel-mix');
require('laravel-svelte-direct')

mix.svelteDirect('resources/js/Components', 'public/js');

```

### Write Your Svelte Components

Write your Svelte components as your normally would, except for two small additions that we will add to the top of our file. Both are part of the official Svelte docs/spec and are not custom syntax.  
```html
<!-- svelte-ignore missing-custom-element-compile-options -->
<svelte:options tag="flash-message" />
```
The options tag tells Svelte (and Svelte Direct), what the component's HTML tag should be. Normally this technique is only used in Svelte when compiling to WebComponents (more on that later). But it is the perfect mechanism for our cause as well. 

The comment tag tells Svelte to ignore when we don't have `customElement` set to true. 


### Install Laravel Svelte Direct PHP

You can install the package via composer:

```bash
composer require nickpoulos/laravel-svelte-direct
```

### Configure Blade Template

In your applications's main Blade layout/component, add the `@sveltedirect` Blade directive in your `<head>` tag.  

Feel free to add your Svelte component anywhere inside the Blade HTML. You will notice the tag we use in the HTML below matches the `<svelte:options>` tag attribute above.

example.blade.php
```php
<!doctype html>
<html>
<head>
    <title>My Example App</title>
    
    @sveltedirect
    
</head>
<body>
<div class="container">

    <!-- example Svelte components here --> 
    
    <app-header>
        <flash-message type="success" message="test" />
    </app-header>
    
    <!-- end components -->
    
</div>

<script type="text/javascript">
    // tie your components together using vanilla js or something ike alpine
</script>
</body>
</html>

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.
    
## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nick Poulos](https://github.com/nickpoulos)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
