<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AcademicPeriodsControllerDeleteTest extends AppTestCase
{
    private $testingId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/AcademicPeriods/Periods/');
    }

    public function testDelete() {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertFalse($exists);
    }
}
