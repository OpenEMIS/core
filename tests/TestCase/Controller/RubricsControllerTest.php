<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class RubricsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testRubricIndex()
    {
        $this->setAuthSession();
        $this->get('/Rubrics/Templates');
        $this->assertResponseCode(200);
    }
}
