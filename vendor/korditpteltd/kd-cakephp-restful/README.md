# Restful Plugin 

[![Version](https://img.shields.io/badge/Version-1.0.1-green.svg)](https://demo.openemis.org/integrator)

KORDIT CakePHP V3 Restful Plugin.


## Requirements
The `master` branch has the following requirements:

* [![CakePHP](https://img.shields.io/badge/CakePHP->3.0.9-yellowgreen.svg)](http://cakephp.org)
* [![CakePHP](https://img.shields.io/badge/CakePHP-<3.2.7-yellowgreen.svg)](http://cakephp.org)
* [![PHP](https://img.shields.io/badge/PHP->%3D5.4.16-yellowgreen.svg)](http://cakephp.org)


## Installation
Install this plugin using `composer` from your CakePHP V3 Project ROOT directory (where the `composer.json` file is located). 


* Edit `composer.json` file by adding the following lines:
```json
"repositories":[
        {
            "type": "git",
            "url": "git@bitbucket.org:korditpteltd/kd-cakephp-restful.git"
        }
    ]
```

* If `composer` is not installed globally, issue the following command on console/terminal:
```sh
php composer.phar require korditpteltd/kd-cakephp-restful "*"
```

* If `composer` is installed globally, issue the following command instead:
```sh
composer require korditpteltd/kd-cakephp-restful "*"
```


## Configuration
* [Load this plugin](http://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin) by adding the following line in your application `bootstrap.php` file.
```php
Plugin::load('Restful', ['bootstrap' => true, 'routes' => true, 'autoload' => true]);
```
- `boostrap` parameter is set to true so that this package bootstrap file will be loaded during the application life-cycle boot up process.
- This package's boostrap.php will then include its routes configurations.

## Usage
coming soon...