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
		$this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('HighChart', [
        	'number_of_students_by_year' => [
        		'_function' => 'getNumberOfStudentsByYear',
				'chart' => ['type' => 'column', 'borderWidth' => 1],
				'xAxis' => ['title' => ['text' => 'Years']],
				'yAxis' => ['title' => ['text' => 'Total']]
			]
		]);

	}



	// public function addBeforeAction(Event $event) {
	// 	$this->ControllerAction->field('institution');
	// 	$this->ControllerAction->field('academic_period');
	// 	$this->ControllerAction->field('education_programme_id');
	// 	$this->ControllerAction->field('education_grade');
	// 	$this->ControllerAction->field('section');
	// 	$this->ControllerAction->field('student_status_id');
	// 	$this->ControllerAction->field('student_status_id');
	// 	$this->ControllerAction->field('start_date');
	// 	$this->ControllerAction->field('end_date');
	// 	$this->ControllerAction->field('security_user_id');
	// 	// $this->ControllerAction->field('search');

	// 	$this->fields['start_year']['visible'] = false;
	// 	$this->fields['end_year']['visible'] = false;
	// 	// initializing to bypass validation - will be modified later when appropriate
	// 	$this->fields['security_user_id']['type'] = 'hidden';
	// 	$this->fields['security_user_id']['value'] = 0;

	// 	$this->ControllerAction->setFieldOrder([
	// 		'institution', 'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
	// 		// , 'search'
	// 		]);
	// }

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'add'.'?new='.$timeNow]);
		}
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period', [
			])
			->add('education_programme_id',[
			])
			->add('education_grade',[
			])
		;
	}

	public function getNumberOfStudentsByYear($params=[]) {
		$conditions = isset($params['conditions']) ? $params['conditions'] : [];
		$_conditions = [];
		foreach ($conditions as $key => $value) {
			$_conditions['InstitutionSiteStudents.'.$key] = $value;
		}

		$periodConditions = $_conditions;
		$query = $this->find();
		$periodResult = $query
			->select([
				'min_year' => $query->func()->min('InstitutionSiteStudents.start_year'),
				'max_year' => $query->func()->max('InstitutionSiteStudents.end_year')
			])
			->where($periodConditions)
			->first();
		$AcademicPeriod = $this->Institutions->InstitutionSiteProgrammes->AcademicPeriods;
		$currentPeriodId = $AcademicPeriod->getCurrent();
		$currentPeriodObj = $AcademicPeriod->get($currentPeriodId);
		$thisYear = $currentPeriodObj->end_year;
		$minYear = $thisYear - 2;
		$minYear = $minYear > $periodResult->min_year ? $minYear : $periodResult->min_year;
		$maxYear = $thisYear;

		$years = [];

		$genderOptions = $this->Users->Genders->getList();
		$dataSet = [];
		foreach ($genderOptions as $key => $value) {
			$dataSet[$value] = ['name' => __($value), 'data' => []];
		}

		$studentsByYearConditions = array('Genders.name IS NOT NULL');
		$studentsByYearConditions = array_merge($studentsByYearConditions, $_conditions);

		for ($currentYear = $minYear; $currentYear <= $maxYear; $currentYear++) {
			$years[$currentYear] = $currentYear;
			$studentsByYearConditions['OR'] = [
				[
					'InstitutionSiteStudents.end_year IS NOT NULL',
					'InstitutionSiteStudents.start_year <= "' . $currentYear . '"',
					'InstitutionSiteStudents.end_year >= "' . $currentYear . '"'
				]
			];

			$query = $this->find();
			$studentsByYear = $query
				->contain(['Users.Genders'])
				->select([
					'Users.first_name',
					'Genders.name',
					'total' => $query->func()->count('InstitutionSiteStudents.id')
				])
				->where($studentsByYearConditions)
				->group('Genders.name')
				->toArray()
				;
 			foreach ($dataSet as $key => $value) {
 				if (!array_key_exists($currentYear, $dataSet[$key]['data'])) {
 					$dataSet[$key]['data'][$currentYear] = 0;
 				}				
			}

			foreach ($studentsByYear as $key => $studentByYear) {
				$studentGender = isset($studentByYear->user->gender->name) ? $studentByYear->user->gender->name : null;
				$studentTotal = isset($studentByYear->total) ? $studentByYear->total : 0;
				$dataSet[$studentGender]['data'][$currentYear] = $studentTotal;
			}
		}

		$params['dataSet'] = $dataSet;
		
		return $params;
	}

}
