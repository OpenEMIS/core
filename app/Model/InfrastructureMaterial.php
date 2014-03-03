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

class InfrastructureMaterial extends AppModel {
	public $belongsTo = array('InfrastructureCategory');
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
		$Category = ClassRegistry::init('InfrastructureCategory');
		$options = $Category->find('list', array('conditions' => array('name' => array('Buildings', 'Sanitation'))));
		
		$fields = array(
			'national_code' => array('label' => 'National Code', 'display' => true), 
			'international_code' => array('label' => 'International Code', 'display' => true),
			'infrastructure_category_id' => array(
				'label' => 'Category', 
				'display' => false, 
				'options' => $options
			)
		);
		return $fields;
	}
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$lookup = array('Materials' => array('model' => $modelName));
		return $lookup;
	}
}
