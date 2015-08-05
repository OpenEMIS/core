<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use App\Model\Table\AppTable;

class StudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.autocomplete');

		// $this->addBehavior('Student.Student');
		// $this->addBehavior('User.Mandatory', ['userRole' => 'Student', 'roleFields' =>['Identities', 'Nationalities', 'Contacts', 'SpecialNeeds']]);
		// $this->addBehavior('Institution.User', ['associatedModel' => $this->InstitutionSiteStudents]);
		// $this->addBehavior('AdvanceSearch');
	}

	public function beforeAction(Event $event) {
		$institutionId = $this->Session->read('Institutions.id');
		$this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
		$this->ControllerAction->field('student_status_id', ['type' => 'select']);
	}

	// public function onGetStudentId(Event $event, Entity $entity) {
	// 	pr($entity);
	// }

	public function addAfterAction(Event $event) {
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('education_grade_id');

		$selectedPeriod = $this->request->data($this->aliasField('academic_period_id'));
		$period = $this->AcademicPeriods->get($selectedPeriod);

		$this->ControllerAction->field('id', ['value' => Text::uuid()]);
		$this->ControllerAction->field('start_date', ['period' => $period]);
		$this->ControllerAction->field('end_date', ['period' => $period]);
		$this->ControllerAction->field('student_id');

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'education_grade_id', 'student_status_id', 'start_date', 'end_date', 'student_id'
		]);
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$periodOptions = $this->AcademicPeriods->getList();
		$institutionId = $this->Session->read('Institutions.id');
		
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');

		$selectedPeriod = 0;
		if ($this->request->is(['post', 'put'])) {
			$selectedPeriod = $this->request->data($this->aliasField('academic_period_id'));
		}
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
			'callable' => function($id) use ($Grades, $institutionId) {
				return $Grades
					->find()
					->where([$Grades->aliasField('institution_site_id') => $institutionId])
					->find('academicPeriod', ['academic_period_id' => $id])
					->count();
			}
		]);
		$this->request->data[$this->alias()]['academic_period_id'] = $selectedPeriod;

		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
		return $attr;
	}

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$selectedPeriod = $this->request->data($this->aliasField('academic_period_id'));
		$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
		$institutionId = $this->Session->read('Institutions.id');
		$data = $Grades->find()
		->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
		->contain('EducationGrades.EducationProgrammes')
		->where([$Grades->aliasField('institution_site_id') => $institutionId])
		->all();

		$gradeOptions = [];
		foreach ($data as $entity) {
			$gradeOptions[$entity->education_grade->id] = $entity->education_grade->programme_grade_name;
		}

		$attr['options'] = $gradeOptions;
		return $attr;
	}

	public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request) {
		$period = $attr['period'];
		$endDate = $period->end_date->copy();
		$attr['date_options']['startDate'] = $period->start_date->format('d-m-Y');
		$attr['date_options']['endDate'] = $endDate->subDay()->format('d-m-Y');
		return $attr;
	}

	public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request) {
		$period = $attr['period'];
		$attr['type'] = 'readonly';
		$attr['attr'] = ['value' => $period->end_date->format('d-m-Y')];
		$attr['value'] = $period->end_date->format('Y-m-d');
		return $attr;
	}

	public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request) {
		$attr['type'] = 'autocomplete';
		$attr['target'] = ['key' => 'student_id', 'name' => $this->aliasField('student_id')];
		$attr['noResults'] = $this->getMessage($this->aliasField('noStudents'));
		$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
		$attr['url'] = ['controller' => 'Institutions', 'action' => 'Students', 'ajaxUserAutocomplete'];
		return $attr;
	}

	public function ajaxUserAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			$data = $this->Users->autocomplete($term);
			echo json_encode($data);
			die;
		}
	}
}
