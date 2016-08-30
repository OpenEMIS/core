<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class NoticesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testNoticeIndex()
    {
        $this->setAuthSession();
        $this->get('/Notices');
        $this->assertResponseCode(200);
    }
}
