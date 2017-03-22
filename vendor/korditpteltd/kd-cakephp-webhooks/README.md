# CakePHP Webhooks Plugin

[![Build Status](https://img.shields.io/badge/Build-passing-brightgreen.svg?style=flat-square)](https://bitbucket.org/korditpteltd/kd-cakephp-webhooks)
[![License](https://img.shields.io/badge/Licence-GPL%202.0-blue.svg?style=flat-square)](LICENSE.txt)

Plugin containing the vanilla javascript file for the triggering of webhook events implemented on the application level. Plugin also contain the Table file, SQL structure and the default controller for retrieval of webhook table information for execution.

## Requirements

* CakePHP 3.1+

## Installation

* Edit `composer.json` file by adding the following lines:
```json
"repositories":[
        {
            "type": "git",
            "url": "git@bitbucket.org:korditpteltd/kd-cakephp-webhooks.git"
        }
    ]
```

* Example `composer.json` after adding required lines:
```json
{
    "name": "korditpteltd/openemis-phpoe",
    "description": "KORDIT OpenEMIS CORE",
    "homepage": "https://demo.openemis.org/core",
    "type": "project",
    "license": "GPL-2.0",
    "require": {
        "php": ">=5.4.16",
        "cakephp/cakephp": "3.2.6"
    },
    "scripts": {
        "post-install-cmd": "App\\Console\\Installer::postInstall",
        "post-autoload-dump": "Cake\\Composer\\Installer\\PluginInstaller::postAutoloadDump"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories":[
        {
            "type": "git",
            "url": "git@bitbucket.org:korditpteltd/kd-cakephp-webhooks.git"
        }
    ]
}
```

```sh
composer require korditpteltd/kd-cakephp-webhooks "*"
```

## Usage

In your app's `config/bootstrap.php` add:

```php
// In config/bootstrap.php
Plugin::load('Webhook', ['autoload' => true, 'route' => true]);
```

or using cake's console:

```sh
bin/cake plugin load -r --autoload Webhook
```

## Configuration:

You will need to insert the event entries into the webhooks and webhook_events table



**webhooks table**

id | name | status | url | method | description | modified_user_id | modified | created_user_id | created
--- | --- | --- | --- | --- | --- | --- | --- | --- | ---
1 | Application A Logout | 1 | http://domain.application.com/logout | GET | To logout from application A | `null` | `null` | 1 | 2017-03-17 03:38:50



**webhook_events table**

webhook_id | event_key
--- | ---
1 | logout



### Using JavaScript Webhook
In the ctp file you may have the following event implemented:
```php
// load the javascript file from the plugin
$this->Html->script('Webhook.webhook');

// Webhook url link to the webhook controller
$webhookListUrl = [
    'plugin' => 'Webhook',
    'controller' => 'Webhooks',
    'action' => 'listWebhooks'
];

// You may replace \'logout\', with your event name for example \'login\'
echo $this->Html->link('Logout Link', 'http://someurl.com/logout', ['onclick' => 'Webhook.triggerEvent(\''.Router::url($webhookListUrl).'\', [\'logout\']);']);
```

### Using Backend CakePHP Client Service with Shell

```php
$Webhooks = TableRegistry::get('Webhook.Webhooks');

// Setting the 'username' parameter will replace the url with the username that you have specified
$Webhooks->triggerShell('logout', ['username' => $username]);
```
