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

class Staff extends StaffAppModel {
	public $useTable = 'staff';

	public $actsAs = array(
		'Excel',
		'Search',
		'UserAccess',
		'TrackActivity' => array('target' => 'Staff.StaffActivity', 'key' => 'staff_id', 'session' => 'Staff.id'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Staff.StaffAttachment',
				'Staff.StaffCustomValue'
			)
		),
		'CustomReport'
	);

	public $belongsTo = array('SecurityUser');

	public $hasMany = array(
		'Staff.StaffMembership',
		'Staff.StaffLicense',
		'Staff.StaffQualification',
		'Staff.StaffBehaviour',
		'Staff.StaffExtracurricular',
		'Staff.StaffBankAccount',
		'Staff.StaffHealth',
		'Staff.StaffHealthHistory',
		'Staff.StaffHealthFamily',
		'Staff.StaffHealthImmunization',
		'Staff.StaffHealthMedication',
		'Staff.StaffHealthAllergy',
		'Staff.StaffHealthTest',
		'Staff.StaffHealthConsultation',
		//'Staff.StaffActivity' => array('dependent' => true),
		'StaffActivity' => array('dependent' => true),
		'TrainingSessionTrainee' => array(
			'className' => 'TrainingSessionTrainee',
			'foreignKey' => 'staff_id',
			'dependent' => true
		),
		'TrainingSessionTrainer' => array(
			'className' => 'TrainingSessionTrainer',
			'foreignKey' => 'ref_trainer_id',
            'conditions' => array('ref_trainer_table' => 'Staff'),
			'dependent' => true
		),
		'StaffTrainingNeed' => array(
			'className' => 'StaffTrainingNeed',
			'foreignKey' => 'staff_id',
			'dependent' => true
		),
		'StaffTrainingSelfStudy' => array(
			'className' => 'StaffTrainingSelfStudy',
			'foreignKey' => 'staff_id',
			'dependent' => true
		),
		'InstitutionSiteStaff',
		'Staff.StaffCustomValue'
	);

	public $validate = array(
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		$conditions = array();

		if (CakeSession::check('Staff.id')) {
			$id = CakeSession::read('Staff.id');
			$conditions = array('Staff.id' => $id);
		}
		return $conditions;
	}
	public function excelGetModels() {
		$models = parent::excelGetModels();
		if (CakeSession::check('Staff.id')) {
			$models = array(
				array('model' => $this,
					'include' => array(
						'header' => 'StaffCustomField',
						'data' => 'StaffCustomValue',
						'dataOptions' => 'StaffCustomFieldOption',
						'plugin' => 'Staff'
					)
				),
				array('model' => $this->StaffContact, 'name' => 'Contacts'),
				//array('model' => $this->StaffIdentity, 'name' => 'Identities'), -- not working due to unknown reasons
				array('model' => $this->StaffNationality, 'name' => 'Nationalities'),
				array('model' => $this->StaffLanguage, 'name' => 'Languages'),
				array('model' => $this->StaffComment, 'name' => 'Comments'),
				array('model' => $this->StaffSpecialNeed, 'name' => 'Special Needs'),
				array('model' => $this->StaffAward, 'name' => 'Awards'),
				array('model' => $this->StaffMembership, 'name' => 'Membership'),
				array('model' => $this->StaffLicense, 'name' => 'Licenses'),
				array('model' => $this->StaffQualification, 'name' => 'Qualifications'),
				array('model' => $this->StaffBehaviour, 'name' => 'Behaviour'),
				array('model' => $this->StaffExtracurricular, 'name' => 'Extracurricular'),
				array('model' => $this->StaffBankAccount, 'name' => 'Bank Accounts'),
				array('model' => $this->StaffHealth, 'name' => 'Health Overview'),
				array('model' => $this->StaffHealthHistory, 'name' => 'Health History'),
				array('model' => $this->StaffHealthFamily, 'name' => 'Health Family'),
				array('model' => $this->StaffHealthImmunization, 'name' => 'Immunizations'),
				array('model' => $this->StaffHealthMedication, 'name' => 'Medications'),
				array('model' => $this->StaffHealthAllergy, 'name' => 'Allergies'),
				array('model' => $this->StaffHealthTest, 'name' => 'Health Tests'),
				array('model' => $this->StaffHealthConsultation, 'name' => 'Health Consulations')
			);
		}
		return $models;
	}
	/* End Excel Behaviour */
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		return $this->getPaginate($conditions, $fields, $order, $limit, $page, $recursive, $extra);
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->getPaginateCount($conditions, $recursive, $extra);
	}
	
	// used by InstitutionSiteStaff
	public function autocomplete($search) {
		$search = '%' . $search . '%';
		
		$conditions = array(
			'OR' => array(
				'openemis_no LIKE' => $search,
				'SecurityUser.first_name LIKE' => $search,
				'SecurityUser.middle_name LIKE' => $search,
				'SecurityUser.third_name LIKE' => $search,
				'SecurityUser.last_name LIKE' => $search
			)
		);
		$options = array(
			'recursive' => -1,
			'joins' => array(
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityUser.id = Staff.security_user_id')
				)
			),
			'conditions' => $conditions,
			'order' => array('SecurityUser.first_name')
		);
		
		$options['fields'] = array('id', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.openemis_no', 'SecurityUser.date_of_birth');
		$data = $this->find('all', $options);
		
		return $data;
	}
}
?>
