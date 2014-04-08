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
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	public $actsAs = array('FieldOption');
	
	public function getDisplayFields() {
		/*
		$data = array(
			'model' => get_class($this),
			'fields' => array(
				array('field' => 'name', 'label' => 'Name'),
				array('field' => 'international_code', 'label' => 'International Code'),
				array('field' => 'national_code', 'label' => 'National Code'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'model' => $model, 'label' => 'Modified On', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'model' => $model, 'label' => 'Created On', 'edit' => false)
			)
		);
		return $data;
		*/
	}
}
