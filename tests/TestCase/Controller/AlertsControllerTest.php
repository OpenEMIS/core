<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class AlertsControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = 'Alerts';
    private $modelAlias = 'Alerts';

    public function __construct()
    {
        $this->fixtures[] = 'app.alerts';
        parent::__construct();
    }

    public function testIndex()
    {
        $this->get('/Alerts/Alerts');
        $this->assertResponseOk();
    }

    public function testRead()
    {
        $this->get('/Alerts/Alerts/view/' . $this->paramsEncode($this->primaryKey));
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }
}
