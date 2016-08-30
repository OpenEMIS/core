<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionCustomFieldsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testInstitutionCustomFieldIndex()
    {
        $this->setAuthSession();
        $this->get('/InstitutionCustomFields/Fields');
        $this->assertResponseCode(200);
    }
}
