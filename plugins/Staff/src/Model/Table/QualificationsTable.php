<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class QualificationsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		$this->belongsTo('QualificationInstitutions', ['className' => 'FieldOption.QualificationInstitutions']);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);
	}

	public function beforeAction() {
		$this->fields['qualification_level_id']['type'] = 'select';
		$this->fields['qualification_specialisation_id']['type'] = 'select';
		$this->fields['qualification_institution_id']['type'] = 'select';
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['qualification_specialisation_id']['visible'] = false;
		$this->fields['qualification_institution_country']['visible'] = false;
		$this->fields['file_name']['visible'] = false;
		$this->fields['file_content']['visible'] = false;
		$this->fields['gpa']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('graduate_year', $order++);
		$this->ControllerAction->setFieldOrder('qualification_level_id', $order++);
		$this->ControllerAction->setFieldOrder('qualification_title', $order++);
		$this->ControllerAction->setFieldOrder('document_no', $order++);
		$this->ControllerAction->setFieldOrder('qualification_institution_id', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['file_name']['visible'] = false;
		$this->fields['graduate_year']['type'] = 'string';

		$order = 0;
		$this->ControllerAction->setFieldOrder('qualification_level_id', $order++);
		// todo:mlee - need to handle auto complete
		$this->ControllerAction->setFieldOrder('qualification_institution_id', $order++);
		$this->ControllerAction->setFieldOrder('qualification_institution_country', $order++);
		$this->ControllerAction->setFieldOrder('qualification_title', $order++);
		$this->ControllerAction->setFieldOrder('qualification_specialisation_id', $order++);
		$this->ControllerAction->setFieldOrder('graduate_year', $order++);
		$this->ControllerAction->setFieldOrder('document_no', $order++);
		$this->ControllerAction->setFieldOrder('gpa', $order++);
		$this->ControllerAction->setFieldOrder('file_name', $order++);
		$this->ControllerAction->setFieldOrder('file_content', $order++);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('graduate_year', 'ruleNumeric', 
				['rule' => 'numeric']
			)
		;
	}

	public function getNumberOfStaffByQualification($params=[]){
		$$QualificationRecord = $this->find();
		$QualificationCount = $QualificationRecord
			->contain(['Users', 'QualificationLevels', 'Users.InstitutionSiteStaff'])
			// ->select([
			// 'qualification' => 'QualificationLevels.name',
			// 	'count' => $QualificationRecord->func()->count('security_user_id')
			// ])
			//->where(['InstitutionSiteStaff.institution_site_id' => 1])
			->toArray();

		pr($qualificationCount);
		return $params;
	}

}