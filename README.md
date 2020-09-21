# Laravel Translation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vemcogroup/laravel-translation.svg?style=flat-square)](https://packagist.org/packages/vemcogroup/laravel-translation)
[![Total Downloads](https://img.shields.io/packagist/dt/vemcogroup/laravel-translation.svg?style=flat-square)](https://packagist.org/packages/vemcogroup/laravel-translation)

## Description

This package allows you to scan your app for translations and create your *.json file.

It also allows you to upload your base translation to [poeditor](https://www.poeditor.com).

## Installation

You can install the package via composer:

```bash
composer require vemcogroup/laravel-translation
```

The package will automatically register its service provider.

To publish the config file to `config/translation.php` run:

```bash
php artisan vendor:publish --provider="Vemcogroup\Translation\TranslationServiceProvider"
```

This is the default contents of the configuration:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Base Language
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of language is your base language.
    | The base language select will be created as json file when scanning.
    | It will also be the file it reads and uploads to POEditor.
    |
    */

    'base_language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Functions
    |--------------------------------------------------------------------------
    |
    | Here you define an array describing all the function names to scan files for.
    |
    */

    'functions' => ['__'],
    
    /*
    |--------------------------------------------------------------------------
    | Excluded directories
    |--------------------------------------------------------------------------
    | 
    | Here you define which directories are excluded from scan.
    |
    */
	
    'excluded_directories' => ['vendor', 'storage', 'public', 'node_modules'],
    
    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | Here you define an array describing all the file extensions to scan through.
    |
    */

    'extensions' => ['*.php', '*.vue'],

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Here you define your API Key for POEditor.
    |
    | More info: https://poeditor.com/account/api
    |
    */

    'api_key' => env('POEDITOR_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Project Id
    |--------------------------------------------------------------------------
    |
    | Here you define the project Id to upload / download from.
    |
    */

    'project_id' => env('POEDITOR_PROJECT_ID'),
];
```

If you want to use upload / download to poeditor features, you need to create a your base_language in poeditor.

## Usage

You are now able to use the translation commands scan/upload/download or create-js 

**Scan files**

To scan your project for translations run this command:
```bash
php artisan translation:scan {--merge : Whether the job should overwrite or merge new translations keys}
``` 

The command creates your `base_language` .json file in `/resources/lang`

**Add terms**

To only add your terms run this command:
```bash
php artisan translation:add-terms {--scan : Whether the job should scan before uploading}
```
This command doesn't remove unsused terms, so remember *NOT* to run `upload` command afterward. 


**Upload translations**

To upload your translation terms to poeditor run this command:
```bash
php artisan translation:upload {--scan : Whether the job should scan before uploading}
```

You are also able to upload your local translations if you have locale changes
```bash
php artisan translation:upload {--translations=all : Upload translations for language sv,da,...}
```


**Download translation languages**

To download languages from poeditor run this command:
```bash
php artisan translation:download
``` 

**Create JS language files**

To create public JS files run this command:
```bash
php artisan translation:create-js {--download : Download language files before creating js}
``` 

You are now able to access all your languages as `window.i18n` from `/public/lang` when you include the .js file

````html
<script src="/build/lang/en.js"></script>
````

**System translations**

If you want to translate system translations change the terms in eg `/resources/lang/en/auth.php` 

From:
```php
'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
``` 

To
```php
'throttle' => __('Too many login attempts. Please try again in :seconds seconds.'),
``` 

Then it will be scanned and included in the synced terms.
