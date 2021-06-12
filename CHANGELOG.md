# Changelog

All notable changes to `laravel-svelte-direct` will be documented in this file.

## 0.1.0 - 2021-06-11

- Refactor the generated bootstrap JavaScript to use the `svelte-tag` package under the hood 

- Replace custom @sveltedirect Blade directive with Laravel's built-in @stack('sveltedirect')

- Migrate Laravel Svelte Direct Mix from its own package into this one

- Improve Regex for tag matching

- Refactor the main ServiceProvider to have cleaner functions

- Added several tests for the rest of the ServiceProvider functions

## 0.0.1b - 2021-05-24

- Hotfix to add default values for class vars, was causing errors in some cases

## 0.0.1 - 2021-05-22

- Initial proof-of-concept packages created
