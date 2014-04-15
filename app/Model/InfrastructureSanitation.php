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

class InfrastructureSanitation extends AppModel {
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
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Sanitation'));
		$options = array(
			array('model' => $modelName, 'label' => 'Category'),
			array('model' => 'InfrastructureMaterial', 'label' => 'Material', 'conditions' => array('infrastructure_category_id' => $categoryId)),
			array('model' => 'InfrastructureStatus', 'label' => 'Status', 'conditions' => array('infrastructure_category_id' => $categoryId))
		);
		return $options;
	}
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Sanitation'));
		$lookup = array(
			'Sanitation' => array('model' => $modelName),
			'Materials' => array(
				'model' => 'InfrastructureMaterial',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			),
			'Status' => array(
				'model' => 'InfrastructureStatus',
				'conditions' => array('infrastructure_category_id' => $categoryId)
			)
		);
		return $lookup;
	}
	
	public function findListAsSubgroups() {
		$categoryModel = ClassRegistry::init('InfrastructureCategory');
		$categoryId = $categoryModel->field('id', array('name' => 'Sanitation'));
		$statusModel = ClassRegistry::init('InfrastructureStatus');
		$conditions = array('InfrastructureStatus.infrastructure_category_id' => $categoryId, 'InfrastructureStatus.visible' => 1);
		return $statusModel->findList(array('conditions' => $conditions));
	}
}
