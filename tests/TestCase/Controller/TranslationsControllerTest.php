<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class TranslationsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testTranslationsIndex()
    {
        $this->setAuthSession();
        $this->get('/Translations');
        $this->assertResponseCode(200);
    }
}
