<?php
/**
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('BaseAuthorize', 'Controller/Component/Auth');

/**
 * An authorization adapter for AuthComponent.  Provides the ability to authorize using a controller callback.
 * Your controller's isAuthorized() method should return a boolean to indicate whether or not the user is authorized.
 *
 * {{{
 *	public function isAuthorized($user) {
 *		if (!empty($this->request->params['admin'])) {
 *			return $user['role'] == 'admin';
 *		}
 *		return !empty($user);
 *	}
 * }}}
 *
 * the above is simple implementation that would only authorize users of the 'admin' role to access
 * admin routing.
 *
 * @package       Cake.Controller.Component.Auth
 * @since 2.0
 * @see AuthComponent::$authenticate
 */
class ControllerAuthorize extends BaseAuthorize {

/**
 * Get/set the controller this authorize object will be working with.  Also checks that isAuthorized is implemented.
 *
 * @param mixed $controller null to get, a controller to set.
 * @return mixed
 * @throws CakeException
 */
	public function controller(Controller $controller = null) {
		if ($controller) {
			if (!method_exists($controller, 'isAuthorized')) {
				throw new CakeException(__d('cake_dev', '$controller does not implement an isAuthorized() method.'));
			}
		}
		return parent::controller($controller);
	}

/**
 * Checks user authorization using a controller callback.
 *
 * @param array $user Active user data
 * @param CakeRequest $request
 * @return boolean
 */
	public function authorize($user, CakeRequest $request) {
		return (bool)$this->_Controller->isAuthorized($user);
	}

}
