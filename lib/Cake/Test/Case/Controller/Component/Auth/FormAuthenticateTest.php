<?php
/**
 * FormAuthenticateTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component.Auth
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AuthComponent', 'Controller/Component');
App::uses('FormAuthenticate', 'Controller/Component/Auth');
App::uses('AppModel', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

require_once CAKE . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 * Test case for FormAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class FormAuthenticateTest extends CakeTestCase {

	public $fixtures = array('core.user', 'core.auth_user');

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Collection = $this->getMock('ComponentCollection');
		$this->auth = new FormAuthenticate($this->Collection, array(
			'fields' => array('username' => 'user', 'password' => 'password'),
			'userModel' => 'User'
		));
		$password = Security::hash('password', null, true);
		$User = ClassRegistry::init('User');
		$User->updateAll(array('password' => $User->getDataSource()->value($password)));
		$this->response = $this->getMock('CakeResponse');
	}

/**
 * test applying settings in the constructor
 *
 * @return void
 */
	public function testConstructor() {
		$object = new FormAuthenticate($this->Collection, array(
			'userModel' => 'AuthUser',
			'fields' => array('username' => 'user', 'password' => 'password')
		));
		$this->assertEquals('AuthUser', $object->settings['userModel']);
		$this->assertEquals(array('username' => 'user', 'password' => 'password'), $object->settings['fields']);
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoData() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array();
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoUsername() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array('password' => 'foobar'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateNoPassword() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array('user' => 'mariano'));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test the authenticate method
 *
 * @return void
 */
	public function testAuthenticateInjection() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array(
			'User' => array(
				'user' => '> 1',
				'password' => "' OR 1 = 1"
		));
		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test authenticate success
 *
 * @return void
 */
	public function testAuthenticateSuccess() {
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => 'password'
		));
		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'user' => 'mariano',
			'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test scope failure.
 *
 * @return void
 */
	public function testAuthenticateScopeFail() {
		$this->auth->settings['scope'] = array('user' => 'nate');
		$request = new CakeRequest('posts/index', false);
		$request->data = array('User' => array(
			'user' => 'mariano',
			'password' => 'password'
		));

		$this->assertFalse($this->auth->authenticate($request, $this->response));
	}

/**
 * test a model in a plugin.
 *
 * @return void
 */
	public function testPluginModel() {
		Cache::delete('object_map', '_cake_core_');
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$PluginModel = ClassRegistry::init('TestPlugin.TestPluginAuthUser');
		$user['id'] = 1;
		$user['username'] = 'gwoo';
		$user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
		$PluginModel->save($user, false);

		$this->auth->settings['userModel'] = 'TestPlugin.TestPluginAuthUser';
		$this->auth->settings['fields']['username'] = 'username';

		$request = new CakeRequest('posts/index', false);
		$request->data = array('TestPluginAuthUser' => array(
			'username' => 'gwoo',
			'password' => 'cake'
		));

		$result = $this->auth->authenticate($request, $this->response);
		$expected = array(
			'id' => 1,
			'username' => 'gwoo',
			'created' => '2007-03-17 01:16:23'
		);
		$this->assertEquals(self::date(), $result['updated']);
		unset($result['updated']);
		$this->assertEquals($expected, $result);
		CakePlugin::unload();
	}

}
