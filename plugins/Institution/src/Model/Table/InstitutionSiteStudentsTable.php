<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', 		 		['className' => 'User.Users', 					'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 	'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes','foreignKey' => 'education_programme_id']);
		$this->belongsTo('StudentStatuses',		['className' => 'FieldOption.StudentStatuses', 	'foreignKey' => 'student_status_id']);
		
		// 'Students.StudentStatus',
		// 'InstitutionSiteProgramme' => array(
		// 	'className' => 'InstitutionSiteProgramme',
		// 	'foreignKey' => false,
		// 	'conditions' => array(
		// 		'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
		// 		'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
		// 	)
		// ),
		// 'EducationProgramme',
		// 'InstitutionSite'

		
		// $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}



	public function addBeforeAction(Event $event) {
		// pr();
		// foreach ($this->fields as $key => $value) {
		// 	var_dump($key.'<br>');
		// }
		
		$this->ControllerAction->field('institution');
		$this->ControllerAction->field('academic_period');
		// education_programme_id
		$this->ControllerAction->field('education_grade');
		$this->ControllerAction->field('section');
		// student_status_id // category
		// start_date
		// end_date
		$this->ControllerAction->field('search');

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['security_user_id']['visible'] = false;

		$this->ControllerAction->setFieldOrder([
			'institution', 'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date', 'search'
			]);
	}

	public function onUpdateFieldInstitution(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'readonly';

		$institutionsId = $this->Session->read('Institutions.id');
		$result = $this->Institutions
			->find()
			->where([$this->Institutions->primaryKey()=>$institutionsId])
			->first()
		;
		$result = $result->toArray();
		if (!empty($result)) {
			$attr['attr']['value'] = $result['name'];
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriod(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');
		$conditions = array(
			'InstitutionSiteProgrammes.institution_site_id' => $institutionsId
		);

		$InstitutionSiteProgramme = TableRegistry::get('Institution.InstitutionSiteProgrammes');
		$list = $InstitutionSiteProgramme->getAcademicPeriodOptions($conditions);

		$attr['type'] = 'select';
		$attr['options'] = $list;
		$attr['onChangeReload'] = 'changePeriod';

		return $attr;
	}

	public function addEditOnChangePeriod(Event $event, Entity $entity, array $data, array $options) {
		// pr('lala');
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
	}

	public function onUpdateFieldSearch(Event $event, array $attr, $action, $request) {
	}


	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

}
