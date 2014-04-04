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

class IdentityType extends AppModel {
	public $hasMany = array('StaffIdentity', 'StudentIdentity', 'TeacherIdentity');
	public $actsAs = array('FieldOption');
	
	public function getDisplayFields() {
		$model = get_class($this);
		$fields = array(
			array('field' => 'title', 'model' => $model),
			array('field' => 'date_of_behaviour', 'model' => $model, 'type' => 'datepicker', 'dateOptions' => array('id' => 'date')),
			array('field' => 'time_of_behaviour', 'model' => $model, 'type' => 'timepicker', 'timeOptions' => array('id' => 'time')),
			array('field' => 'description', 'model' => $model),
			array('field' => 'action', 'model' => $model),
			array('field' => 'name', 'model' => 'BehaviourCategory', 'label' => 'Behaviour Category', 'edit' => false), // for view
			array('field' => 'behaviour_category_id', 'model' => $model, 'options' => $categoryOptions, 'edit' => true), // for edit
			array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
			array('field' => 'modified', 'model' => $model, 'label' => 'Modified On', 'edit' => false),
			array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
			array('field' => 'created', 'model' => $model, 'label' => 'Created On', 'edit' => false)
		);
		return $fields;
	}
	/*
	public function getLookupVariables() {
		$lookup = array(
			'Identity Types' => array('model' => 'IdentityType')
		);
		return $lookup;
	}

	public function getOptions(){
		$data = $this->find('all', array('recursive' => -1, 'conditions'=>array('visible'=>1), 'order' => array('IdentityType.order')));
		$list = array();
		foreach($data as $obj){
			$list[$obj['IdentityType']['id']] = $obj['IdentityType']['name'];
		}

		return $list;
	}
	*/
}
