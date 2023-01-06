<?php
namespace App\Test\TestCase\Form;

use App\Form\DatabaseInstallerForm;
use Cake\TestSuite\TestCase;

/**
 * App\Form\DatabaseInstallerForm Test Case
 */
class DatabaseInstallerFormTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Form\DatabaseInstallerForm
     */
    public $DatabaseInstaller;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->DatabaseInstaller = new DatabaseInstallerForm();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->DatabaseInstaller);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
