<?php
namespace OAuth\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use OAuth\Model\Table\CredentialsTable;

/**
 * OAuth\Model\Table\CredentialsTable Test Case
 */
class CredentialsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \OAuth\Model\Table\CredentialsTable
     */
    public $Credentials;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.o_auth.credentials',
        'plugin.o_auth.clients',
        'plugin.o_auth.modified_users',
        'plugin.o_auth.created_users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Credentials') ? [] : ['className' => 'OAuth\Model\Table\CredentialsTable'];
        $this->Credentials = TableRegistry::get('Credentials', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Credentials);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
