<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class BankBranch extends AppModel {
	public $belongsTo = array('Bank');
	public $actsAs = array('FieldOption');
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Option'
			)
		)
	);
	
	public function getOptionFields() {
		$Bank = ClassRegistry::init('Bank');
		$options = $Bank->find('list', array('order' => array('order')));
		
		$fields = array(
			'code' => array('label' => 'Code', 'display' => true),
			'bank_id' => array(
				'label' => 'Bank', 
				'display' => false, 
				'options' => $options
			)
		);
		return $fields;
	}
	
	public function getSubOptions() {
		$modelName = get_class($this);
		$Bank = ClassRegistry::init('Bank');
		$list = $Bank->find('list', array('order' => array('order')));
		$options = array();
		foreach($list as $id => $name) {
			$options[] = array('model' => $modelName, 'label' => $name, 'conditions' => array('bank_id' => $id));
		}
		return $options;
	}
	
	public function getLookupVariables() {
		$Bank = ClassRegistry::init('Bank');
		$list = $Bank->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array('model' => 'BankBranch', 'conditions' => array('bank_id' => $id));
		}
		return $lookup;
	}
}
?>