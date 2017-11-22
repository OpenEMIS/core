<?php
namespace App\Test\TestCase\Shell\Task;

use App\Shell\Task\InstitutionStatusTask;
use Cake\TestSuite\TestCase;

/**
 * App\Shell\Task\InstitutionStatusTask Test Case
 */
class InstitutionStatusTaskTest extends TestCase
{

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|\PHPUnit_Framework_MockObject_MockObject
     */
    public $io;

    /**
     * Test subject
     *
     * @var \App\Shell\Task\InstitutionStatusTask
     */
    public $InstitutionStatus;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();

        $this->InstitutionStatus = $this->getMockBuilder('App\Shell\Task\InstitutionStatusTask')
            ->setConstructorArgs([$this->io])
            ->getMock();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->InstitutionStatus);

        parent::tearDown();
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
