<?php
/**
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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AclNode', 'Model');

/**
 * Access Request Object
 *
 * @package       Cake.Model
 */
class Aro extends AclNode {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'Aro';

/**
 * AROs are linked to ACOs by means of Permission
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('Aco' => array('with' => 'Permission'));
}
