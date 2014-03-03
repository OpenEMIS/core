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

class FinanceType extends AppModel {
	public $belongsTo = array('FinanceNature');
	public $hasMany = array('FinanceCategory');
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
	
	public function getSubOptions() {
		$modelName = get_class($this);
		$Nature = ClassRegistry::init('FinanceNature');
		$list = $Nature->find('list', array('order' => array('order')));
		$options = array();
		foreach($list as $id => $name) {
			$options[] = array('model' => $modelName, 'label' => $name, 'conditions' => array('finance_nature_id' => $id));
		}
		return $options;
	}
	
	public function getOptionFields() {
		$Nature = ClassRegistry::init('FinanceNature');
		$options = $Nature->find('list', array('order' => array('order')));
		
		$fields = array(
			'national_code' => array('label' => 'National Code', 'display' => true), 
			'international_code' => array('label' => 'International Code', 'display' => true),
			'finance_nature_id' => array(
				'label' => 'Nature', 
				'display' => false, 
				'options' => $options
			)
		);
		return $fields;
	}
	
	public function getLookupVariables() {
		$parent = ClassRegistry::init('FinanceNature');
		$list = $parent->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array('model' => 'FinanceType', 'conditions' => array('finance_nature_id' => $id));
		}
		return $lookup;
	}
}
