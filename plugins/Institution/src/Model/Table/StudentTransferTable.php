<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StudentTransferTable extends AppTable {
	// Status of Transfer Request
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for admission
	const TRANSFER = 2;
	const ADMISSION = 1;

	private $dataCount = null;

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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
		if ($field == 'select_all') {
			$html = '';
			$Form = $event->subject()->Form;

			$alias = $this->alias() . '.select_all';
			$html .= $Form->checkbox($alias, ['class' => 'icheck-input']);

			return $html;
			return __('Programme') . '<span class="divider"></span>' . __('Grade');
		} else {
			return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
		}
	}

    public function onGetSelectAll(Event $event, Entity $entity) {
    	$html = '';

    	$id = $entity->student_id;
		$fieldPrefix = $this->alias() . '.students.' . $id;
		$Form = $event->subject()->Form;
		$html .= $Form->checkbox("$fieldPrefix.selected", ['class' => 'icheck-input', 'checked' => false]);

		return $html;
	}

    public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetStudentId(Event $event, Entity $entity) {
		$html = '';

    	$id = $entity->student_id;
		$fieldPrefix = $this->alias() . '.students.' . $id;
		$Form = $event->subject()->Form;

		$startDate = $entity->start_date->format('Y-m-d');
    	$endDate = $entity->end_date->format('Y-m-d');

		$html .= $entity->user->name;
		$html .= $Form->hidden("$fieldPrefix.student_id", ['value' => $id]);
		$html .= $Form->hidden("$fieldPrefix.start_date", ['value' => $startDate]);
		$html .= $Form->hidden("$fieldPrefix.end_date", ['value' => $endDate]);
		$html .= $Form->hidden("$fieldPrefix.status", ['value' => self::NEW_REQUEST]);
		$html .= $Form->hidden("$fieldPrefix.type", ['value' => self::TRANSFER]);

		return $html;
	}

    public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
    	$this->ControllerAction->field('select_all');
    	$this->ControllerAction->field('openemis_no');
    	$this->ControllerAction->field('student_status_id', ['visible' => false]);
    	$this->ControllerAction->field('education_grade_id', ['visible' => false]);
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('institution_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['select_all', 'openemis_no', 'student_id']);

		$settings['pagination'] = false;
		if ($this->Session->check('Institution.Institutions.id')) {
			//Add controls filter to index page
	        $toolbarElements = [
				['name' => 'Institution.StudentTransfer/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
			// End

			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$GradeStudents = TableRegistry::get('Institution.StudentPromotion');

			// Academic Periods
			$periodOptions = $this->AcademicPeriods->getList();
			$selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
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
			$this->request->query['academic_period_id'] = $selectedPeriod;
			// End

			// Grades
			$this->gradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_site_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->toArray();
			$selectedGrade = $this->queryString('education_grade_id', $this->gradeOptions);
			$gradeOptions = $this->gradeOptions;
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
			$this->request->query['education_grade_id'] = $selectedGrade;
			// End

			$this->controller->set(compact('periodOptions', 'gradeOptions'));

			$query
				->contain(['Users'])
				->innerJoin(
					[$this->StudentStatuses->alias() => $this->StudentStatuses->table()],
					[
						$this->StudentStatuses->aliasField('id = ') . $this->aliasField('student_status_id'),
						$this->StudentStatuses->aliasField('code IN') => ['GRADUATED', 'PROMOTED']
					]
				)
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $selectedPeriod,
					$this->aliasField('education_grade_id') => $selectedGrade
				]);

			return $query;
		} else {
			return $query
				->where([$this->aliasField('institution_id') => 0]);
		}
    }

    public function indexAfterAction(Event $event, $data) {
		$this->dataCount = $data->count();
	}

    public function afterAction(Event $event, ArrayObject $config) {
    	if (!is_null($this->request->query('mode'))) {
			$indexElements = $this->controller->viewVars['indexElements'];

			$institutionId = $this->Session->read('Institution.Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$GradeStudents = TableRegistry::get('Institution.StudentPromotion');

			// Next Academic Period
			$selectedPeriod = $this->request->query('academic_period_id');
			$currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
			$startDate = $currentPeriod->start_date->format('Y-m-d');

			$where = [
				$this->AcademicPeriods->aliasField('id <>') => $selectedPeriod,
				$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
				$this->AcademicPeriods->aliasField('start_date >=') => $startDate
			];

			$nextPeriodOptions = $this->AcademicPeriods
				->find('list')
				->find('visible')
				->find('order')
				->where($where)
				->toArray();

			$nextPeriodId = $this->queryString('next_academic_period_id', $nextPeriodOptions);
			$this->advancedSelectOptions($nextPeriodOptions, $nextPeriodId, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noGrades')),
				'callable' => function($id) use ($Grades, $institutionId) {
					return $Grades
						->find()
						->where([$Grades->aliasField('institution_site_id') => $institutionId])
						->find('academicPeriod', ['academic_period_id' => $id])
						->count();
				}
			]);
			// End

			// Next Education Grade
			$nextGradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_site_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $nextPeriodId])
				->toArray();
			$nextGradeId = $this->queryString('next_education_grade_id', $nextGradeOptions);
			$this->advancedSelectOptions($nextGradeOptions, $nextGradeId, [
				'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
				'callable' => function($id) use ($GradeStudents, $institutionId, $nextPeriodId) {
					return $GradeStudents
						->find()
						->where([
							$GradeStudents->aliasField('institution_id') => $institutionId,
							$GradeStudents->aliasField('academic_period_id') => $nextPeriodId,
							$GradeStudents->aliasField('education_grade_id') => $id
						])
						->count();
				}
			]);
			// End

			// Next Institution
			$nextPeriodData = $this->AcademicPeriods->get($nextPeriodId);
			if ($nextPeriodData->start_date instanceof Time) {
				$nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
			} else {
				$nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
			}
			$institutionOptions = $this->Institutions
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->join([
					'table' => $Grades->table(),
					'alias' => $Grades->alias(),
					'conditions' => [
						$Grades->aliasField('institution_site_id =') . $this->Institutions->aliasField('id'),
						$Grades->aliasField('education_grade_id') => $nextGradeId,
						$Grades->aliasField('start_date').' <=' => $nextPeriodStartDate,
						'OR' => [
							$Grades->aliasField('end_date').' IS NULL',
							$Grades->aliasField('end_date').' >=' => $nextPeriodStartDate
						]
					]
				])
				->where([$this->Institutions->aliasField('id <>') => $institutionId])
				->toArray();
			// End

			// Student Transfer Reason
			$StudentTransferReasons = TableRegistry::get('FieldOption.StudentTransferReasons');
			$reasonOptions = $StudentTransferReasons->getList();
			// End

			$indexElements[] = [
				'name' => 'Institution.StudentTransfer/filters',
				'data' => [
					'alias' => $this->alias(),
					'nextPeriodOptions' => $nextPeriodOptions,
					'nextGradeOptions' => $nextGradeOptions,
					'institutionOptions' => $institutionOptions,
					'reasonOptions' => $reasonOptions
				],
				'options' => [],
				'order' => 1
			];

			$this->controller->set(compact('indexElements'));

			if ($this->dataCount > 0) {
				$config['formButtons'] = true;
				$config['url'] = $config['buttons']['index']['url'];
				$config['url'][0] = 'indexEdit';
			} else {
				$this->Alert->info('StudentTransfer.noData');
			}
    	}
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if (!is_null($this->request->query('mode'))) {
			$toolbarButtons['back'] = $buttons['back'];
			if ($toolbarButtons['back']['url']['mode']) {
				unset($toolbarButtons['back']['url']['mode']);
			}
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
			$toolbarButtons['back']['url']['action'] = 'Students';
		}
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestData = $this->request->data;

			$TransferRequests = TableRegistry::get('Institution.TransferRequests');
			$institutionId = $this->Session->read('Institution.Institutions.id');

			$nextAcademicPeriodId = null;
			$nextEducationGradeId = null;
			$nextInstitutionId = null;
			$studentTransferReasonId = null;

			if (array_key_exists($this->alias(), $requestData)) {
				if (array_key_exists('next_academic_period_id', $requestData[$this->alias()])) {
					$nextAcademicPeriodId = $requestData[$this->alias()]['next_academic_period_id'];
					$this->request->query['next_academic_period_id'] = $nextAcademicPeriodId;
				}
				if (array_key_exists('next_education_grade_id', $requestData[$this->alias()])) {
					$nextEducationGradeId = $requestData[$this->alias()]['next_education_grade_id'];
					$this->request->query['next_education_grade_id'] = $nextEducationGradeId;
				}
				if (array_key_exists('next_institution_id', $requestData[$this->alias()])) {
					$nextInstitutionId = $requestData[$this->alias()]['next_institution_id'];
				}
				if (array_key_exists('student_transfer_reason_id', $requestData[$this->alias()])) {
					$studentTransferReasonId = $requestData[$this->alias()]['student_transfer_reason_id'];
				}
			}
			$submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';

			if ($submit == 'save') {
				if (array_key_exists($this->alias(), $requestData)) {
					if (array_key_exists('students', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['students'] as $key => $studentObj) {
							if ($studentObj['selected']) {
								unset($studentObj['selected']);
								$studentObj['academic_period_id'] = $nextAcademicPeriodId;
								$studentObj['education_grade_id'] = $nextEducationGradeId;
								$studentObj['institution_id'] = $nextInstitutionId;
								$studentObj['student_transfer_reason_id'] = $studentTransferReasonId;
								$studentObj['previous_institution_id'] = $institutionId;

								$entity = $TransferRequests->newEntity($studentObj);
								if ($TransferRequests->save($entity)) {
								} else {
									$this->log($entity->errors(), 'debug');
								}
							}
						}
					}
				}

				$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'Students'];
				$url = array_merge($url, $this->request->query, $this->request->pass);
				$url[0] = 'index';
				unset($url['mode']);
			} else {
				// Reload
				if (array_key_exists($this->alias(), $requestData)) {
					if (array_key_exists('next_academic_period_id', $requestData[$this->alias()])) {
						$this->request->query['next_academic_period_id'] = $requestData[$this->alias()]['next_academic_period_id'];
					}
					if (array_key_exists('next_education_grade_id', $requestData[$this->alias()])) {
						$this->request->query['next_education_grade_id'] = $requestData[$this->alias()]['next_education_grade_id'];
					}
				}

				$url = $this->ControllerAction->url('index');
			}

			return $this->controller->redirect($url);
		}
	}
}
