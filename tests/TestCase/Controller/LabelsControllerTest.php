<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class LabelsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testLabelsIndex()
    {
        $this->setAuthSession();
        $this->get('/Labels');
        $this->assertResponseCode(200);
    }
}
