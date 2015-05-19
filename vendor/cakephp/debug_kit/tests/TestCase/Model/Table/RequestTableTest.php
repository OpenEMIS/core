<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace DebugKit\Test\TestCase\Model\Table;

use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tests for request table.
 */
class RequestTableTest extends TestCase
{

    /**
     * test that schema is created on-demand.
     *
     * @return void
     */
    public function testInitializeCreatesSchema()
    {
        $connection = ConnectionManager::get('test');
        $this->skipIf($connection->driver() instanceof Sqlite, 'Schema insertion/removal breaks SQLite');

        TableRegistry::clear();
        
        $stmt = $connection->execute('DROP TABLE IF EXISTS panels');
        $stmt->closeCursor();

        $stmt = $connection->execute('DROP TABLE IF EXISTS requests');
        $stmt->closeCursor();

        TableRegistry::get('DebugKit.Requests');
        TableRegistry::get('DebugKit.Panels');

        $schema = $connection->schemaCollection();
        $this->assertContains('requests', $schema->listTables());
        $this->assertContains('panels', $schema->listTables());
    }

    /**
     * Test the recent finder.
     *
     * @return void
     */
    public function testFindRecent()
    {
        $table = TableRegistry::get('DebugKit.Requests');
        $query = $table->find('recent');
        $this->assertEquals(10, $query->clause('limit'));
        $this->assertNotEmpty($query->clause('order'));
    }
}
