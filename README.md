# Hierarchy
Polymorphic content management with node structure.

---
[![Build Status](https://travis-ci.org/NuclearCMS/Hierarchy.svg?branch=master)](https://travis-ci.org/NuclearCMS/Hierarchy)
[![Total Downloads](https://poser.pugx.org/Nuclear/Hierarchy/downloads)](https://packagist.org/packages/Nuclear/Hierarchy)
[![Latest Stable Version](https://poser.pugx.org/Nuclear/Hierarchy/version)](https://packagist.org/packages/Nuclear/Hierarchy)
[![License](https://poser.pugx.org/Nuclear/Hierarchy/license)](https://packagist.org/packages/Nuclear/Hierarchy)

This package is intended for [Nuclear CMS](https://github.com/NuclearCMS/Nuclear) and it constitutes its main content management and content type management functionality. It is developed separately to enable individual development and possible reuse.

## Installation
Installing Hierarchy is simple.

1. Pull this package in through [Composer](https://getcomposer.org).
    ```js
    {
        "require": {
            "nuclear/hierarchy": "~1.0"
        }
    }
    ```

2. In order to register Hierarchy Service Provider add `'Nuclear\Hierarchy\Providers\HierarchyServiceProvider'` and `'Nuclear\Hierarchy\Providers\BuilderServiceProvider'` to the end of `providers` array in your `config/app.php` file.
    ```php
    'providers' => array(
    
        'Illuminate\Foundation\Providers\ArtisanServiceProvider',
        'Illuminate\Auth\AuthServiceProvider',
        ...
        'Nuclear\Hierarchy\Providers\HierarchyServiceProvider',
        'Nuclear\Hierarchy\Providers\BuilderServiceProvider',
    
    ),
    ```
    **Important:** As of version 1.3 the services inside `HierarchyServiceProvider` is separated into two. The `BuilderServiceProvider` must be registered separately.
    
3. Publish the migrations and configuration file.
    ```bash
        php artisan vendor:publish
    ```
    Do not forget to migrate the database.

4. Finally, register the autoloader in your composer.json file and use `composer dump-autoload` command.
    ```json
    "autoload": {
        "psr-4": {
            "gen\\": "gen/"
        }
    },
    ```
    This is essential for using the entities that are generated by Hierarchy.

5. Please check the tests and source code for further documentation.

## License
Hierarchy is released under [MIT License](https://github.com/NuclearCMS/Hierarchy/blob/master/LICENSE).
