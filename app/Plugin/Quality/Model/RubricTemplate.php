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

class RubricTemplate extends QualityAppModel {
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
        'RubricTemplateGrade' => array(
            'className' => 'Quality.RubricTemplateGrade',
			'dependent' => true
        ),
        'RubricTemplateOption' => array(
            'className' => 'Quality.RubricTemplateOption',
			'dependent' => true
        ),
        'RubricSection' => array(
            'className' => 'Quality.RubricSection',
			'dependent' => true
        )
    );

    public $hasAndBelongsToMany = array(
		'SecurityRole' => array(
			'className' => 'SecurityRole',
			'joinTable' => 'rubric_template_roles',
			'associationForeignKey' => 'security_role_id',
			'fields' => array('SecurityRole.id', 'SecurityRole.name', 'SecurityRole.order'),
			'order' => array('SecurityRole.order')
		)
	);

	public function beforeAction() {
		$this->Navigation->addCrumb('Rubrics Templates');

		if ($this->action == 'view') {

		} else if($this->action == 'add' || $this->action == 'edit') {
			$weightingTypeOptions = array(1 => __('Points'), 2 => __('Percentage'));
			$this->fields['weighting_type']['type'] = 'select';
			$this->fields['weighting_type']['options'] = $weightingTypeOptions;

			if ($this->request->is(array('post', 'put'))) {

			} else {

			}
		}

		$this->controller->set('contentHeader', __('Rubrics Templates'));
	}
}
