# CakePHP Single Sign-On Plugin

[![Build Status](https://img.shields.io/badge/Build-passing-brightgreen.svg?style=flat-square)](https://bitbucket.org/korditpteltd/kd-cakephp-webhooks)
[![License](https://img.shields.io/badge/Licence-GPL%202.0-blue.svg?style=flat-square)](LICENSE.txt)

This plugin allows authentication of CakePHP application from Google, OAuth and SAML 2.0 identity provider.

## Requirements

* CakePHP 3.3 (CakePHP 3.4 is not yet supported)

## Installation

* Edit `composer.json` file by adding the following lines:
```json
"repositories":[
        {
            "type": "git",
            "url": "git@bitbucket.org:korditpteltd/kd-cakephp-sso.git"
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
            "url": "git@bitbucket.org:korditpteltd/kd-cakephp-sso.git"
        }
    ]
}
```

```sh
composer require korditpteltd/kd-cakephp-sso "*"
```

## Usage

In your app's `config/bootstrap.php` add:

```php
// In config/bootstrap.php
Plugin::load('SSO');
```

or using cake's console:

```sh
bin/cake plugin load SSO
```

## Configuration

The following will be an example on configurations to be set for an environment with multiple IDP.
Do note that the redirect_uri or sp_acs is added with the Authentication type name together with the system_authentication's code. You may refer to the following sample table for the example of the redirect uri and configuration for each type of IDP.


**system_authentications table**

id | code | name | authentication_type_id | status | allow_create_user | mapped_username | mapped_first_name | mapped_last_name | mapped_date_of_birth | mapped_gender | mapped_role
--- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | ---
1 | IDP59599cc6f065e | Google | 1 | 1 | 1 | email |  |  |  |  |
2 | IDP59599cfb0bcda | One Login - SAML | 2 | 1 | 1 | email | first_name | last_name | date_of_birth | gender | role
3 | IDP59599e294701f | OpenEMIS Portal | 3 | 1 | 1 | user_login | given_name | family_name |  |  |


**idp_google table**

system_authentication_id | client_id | client_secret | redirect_uri | hd
--- | --- | --- | --- | ---
1 | 503787316191-10otl1ffcp481sdnqdfibt0js5dauem3.apps.googleusercontent.com | IzudE2I0hLPJYRiZXUsWfcVR | http://localhost:8080/openemis-phpoe/Users/postLogin/Google/IDP59599cc6f065e | kordit.com

**idp_saml table**

system_authentication_id | idp_entity_id | idp_sso | idp_sso_binding | idp_slo | idp_slo_binding | idp_x509cert | idp_cert_fingerprint | idp_cert_fingerprint_algorithm | sp_entity_id | sp_acs | sp_slo | sp_name_id_format | sp_private_key | sp_metadata
--- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | ---
2 | https://app.onelogin.com/saml/metadata/674788 | https://kord-it-pte-ltd-dev.onelogin.com/trust/saml2/http-post/sso/674788 | urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST | https://kord-it-pte-ltd-dev.onelogin.com/trust/saml2/http-redirect/slo/674788 | urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect | | | | http://localhost:8080/openemis-phpoe | http://localhost:8080/openemis-phpoe/Users/postLogin/Saml/IDP59599cfb0bcda | http://localhost:8080/openemis-phpoe/Users/logout | | | -

**idp_oauth table**

system_authentication_id | client_id | client_secret | redirect_uri | well_known_uri | authorization_endpoint | token_endpoint | userinfo_endpoint | issuer | jwks_uri
--- | --- | --- | --- | --- | --- | --- | --- | --- | ---
3 | jYZF79ttp4ur2G5Us2qDo6uA6hteof | aHdamljQPJVkbX0gSsjj6HpAhvSLKg | http://localhost:8080/openemis-phpoe/Users/postLogin/OAuth/IDP59599e294701f | https://dmo-dev.openemis.org/portal/.well-known/openid-configuration | https://dmo-dev.openemis.org/portal/oauth/authorize | https://dmo-dev.openemis.org/portal/oauth/token | https://dmo-dev.openemis.org/portal/oauth/me | https://dmo-dev.openemis.org/portal | https://dmo-dev.openemis.org/portal/.well-known/keys

**Authentication controller**
Using the 'UsersController' as the example, you may have an action called postLogin and you will call the SSO plugin's doAuthentication function to perform the authentication on the appropriate type.

```php
public function postLogin($authenticationType = 'Local', $code = null)
{
    // Turn off auto render
    $this->autoRender = false;

    // Logic to check if the local login is turn off and if there is only one IDP set as active
    // If there is only one, we will just triggle the IDP authentication
    $enableLocalLogin = TableRegistry::get('Configuration.ConfigItems')->value('enable_local_login');
    $authentications = TableRegistry::get('SSO.SystemAuthentications')->getActiveAuthentications();
    if (!$enableLocalLogin && count($authentications) == 1) {
        $authenticationType = $authentications[0]['authentication_type'];
        $code = $authentications[0]['code'];
    } elseif (is_null($code)) {
        $authenticationType = 'Local';
    }

    // Function call to trigger SSO authentication
    $this->SSO->doAuthentication($authenticationType, $code);
}
```

### Example of implementation of SSO Plugin into login view page

In the controller you can get the list of active system authentications and set the authentication so that the view can render
```php

// Value to show local login or to turn off local login
$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
$localLoginEnabled = $ConfigItems->value('enable_local_login');
$this->set('enableLocalLogin', $localLoginEnabled);

// Setting the list of IDP to choose for authentication on the login page
$SystemAuthentications = TableRegistry::get('SSO.SystemAuthentications');
$authentications = $SystemAuthentications->getActiveAuthentications();
$authenticationOptions = [];
foreach ($authentications as $auth) {
    $authenticationOptions[$auth['name']] = Router::url(['plugin' => 'User', 'controller' => 'Users', 'action' => 'postLogin', $auth['authentication_type'], $auth['code']]);
}
$authentication = [];
if ($authenticationOptions) {
    $authentication[] = [
        'text' => __('Select Single Sign On Method'),
        'value' => 0
    ];
    foreach ($authenticationOptions as $key => $value) {
        $authentication[] = [
            'text' => $key,
            'value' => $value
        ];
    }
}
$this->set('authentications', $authentication);
```

In the ctp file you may have the following implemented:
```php
// Logic to display local login username and password field
// Local login will be turn off if the enable local login configuration is set to off
if ($enableLocalLogin) {
    echo $this->Form->input('username', ['placeholder' => __('Username'), 'label' => false, 'value' => $username]);
    echo $this->Form->input('password', ['placeholder' => __('Password'), 'label' => false, 'value' => $password]);
}

// If there is active IDP, show the dropdown list for the selection of the IDP
if ($authentications) {
    echo $this->Form->input('idp', [
        'options' => $authentications,
        'label' => false,
        'onchange' => 'window.document.location.href=this.options[this.selectedIndex].value;'
    ]);
}
```

## Migration from Version 1 to Version 1.2

Please change the syntax of the code to follow the implementation guide and apply the [a version_1-2_migration.sql](sql/version_1-2_migration_patch.sql) patch. You may execute the [a version_1-2_migration.sql](sql/version_1-2_migration_patch.sql) patch file included in the sql folder of the plugin for the migration to version 1.2 of this plugin.

If the enable local login option is not necessary, you may exclude the patch for the config_items table.

You will be required to change the Redirection URI or the Assertion Consumer Service URL on the identity provider side the match the new value after running the migration patch.

By default, local login will be turn off after the migration if there is an active IDP configured (assuming that the patch is executed as it is).

## Single Logout Implementation
Please execute the following SQL on your database to add the table for the single logout. This table will keep track of your user login session on top of the PHP session on the application.

```sql
CREATE TABLE `security_user_sessions` (
  `id` VARCHAR(40) NOT NULL default '',
  `username` VARCHAR(50) NOT NULL default '',
  PRIMARY KEY (`id`, `username`)
);
```

### Adding a record to the security user sessions table
You may add the following line after the authentication to add an entry to your security user sessions table to keep track of the logon user.

```php
$SecurityUserSessions = TableRegistry::get('SSO.SecurityUserSessions');
$SecurityUserSessions->addEntry($user['username'], $this->request->session()->id());
```


### Removing record from security user sessions table
To logout, your api should trigger the deleteEntries function to trigger the logout of the application. You may use the following code as the example to trigger your logout in your own api function.

```php
$SecurityUserSessions = TableRegistry::get('SSO.SecurityUserSessions');
$SecurityUserSessions->deleteEntries($username);
```

### More examples
You may use the Single Logout api together with the webhooks plugin to trigger mass logout from applications that are already logon. You may go to [https://bitbucket.org/korditpteltd/kd-cakephp-webhooks](https://bitbucket.org/korditpteltd/kd-cakephp-webhooks) to see more example on how this can be implemented with the webhooks plugin.