<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * WincacheEngineTest class
 *
 */
class WincacheEngineTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!function_exists('wincache_ucache_set'), 'Wincache is not installed or configured properly.');
        $this->skipIf(!ini_get('wincache.enablecli'), 'Wincache is not enabled on the CLI.');
        Cache::enable();
        $this->_configCache();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('wincache');
        Cache::drop('wincache_groups');
    }

    /**
     * Helper method for testing.
     *
     * @param array $config
     * @return void
     */
    protected function _configCache($config = [])
    {
        $defaults = [
            'className' => 'Wincache',
            'prefix' => 'cake_'
        ];
        Cache::drop('wincache');
        Cache::config('wincache', array_merge($defaults, $config));
    }

    /**
     * testReadAndWriteCache method
     *
     * @return void
     */
    public function testReadAndWriteCache()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'wincache');
        $expecting = '';
        $this->assertEquals($expecting, $result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('test', $data, 'wincache');
        $this->assertTrue($result);

        $result = Cache::read('test', 'wincache');
        $expecting = $data;
        $this->assertEquals($expecting, $result);

        Cache::delete('test', 'wincache');
    }

    /**
     * testExpiry method
     *
     * @return void
     */
    public function testExpiry()
    {
        $this->_configCache(['duration' => 1]);

        $result = Cache::read('test', 'wincache');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'wincache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'wincache');
        $this->assertFalse($result);

        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('other_test', $data, 'wincache');
        $this->assertTrue($result);

        sleep(2);
        $result = Cache::read('other_test', 'wincache');
        $this->assertFalse($result);
    }

    /**
     * testDeleteCache method
     *
     * @return void
     */
    public function testDeleteCache()
    {
        $data = 'this is a test of the emergency broadcasting system';
        $result = Cache::write('delete_test', $data, 'wincache');
        $this->assertTrue($result);

        $result = Cache::delete('delete_test', 'wincache');
        $this->assertTrue($result);
    }

    /**
     * testDecrement method
     *
     * @return void
     */
    public function testDecrement()
    {
        $this->skipIf(
            !function_exists('wincache_ucache_dec'),
            'No wincache_ucache_dec() function, cannot test decrement().'
        );

        $result = Cache::write('test_decrement', 5, 'wincache');
        $this->assertTrue($result);

        $result = Cache::decrement('test_decrement', 1, 'wincache');
        $this->assertEquals(4, $result);

        $result = Cache::read('test_decrement', 'wincache');
        $this->assertEquals(4, $result);

        $result = Cache::decrement('test_decrement', 2, 'wincache');
        $this->assertEquals(2, $result);

        $result = Cache::read('test_decrement', 'wincache');
        $this->assertEquals(2, $result);
    }

    /**
     * testIncrement method
     *
     * @return void
     */
    public function testIncrement()
    {
        $this->skipIf(
            !function_exists('wincache_ucache_inc'),
            'No wincache_inc() function, cannot test increment().'
        );

        $result = Cache::write('test_increment', 5, 'wincache');
        $this->assertTrue($result);

        $result = Cache::increment('test_increment', 1, 'wincache');
        $this->assertEquals(6, $result);

        $result = Cache::read('test_increment', 'wincache');
        $this->assertEquals(6, $result);

        $result = Cache::increment('test_increment', 2, 'wincache');
        $this->assertEquals(8, $result);

        $result = Cache::read('test_increment', 'wincache');
        $this->assertEquals(8, $result);
    }

    /**
     * test the clearing of cache keys
     *
     * @return void
     */
    public function testClear()
    {
        wincache_ucache_set('not_cake', 'safe');
        Cache::write('some_value', 'value', 'wincache');

        $result = Cache::clear(false, 'wincache');
        $this->assertTrue($result);
        $this->assertFalse(Cache::read('some_value', 'wincache'));
        $this->assertEquals('safe', wincache_ucache_get('not_cake'));
    }

    /**
     * Tests that configuring groups for stored keys return the correct values when read/written
     * Shows that altering the group value is equivalent to deleting all keys under the same
     * group
     *
     * @return void
     */
    public function testGroupsReadWrite()
    {
        Cache::config('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'wincache_groups'));

        wincache_ucache_inc('test_group_a');
        $this->assertFalse(Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value2', 'wincache_groups'));
        $this->assertEquals('value2', Cache::read('test_groups', 'wincache_groups'));

        wincache_ucache_inc('test_group_b');
        $this->assertFalse(Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::write('test_groups', 'value3', 'wincache_groups'));
        $this->assertEquals('value3', Cache::read('test_groups', 'wincache_groups'));
    }

    /**
     * Tests that deleteing from a groups-enabled config is possible
     *
     * @return void
     */
    public function testGroupDelete()
    {
        Cache::config('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);
        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertEquals('value', Cache::read('test_groups', 'wincache_groups'));
        $this->assertTrue(Cache::delete('test_groups', 'wincache_groups'));

        $this->assertFalse(Cache::read('test_groups', 'wincache_groups'));
    }

    /**
     * Test clearing a cache group
     *
     * @return void
     */
    public function testGroupClear()
    {
        Cache::config('wincache_groups', [
            'engine' => 'Wincache',
            'duration' => 0,
            'groups' => ['group_a', 'group_b'],
            'prefix' => 'test_'
        ]);

        $this->assertTrue(Cache::write('test_groups', 'value', 'wincache_groups'));
        $this->assertTrue(Cache::clearGroup('group_a', 'wincache_groups'));
        $this->assertFalse(Cache::read('test_groups', 'wincache_groups'));

        $this->assertTrue(Cache::write('test_groups', 'value2', 'wincache_groups'));
        $this->assertTrue(Cache::clearGroup('group_b', 'wincache_groups'));
        $this->assertFalse(Cache::read('test_groups', 'wincache_groups'));
    }
}
