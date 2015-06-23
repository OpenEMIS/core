<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;


class InstitutionSiteStudentsTable extends AppTable {
	private $academicPeriodId;
	private $educationProgrammeId;
	private $education_grade;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', 		 		['className' => 'User.Users', 					'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 	'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes','foreignKey' => 'education_programme_id']);
		$this->belongsTo('StudentStatuses',		['className' => 'FieldOption.StudentStatuses', 	'foreignKey' => 'student_status_id']);


		// $options['associated'] = [
		// 	'Users' => ['validate' => false],
		// 	'Institutions' => ['validate' => false],
		// 	'EducationProgrammes' => ['validate' => false],
		// 	'StudentStatuses' => ['validate' => false],
		// ];

				
		
		// 'Students.StudentStatus',
		// 'InstitutionSiteProgramme' => array(
		// 	'className' => 'InstitutionSiteProgramme',
		// 	'foreignKey' => false,
			// 'conditions' => array(
			// 	'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
			// 	'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
			// )
		// ),
		// 'EducationProgramme',
		// 'InstitutionSite'

		
		// $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}



	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('institution');
		$this->ControllerAction->field('academic_period');
		$this->ControllerAction->field('education_programme_id');
		$this->ControllerAction->field('education_grade');
		$this->ControllerAction->field('section');
		$this->ControllerAction->field('student_status_id');
		$this->ControllerAction->field('student_status_id');
		// $this->ControllerAction->field('start_date');
		// $this->ControllerAction->field('end_date');
		// $this->ControllerAction->field('search');

		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['security_user_id']['visible'] = false;

		$this->ControllerAction->setFieldOrder([
			'institution', 'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
			// , 'search'
			]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		die('asd');
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		$options['validate'] = false;
		return compact('entity', 'data', 'options');
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
		$attr['onChangeReload'] = 'true';

		return $attr;
	}

	public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');
		$this->academicPeriodId = null;
		if (array_key_exists('academic_period', $this->fields)) {
			if (array_key_exists('options', $this->fields['academic_period'])) {
				$this->academicPeriodId = key($this->fields['academic_period']['options']);
				if ($this->request->data($this->aliasField('academic_period'))) {
					$this->academicPeriodId = $this->request->data($this->aliasField('academic_period'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';
		if (isset($this->academicPeriodId)) {
			$InstitutionSiteProgrammes = TableRegistry::get('Institution.InstitutionSiteProgrammes');
			$attr['options'] = $InstitutionSiteProgrammes->getSiteProgrammeOptions($institutionsId, $this->academicPeriodId);
		}

		return $attr;
	}

	public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');

		if (array_key_exists('education_programme_id', $this->fields)) {
			if (array_key_exists('options', $this->fields['education_programme_id'])) {
				$this->educationProgrammeId = key($this->fields['education_programme_id']['options']);
				if ($this->request->data($this->aliasField('education_programme_id'))) {
					$this->educationProgrammeId = $this->request->data($this->aliasField('education_programme_id'));
				}
			}
		}
		$attr['type'] = 'select';
		$attr['onChangeReload'] = 'true';

		if (isset($this->educationProgrammeId)) {
			$InstitutionSiteGrades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$attr['options'] = $InstitutionSiteGrades->getGradeOptions($institutionsId, $this->academicPeriodId, $this->educationProgrammeId);
		}

		return $attr;
	}

	public function onUpdateFieldSection(Event $event, array $attr, $action, $request) {
		$institutionsId = $this->Session->read('Institutions.id');

		if (array_key_exists('education_grade', $this->fields)) {
			if (array_key_exists('options', $this->fields['education_grade'])) {
				$this->education_grade = key($this->fields['education_grade']['options']);
				if ($this->request->data($this->aliasField('education_grade'))) {
					$this->education_grade = $this->request->data($this->aliasField('education_grade'));
				}
			}
		}
		$attr['type'] = 'select';

		if (isset($this->education_grade)) {
			$InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
			$attr['options'] = $InstitutionSiteSections->getSectionOptions($this->academicPeriodId, $institutionsId, $this->education_grade);
		}

		return $attr;
	}

	public function onUpdateFieldStudentStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->StudentStatuses->getList();

		return $attr;
	}

	// public function onUpdateFieldSearch(Event $event, array $attr, $action, $request) {
	// 	$url = 'testurl';
	// 	$inputOptions = [
	// 		'class' => 'form-control autocomplete', 
	// 		'url' => $url
	// 	];

	// 	$attr['labelOptions'] = $inputOptions;
	// 	$attr['placeholder'] = 'OpenEMIS ID or Name';
	// 	// $attr['url'] = $this->params['controller'] . '/' . $model . '/autocomplete';
	// 	$attr['linkWhenNoRecords'] = '<span><a href="#" onclick="Autocomplete.submitForm(this);"> ' . __('Create') . ' ' . __('New') . '</a></span>';
	// 	// $attr['controller'] = $this->params['controller'];
	// 	$attr['linkWhenHasRecords'] = '<span><a href="#" onclick="Autocomplete.submitForm(this);"> ' . __('Create') . ' ' . __('New') . '</a></span>';
	// }


	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

}
