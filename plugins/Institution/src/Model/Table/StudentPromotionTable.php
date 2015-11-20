<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StudentPromotionTable extends AppTable {
	private $nextGrade = null;
	private $gradeOptions = [];
	private $nextPeriodGradeOptions = [];

	private $nextStatusId = null;	// promoted / graduated
	private $repeatStatusId = null;	// repeated
	private $currentStatusId = null;	// current
	private $statusOptions = [];
	private $statusMap = [];

	private $dataCount = null;

	private $Grades = null;
	private $institutionId = null;
	private $currentPeriod = null;
	private $statuses = [];	// Student Status

	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function beforeAction(Event $event) {
		$this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
		$this->institutionId = $this->Session->read('Institution.Institutions.id');
		$selectedPeriod = $this->AcademicPeriods->getCurrent();
    	$this->currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
    	$this->statuses = $this->StudentStatuses->findCodeList();
    }

    public function addAfterAction() {
    	$this->fields = [];
    	$this->ControllerAction->field('current_academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->currentPeriod->name], 'value' => $this->currentPeriod->id]);
    	$this->ControllerAction->field('next_academic_period_id');
    	$this->ControllerAction->field('grade_to_promote');
    	$this->ControllerAction->field('education_grade_id');
		$this->ControllerAction->field('students');

		$this->ControllerAction->setFieldOrder(['current_academic_period_id', 'grade_to_promote', 'education_grade_id', 'students']);
	}

	public function onUpdateFieldNextAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		$currentPeriod = $this->currentPeriod;
		$selectedPeriod = $currentPeriod->id;
		$startDate = $currentPeriod->start_date->format('Y-m-d');
		$where = [
			$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
			$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
			$this->AcademicPeriods->aliasField('start_date >=') => $startDate
		];
		$periodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();
		$attr['type'] = 'select';
		$attr['options'] = $periodOptions;
		$attr['onChangeReload'] = true;
		if (empty($request->data[$this->alias()]['next_academic_period_id'])) {
			$request->data[$this->alias()]['next_academic_period_id'] = key($periodOptions);
		}
		return $attr;
	}

	public function onUpdateFieldGradeToPromote(Event $event, array $attr, $action, Request $request) {
		$InstitutionTable = $this->Institutions;
		$InstitutionGradesTable = $this->InstitutionGrades;
		$selectedPeriod = $this->currentPeriod->id;
		$institutionId = $this->institutionId;
		$gradeOptions = $InstitutionGradesTable
			->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
			->contain(['EducationGrades'])
			->where([$InstitutionGradesTable->aliasField('institution_site_id') => $institutionId])
			->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
			->toArray();
		$attr['type'] = 'select';
		$selectedGrade = $request->query('grade_to_promote');
		$GradeStudents = $this;
		$this->advancedSelectOptions($gradeOptions, $selectedGrade, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
			'callable' => function($id) use ($GradeStudents, $institutionId, $selectedPeriod) {
				return $GradeStudents
					->find()
					->where([
						$GradeStudents->aliasField('institution_id') => $institutionId,
						$GradeStudents->aliasField('academic_period_id') => $selectedPeriod,
						$GradeStudents->aliasField('education_grade_id') => $id
					])
					->count();
			}
		]);
		$attr['onChangeReload'] = true;
		$attr['options'] = $gradeOptions;
		if (empty($request->data[$this->alias()]['grade_to_promote'])) {
			$request->data[$this->alias()]['grade_to_promote'] = $selectedGrade;
		}
		return $attr;
	}
	
	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
  //   	if (array_key_exists($this->alias(), $data)) {
		// 	$nextAcademicPeriodId = null;
		// 	$nextEducationGradeId = null;
		// 	$nextInstitutionId = null;
		// 	$studentTransferReasonId = null;

		// 	if (array_key_exists('next_academic_period_id', $data[$this->alias()])) {
		// 		$nextAcademicPeriodId = $data[$this->alias()]['next_academic_period_id'];
		// 	}
		// 	if (array_key_exists('next_education_grade_id', $data[$this->alias()])) {
		// 		$nextEducationGradeId = $data[$this->alias()]['next_education_grade_id'];
		// 	}
		// 	if (array_key_exists('next_institution_id', $data[$this->alias()])) {
		// 		$nextInstitutionId = $data[$this->alias()]['next_institution_id'];
		// 	}
		// 	if (array_key_exists('student_transfer_reason_id', $data[$this->alias()])) {
		// 		$studentTransferReasonId = $data[$this->alias()]['student_transfer_reason_id'];
		// 	}

		// 	if (!empty($nextAcademicPeriodId) && !empty($nextEducationGradeId) && !empty($nextInstitutionId) && !empty($studentTransferReasonId)) {
		// 		if (array_key_exists('students', $data[$this->alias()])) {
		// 			$TransferRequests = TableRegistry::get('Institution.TransferRequests');
		// 			$institutionId = $data[$this->alias()]['institution_id'];

		// 			$tranferCount = 0;
		// 			foreach ($data[$this->alias()]['students'] as $key => $studentObj) {
		// 				if ($studentObj['selected']) {
		// 					unset($studentObj['selected']);
		// 					$studentObj['academic_period_id'] = $nextAcademicPeriodId;
		// 					$studentObj['education_grade_id'] = $nextEducationGradeId;
		// 					$studentObj['institution_id'] = $nextInstitutionId;
		// 					$studentObj['student_transfer_reason_id'] = $studentTransferReasonId;
		// 					$studentObj['previous_institution_id'] = $institutionId;

		// 					$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);
		// 					$studentObj['start_date'] = $nextPeriod->start_date->format('Y-m-d');
		// 					$studentObj['end_date'] = $nextPeriod->end_date->format('Y-m-d');

		// 					$entity = $TransferRequests->newEntity($studentObj);
		// 					if ($TransferRequests->save($entity)) {
		// 						$tranferCount++;
		// 						$this->Alert->success($this->aliasField('success'));
		// 					} else {
		// 						$this->log($entity->errors(), 'debug');
		// 						$this->Alert->error('general.add.failed');
		// 					}
		// 				}
		// 			}

		// 			if ($tranferCount == 0) {
		// 				$this->Alert->error('general.notSelected');
		// 			}

		// 			$url = $this->ControllerAction->url('add');

		// 			$event->stopPropagation();
		// 			return $this->controller->redirect($url);
		// 		}
		// 	}			
		// }
    }

	public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request) {
		$educationGradeId = $request->data[$this->alias()]['grade_to_promote'];
		$institutionId = $this->institutionId;
		
		// list of grades available to promote to
		$listOfGrades = $this->EducationGrades->getNextAvailableEducationGrades($educationGradeId);

		// list of grades available in the institution
		$listOfInstitutionGrades = $this->InstitutionGrades
			->find('list', [
				'keyField' => 'education_grade_id', 
				'valueField' => 'education_grade.programme_grade_name'])
			->contain(['EducationGrades'])
			->where([$this->InstitutionGrades->aliasField('institution_site_id') => $institutionId])
			->toArray();

		// Only display the options that are available in the institution and also linked to the current programme
		$options = array_intersect_key($listOfInstitutionGrades, $listOfGrades);
		$attr['type'] = 'select';
		$attr['options'] = $options;
		return $attr;
	}

	public function onUpdateFieldStudents(Event $event, array $attr, $action, Request $request) {
    	$institutionId = $this->institutionId;
    	$selectedPeriod = $this->currentPeriod->id;
    	$selectedGrade = $request->data[$this->alias()]['grade_to_promote'];
    	$students = [];
    	if (!is_null($selectedGrade)) {
    		$studentStatuses = $this->statuses;
    		$students = $this->find()
	    		->matching('Users')
	    		->matching('EducationGrades')
	    		->where([
	    			$this->aliasField('institution_id') => $institutionId,
	    			$this->aliasField('academic_period_id') => $selectedPeriod,
	    			$this->aliasField('student_status_id') => $studentStatuses['CURRENT'],
	    			$this->aliasField('education_grade_id') => $selectedGrade
	    		])
	    		->toArray();
    	}
	  	if (empty($students)) {
	  		$this->Alert->warning($this->aliasField('noData'));
	  	}
    	$attr['type'] = 'element';
		$attr['element'] = 'Institution.StudentPromotion/students';
		$attr['data'] = $students;

		return $attr;
    }

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		return $entity->user->name;
	}

	public function onGetStudentStatusId(Event $event, Entity $entity) {
		$html = '';

		$selectedPeriod = $this->request->query('period');
		$selectedGrade = $this->request->query('grade');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$id = $entity->user->id;

		$alias = Inflector::underscore($this->alias());
		$fieldPrefix = $this->EducationGrades->alias() . '.'.$alias.'.' . $id;
		$Form = $event->subject()->Form;

		$html .= $Form->hidden($this->EducationGrades->alias().".academic_period_id", ['value' => $selectedPeriod]);
		$html .= $Form->hidden($this->EducationGrades->alias().".education_grade_id", ['value' => $selectedGrade]);
		$html .= $Form->hidden($this->EducationGrades->alias().".next_status_id", ['value' => $this->nextStatusId]);
		$html .= $Form->hidden($this->EducationGrades->alias().".repeat_status_id", ['value' => $this->repeatStatusId]);
		$html .= $Form->hidden($this->EducationGrades->alias().".current_status_id", ['value' => $this->currentStatusId]);

		$options = ['type' => 'select', 'label' => false, 'options' => $this->statusOptions, 'onChange' => '$(".grade_'.$id.'").hide();$("#grade_'.$id.'_"+$(this).val()).show();'];
		$html .= $Form->input($fieldPrefix.".student_status_id", $options);
		$html .= $Form->hidden($fieldPrefix.".student_id", ['value' => $id]);
		$html .= $Form->hidden($fieldPrefix.".institution_id", ['value' => $institutionId]);

		if (!is_null($this->request->query('mode'))) {
			return $html;
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add') {
			$toolbarButtons['back'] = $buttons['back'];
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['action'] = 'Students';
		}
	}
}
