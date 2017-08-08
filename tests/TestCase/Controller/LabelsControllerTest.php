<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class LabelsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.user_identities',
        'app.identity_types',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];

    public function testIndex()
    {
        $this->get('/Labels');
        $this->assertResponseCode(200);
    }
}
