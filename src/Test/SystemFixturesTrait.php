<?php
namespace App\Test;

trait SystemFixturesTrait {
    public $fixtures = [
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.user_identities',
        'app.identity_types',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];
}
