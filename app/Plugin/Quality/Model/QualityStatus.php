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

class QualityStatus extends QualityAppModel {
	public $actsAs = array(
		'DatePicker' => array('date_enabled', 'date_disabled')
	);

	public $belongsTo = array(
		'AcademicPeriodLevel',
		'RubricTemplate' => array(
            'className' => 'Quality.RubricTemplate',
            'foreignKey' => 'rubric_template_id'
        ),
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

	public $hasAndBelongsToMany = array(
		'AcademicPeriod' => array(
			'className' => 'AcademicPeriod',
			'joinTable' => 'quality_status_periods',
			'foreignKey' => 'quality_status_id',
			'associationForeignKey' => 'academic_period_id',
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name', 'AcademicPeriod.order'),
			'order' => array('AcademicPeriod.order')
		),
		'EducationProgramme' => array(
			'className' => 'EducationProgramme',
			'joinTable' => 'quality_status_programmes',
			'foreignKey' => 'quality_status_id',
			'associationForeignKey' => 'education_programme_id',
			'fields' => array('EducationProgramme.id', 'EducationProgramme.name', 'EducationProgramme.order'),
			'order' => array('EducationProgramme.order')
		),
		'SecurityRole' => array(
			'className' => 'SecurityRole',
			'joinTable' => 'quality_status_roles',
			'foreignKey' => 'quality_status_id',
			'associationForeignKey' => 'security_role_id',
			'fields' => array('SecurityRole.id', 'SecurityRole.name', 'SecurityRole.order'),
			'order' => array('SecurityRole.order')
		)
	);

    public $validate = array(
		'rubric_template_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a rubric template.'
			)
		),
		'academic_period_level_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select an academic period level.'
			)
		)
	);
}
