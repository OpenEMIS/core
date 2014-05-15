<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('AclInterface', 'Controller/Component/Acl');

/**
 * DbAcl implements an ACL control system in the database.  ARO's and ACO's are
 * structured into trees and a linking table is used to define permissions.  You
 * can install the schema for DbAcl with the Schema Shell.
 *
 * `$aco` and `$aro` parameters can be slash delimited paths to tree nodes.
 *
 * eg. `controllers/Users/edit`
 *
 * Would point to a tree structure like
 *
 * {{{
 *	controllers
 *		Users
 *			edit
 * }}}
 *
 * @package       Cake.Controller.Component
 */
class DbAcl extends Object implements AclInterface {

/**
 * Constructor
 *
 */
	public function __construct() {
		parent::__construct();
		App::uses('AclNode', 'Model');
		$this->Aro = ClassRegistry::init(array('class' => 'Aro', 'alias' => 'Aro'));
		$this->Aco = ClassRegistry::init(array('class' => 'Aco', 'alias' => 'Aco'));
	}

/**
 * Initializes the containing component and sets the Aro/Aco objects to it.
 *
 * @param AclComponent $component
 * @return void
 */
	public function initialize(Component $component) {
		$component->Aro = $this->Aro;
		$component->Aco = $this->Aco;
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success (true if ARO has access to action in ACO, false otherwise)
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#checking-permissions-the-acl-component
 */
	public function check($aro, $aco, $action = "*") {
		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
		$aroPath = $this->Aro->node($aro);
		$acoPath = $this->Aco->node($aco);

		if (empty($aroPath) || empty($acoPath)) {
			trigger_error(__d('cake_dev', "DbAcl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: ") . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		if ($acoPath == null || $acoPath == array()) {
			trigger_error(__d('cake_dev', "DbAcl::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: ") . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error(__d('cake_dev', "ACO permissions key %s does not exist in DbAcl::check()", $action), E_USER_NOTICE);
			return false;
		}

		$inherited = array();
		$acoIDs = Set::extract($acoPath, '{n}.' . $this->Aco->alias . '.id');

		$count = count($aroPath);
		for ($i = 0; $i < $count; $i++) {
			$permAlias = $this->Aro->Permission->alias;

			$perms = $this->Aro->Permission->find('all', array(
				'conditions' => array(
					"{$permAlias}.aro_id" => $aroPath[$i][$this->Aro->alias]['id'],
					"{$permAlias}.aco_id" => $acoIDs
				),
				'order' => array($this->Aco->alias . '.lft' => 'desc'),
				'recursive' => 0
			));

			if (empty($perms)) {
				continue;
			} else {
				$perms = Set::extract($perms, '{n}.' . $this->Aro->Permission->alias);
				foreach ($perms as $perm) {
					if ($action == '*') {

						foreach ($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] == -1) {
									return false;
								} elseif ($perm[$key] == 1) {
									$inherited[$key] = 1;
								}
							}
						}

						if (count($inherited) === count($permKeys)) {
							return true;
						}
					} else {
						switch ($perm['_' . $action]) {
							case -1:
								return false;
							case 0:
								continue;
							break;
							case 1:
								return true;
							break;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $actions Action (defaults to *)
 * @param integer $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
 * @return boolean Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
 */
	public function allow($aro, $aco, $actions = "*", $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
		$save = array();

		if ($perms == false) {
			trigger_error(__d('cake_dev', 'DbAcl::allow() - Invalid node'), E_USER_WARNING);
			return false;
		}
		if (isset($perms[0])) {
			$save = $perms[0][$this->Aro->Permission->alias];
		}

		if ($actions == "*") {
			$permKeys = $this->_getAcoKeys($this->Aro->Permission->schema());
			$save = array_combine($permKeys, array_pad(array(), count($permKeys), $value));
		} else {
			if (!is_array($actions)) {
				$actions = array('_' . $actions);
			}
			if (is_array($actions)) {
				foreach ($actions as $action) {
					if ($action{0} != '_') {
						$action = '_' . $action;
					}
					if (in_array($action, $permKeys)) {
						$save[$action] = $value;
					}
				}
			}
		}
		list($save['aro_id'], $save['aco_id']) = array($perms['aro'], $perms['aco']);

		if ($perms['link'] != null && !empty($perms['link'])) {
			$save['id'] = $perms['link'][0][$this->Aro->Permission->alias]['id'];
		} else {
			unset($save['id']);
			$this->Aro->Permission->id = null;
		}
		return ($this->Aro->Permission->save($save) !== false);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html#assigning-permissions
 */
	public function deny($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, -1);
	}

/**
 * Let access for $aro to action $action in $aco be inherited
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 */
	public function inherit($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, 0);
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @see allow()
 */
	public function grant($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action);
	}

/**
 * Deny access for $aro to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success
 * @see deny()
 */
	public function revoke($aro, $aco, $action = "*") {
		return $this->deny($aro, $aco, $action);
	}

/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @return array Indexed array with: 'aro', 'aco' and 'link'
 */
	public function getAclLink($aro, $aco) {
		$obj = array();
		$obj['Aro'] = $this->Aro->node($aro);
		$obj['Aco'] = $this->Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}

		return array(
			'aro' => Set::extract($obj, 'Aro.0.' . $this->Aro->alias . '.id'),
			'aco' => Set::extract($obj, 'Aco.0.' . $this->Aco->alias . '.id'),
			'link' => $this->Aro->Permission->find('all', array('conditions' => array(
				$this->Aro->Permission->alias . '.aro_id' => Set::extract($obj, 'Aro.0.' . $this->Aro->alias . '.id'),
				$this->Aro->Permission->alias . '.aco_id' => Set::extract($obj, 'Aco.0.' . $this->Aco->alias . '.id')
			)))
		);
	}

/**
 * Get the keys used in an ACO
 *
 * @param array $keys Permission model info
 * @return array ACO keys
 */
	protected function _getAcoKeys($keys) {
		$newKeys = array();
		$keys = array_keys($keys);
		foreach ($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}

}
