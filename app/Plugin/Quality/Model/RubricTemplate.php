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
	private $weightingType = array(
		1 => array('id' => 1, 'name' => 'Points'),
		2 => array('id' => 2, 'name' => 'Percentage')
	);

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
        'RubricTemplateOption' => array(
            'className' => 'Quality.RubricTemplateOption',
			'dependent' => true
        ),
        'RubricSection' => array(
            'className' => 'Quality.RubricSection',
			'dependent' => true
        ),
        'QualityStatus' => array(
            'className' => 'Quality.QualityStatus',
			'dependent' => true
        )
    );

    public $hasAndBelongsToMany = array(
		'EducationGrade' => array(
			'className' => 'EducationGrade',
			'joinTable' => 'rubric_template_grades',
			'foreignKey' => 'rubric_template_id',
			'associationForeignKey' => 'education_grade_id',
			'fields' => array('EducationGrade.id', 'EducationGrade.programme_grade_name', 'EducationGrade.programme_order'),
			'order' => array('EducationGrade.programme_order', 'EducationGrade.order')
		),
		'SecurityRole' => array(
			'className' => 'SecurityRole',
			'joinTable' => 'rubric_template_roles',
			'foreignKey' => 'rubric_template_id',
			'associationForeignKey' => 'security_role_id',
			'fields' => array('SecurityRole.id', 'SecurityRole.name', 'SecurityRole.order'),
			'order' => array('SecurityRole.order')
		)
	);

    public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('name'), false),
	            'message' => 'This name is already exists in the system'
	        )
		),
		'pass_mark' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a pass mark'
			)
		)
	);

	public function beforeAction() {
		$this->Navigation->addCrumb('Templates');

		$weightingTypeOptions = array();
		foreach ($this->weightingType as $key => $weightingType) {
			$weightingTypeOptions[$weightingType['id']] = __($weightingType['name']);
		}

		$this->fields['security_roles'] = array(
			'type' => 'chosen_select',
			'id' => 'SecurityRole.SecurityRole',
			'placeholder' => __('Select security roles'),
			'visible' => true
		);
		$this->ControllerAction->setFieldOrder('security_roles', 5);

		$this->fields['education_grades'] = array(
			'type' => 'chosen_select',
			'id' => 'EducationGrade.EducationGrade',
			'placeholder' => __('Select education grades'),
			'visible' => true
		);
		$this->ControllerAction->setFieldOrder('education_grades', 6);

		if ($this->action == 'index') {
			$this->controller->set('weightingTypeOptions', $weightingTypeOptions);
		} else if ($this->action == 'view') {
			$this->fields['weighting_type']['dataModel'] = 'WeightingType';
			$this->fields['weighting_type']['dataField'] = 'name';

			$this->fields['security_roles']['dataModel'] = 'SecurityRole';
			$this->fields['security_roles']['dataField'] = 'name';

			$this->fields['education_grades']['dataModel'] = 'EducationGrade';
			$this->fields['education_grades']['dataField'] = 'programme_grade_name';
			$this->fields['education_grades']['dataSeparator'] = '<br>';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['weighting_type']['type'] = 'select';
			$this->fields['weighting_type']['options'] = $weightingTypeOptions;

			$securityRoleOptions = $this->SecurityRole->find('list');
			$this->fields['security_roles']['options'] = $securityRoleOptions;

			$educationGradeOptions = $this->EducationGrade->find('list', array(
				'fields' => array(
					'EducationGrade.id', 'EducationGrade.programme_grade_name'
				),
				'order' => array(
					'EducationGrade.programme_order', 'EducationGrade.order'
				)
			));
			$this->fields['education_grades']['options'] = $educationGradeOptions;

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;

				if ($data['submit'] == 'reload') {
					$this->ControllerAction->autoProcess = false;
				} else {
					if ($this->action == 'add') {
						$data['RubricTemplateOption'][0] = array(
							'name' => 'Good',
							'weighting' => 3,
							'color' => '#00ff00',
							'order' => 1
						);
						$data['RubricTemplateOption'][1] = array(
							'name' => 'Normal',
							'weighting' => 2,
							'color' => '#000ff0',
							'order' => 2
						);
						$data['RubricTemplateOption'][2] = array(
							'name' => 'Bad',
							'weighting' => 1,
							'color' => '#ff0000',
							'order' => 3
						);
					}

					$this->request->data = $data;
					$this->ControllerAction->autoProcess = true;
				}
			}
		}

		$this->controller->set('contentHeader', __('Templates'));
	}

	public function afterAction() {
		if ($this->action == 'view') {
			$data = $this->controller->viewVars['data'];
			$weightingTypeId = $data['RubricTemplate']['weighting_type'];
			$data['WeightingType'] = $this->weightingType[$weightingTypeId];
			$this->controller->set('data', $data);
		}
	}

	public function index() {
		$this->contain('SecurityRole', 'EducationGrade');
		$data = $this->find('all');
		$this->controller->set('data', $data);
	}
}
