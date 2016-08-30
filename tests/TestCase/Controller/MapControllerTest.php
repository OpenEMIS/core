<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class MapControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testMap()
    {
        $this->setAuthSession();
        $this->get('/Map');
        $this->assertResponseCode(200);
    }
}
