<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class InstitutionGradeStudentsTable extends AppTable {
	private $nextPeriod = null;
	private $nextGrade = null;

	private $nextStatusId = null;	// promoted / graduated
	private $repeatStatusId = null;	// repeated
	private $currentStatusId = null;	// current
	private $statusOptions = [];
	private $statusMap = [];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey'=>'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onGetOpenemisNo(Event $event, Entity $entity) {
		return $entity->user->openemis_no;
	}

	public function onGetSecurityUserId(Event $event, Entity $entity) {
		return $entity->user->name;
	}

	public function onGetStudentStatusId(Event $event, Entity $entity) {
		if (!is_null($this->nextPeriod)) {
			$html = '';

			$selectedPeriod = $this->request->query('period');
			$selectedGrade = $this->request->query('grade');
			$institutionId = $this->Session->read('Institutions.id');
			$periodId = $this->nextPeriod->id;
			$startDate = date('Y-m-d', strtotime($this->nextPeriod->start_date));
			$endDate = date('Y-m-d', strtotime($this->nextPeriod->end_date));
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
			$html .= $Form->hidden($fieldPrefix.".security_user_id", ['value' => $id]);
			$html .= $Form->hidden($fieldPrefix.".institution_id", ['value' => $institutionId]);
			$html .= $Form->hidden($fieldPrefix.".academic_period_id", ['value' => $periodId]);
			$html .= $Form->hidden($fieldPrefix.".start_date", ['value' => $startDate]);
			$html .= $Form->hidden($fieldPrefix.".end_date", ['value' => $endDate]);

			if (!is_null($this->nextGrade)) {
				$gradeId = $this->nextGrade->id;
				$html .= $Form->hidden($fieldPrefix.".education_grade_id", ['value' => $gradeId]);
			}

			if (!is_null($this->request->query('mode'))) {
				return $html;
			}
		}
	}

	public function onGetEducationGradeId(Event $event, Entity $entity) {
		$html = '';

		$id = $entity->user->id;
		$selectedGrade = $this->request->query('grade');
		$currentGrade = $this->EducationGrades->get($selectedGrade);

		$html .= '<span class="grade_'.$id.'" id="grade_'.$id.'_'.$this->repeatStatusId.'" style="display: none">';
			$html .= $currentGrade->programme_grade_name;
		$html .= '</span>';

		if (!is_null($this->nextGrade)) {
			$html .= '<span class="grade_'.$id.'" id="grade_'.$id.'_'.$this->nextStatusId.'">';
				$html .= $this->nextGrade->programme_grade_name;
			$html .= '</span>';
		}

		return $html;
	}

	public function beforeAction(Event $event) {
		$statuses = $this->StudentStatuses->find()->all();

		foreach ($statuses as $entity) {
			$this->statusOptions[$entity->id] = $entity->name;
			$this->statusMap[$entity->code] = $entity->id;
		}

		$promotedChoices = ['PROMOTED', 'REPEATED'];
		$promotedOptions = [];

		foreach ($promotedChoices as $code) {
			$promotedOptions[$this->statusMap[$code]] = $this->statusOptions[$this->statusMap[$code]];
		}
		$promotedOptions = [
			$this->statusMap['PROMOTED'] => $this->statusOptions[$this->statusMap['PROMOTED']],
			$this->statusMap['REPEATED'] => $this->statusOptions[$this->statusMap['REPEATED']]
		];
		// pr($promotedOptions);

		$graduatedOptions = [
			$this->statusMap['GRADUATED'] => $this->statusOptions[$this->statusMap['GRADUATED']],
			$this->statusMap['REPEATED'] => $this->statusOptions[$this->statusMap['REPEATED']]
		];
		// pr($graduatedOptions);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('institution_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'security_user_id', 'student_status_id', 'education_grade_id']);

		$settings['pagination'] = false;
		if ($this->Session->check('Institutions.id')) {
			//Add controls filter to index page
	        $toolbarElements = [
				['name' => 'Institution.StudentGrades/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
			// End

			$institutionId = $this->Session->read('Institutions.id');
			$Grades = TableRegistry::get('Institution.InstitutionSiteGrades');
			$GradeStudents = TableRegistry::get('Institution.InstitutionGradeStudents');

			// Academic Periods
			$periodOptions = $this->AcademicPeriods->getYearList();
			$selectedPeriod = $this->queryString('period', $periodOptions);
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
			$this->request->query['period'] = $selectedPeriod;
			// End

			// Grades
			$gradeOptions = $Grades
				->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade.programme_grade_name'])
				->contain(['EducationGrades'])
				->where([$Grades->aliasField('institution_site_id') => $institutionId])
				->find('academicPeriod', ['academic_period_id' => $selectedPeriod])
				->toArray();
			$selectedGrade = $this->queryString('grade', $gradeOptions);
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
			$this->request->query['grade'] = $selectedGrade;
			// End

			$this->controller->set(compact('periodOptions', 'gradeOptions'));

			// Next Period
			$currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
			$nextPeriodOrder = $currentPeriod->order;
			$nextPeriodOrder--;

			$this->nextPeriod = $this->AcademicPeriods
				->find()
				->where([
					$this->AcademicPeriods->aliasField('academic_period_level_id') => $currentPeriod->academic_period_level_id,
					$this->AcademicPeriods->aliasField('order') => $nextPeriodOrder
				])
				->first();
			// End

			// Next Grade
			if ($selectedGrade != 0) {
				$currentGrade = $this->EducationGrades->get($selectedGrade);
				$nextGradeOrder = $currentGrade->order;
				$nextGradeOrder++;

				$this->nextGrade = $this->EducationGrades
					->find()
					->where([
						$this->EducationGrades->aliasField('education_programme_id') => $currentGrade->education_programme_id,
						$this->EducationGrades->aliasField('order') => $nextGradeOrder
					])
					->first();

				if (!is_null($this->nextGrade)) {
					$statusList = ['PROMOTED', 'REPEATED'];

					$this->nextStatusId = $this->StudentStatuses
						->find()
						->where([$this->StudentStatuses->aliasField('code') => 'PROMOTED'])
						->first()
						->id;
				} else {
					$statusList = ['GRADUATED', 'REPEATED'];

					$this->nextStatusId = $this->StudentStatuses
						->find()
						->where([$this->StudentStatuses->aliasField('code') => 'GRADUATED'])
						->first()
						->id;
				}

				$this->repeatStatusId = $this->StudentStatuses
					->find()
					->where([$this->StudentStatuses->aliasField('code') => 'REPEATED'])
					->first()
					->id;

				$this->currentStatusId = $this->StudentStatuses
					->find()
					->where([$this->StudentStatuses->aliasField('code') => 'CURRENT'])
					->first()
					->id;

				$this->statusOptions = $this->StudentStatuses
					->find('list')
					->where([$this->StudentStatuses->aliasField('code IN') => $statusList])
					->toArray();
			}
			// End

			$query
				->contain(['StudentStatuses', 'Users'])
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('academic_period_id') => $selectedPeriod,
					$this->aliasField('education_grade_id') => $selectedGrade
				]);

			if (!is_null($this->request->query('mode'))) {
				$query->where([
					$this->aliasField('student_status_id') => $this->currentStatusId
				]);
			}

			return $query;
		} else {
			return $query
				->where([$this->aliasField('institution_id') => 0]);
		}
	}

	public function indexAfterAction(Event $event, $data) {
		// if ($data->count() == 0) {
		// 	// There are no available Students for Promotion/Graduation.
		// 	$this->Alert->info('InstitutionGradeStudents.noData');
		// 	$event->stopPropagation();
		// 	$action = ['action' => 'Grades', 'index'];
		// 	$params = $this->ControllerAction->paramsQuery();
		// 	unset($params['mode']);
		// 	$action = array_merge($action, $params);
		// 	return $this->controller->redirect($action);
		// }
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if (!is_null($this->request->query('mode'))) {
			$config['formButtons'] = true;
			$config['url'] = $config['buttons']['index']['url'];
			$config['url'][0] = 'indexEdit';
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if (is_null($this->request->query('mode'))) {
			$toolbarButtons['edit'] = $buttons['index'];
	    	$toolbarButtons['edit']['url'][0] = 'index';
			$toolbarButtons['edit']['url']['mode'] = 'edit';
			$toolbarButtons['edit']['type'] = 'button';
			$toolbarButtons['edit']['label'] = '<i class="fa kd-edit"></i>';
			$toolbarButtons['edit']['attr'] = $attr;
			$toolbarButtons['edit']['attr']['title'] = __('Edit');

			$toolbarButtons['back'] = $buttons['back'];
			$toolbarButtons['back']['type'] = null;
		} else {
			$toolbarButtons['back'] = $buttons['back'];
			if ($toolbarButtons['back']['url']['mode']) {
				unset($toolbarButtons['back']['url']['mode']);
			}
			$toolbarButtons['back']['type'] = 'button';
			$toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
			$toolbarButtons['back']['attr'] = $attr;
			$toolbarButtons['back']['attr']['title'] = __('Back');
		}
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestData = $this->request->data;

			if (array_key_exists($this->EducationGrades->alias(), $requestData)) {
				if (array_key_exists('institution_grade_students', $requestData[$this->EducationGrades->alias()])) {
					$academicPeriodId = $requestData[$this->EducationGrades->alias()]['academic_period_id'];
					$educationGradeId = $requestData[$this->EducationGrades->alias()]['education_grade_id'];
					$nextStatusId = $requestData[$this->EducationGrades->alias()]['next_status_id'];
					$repeatStatusId = $requestData[$this->EducationGrades->alias()]['repeat_status_id'];
					$currentStatusId = $requestData[$this->EducationGrades->alias()]['current_status_id'];

					foreach ($requestData[$this->EducationGrades->alias()]['institution_grade_students'] as $key => $obj) {
						if ($obj['student_status_id'] == $repeatStatusId) {
							$status = $repeatStatusId;
							$obj['education_grade_id'] = $educationGradeId;
						} else {
							$status = $nextStatusId;
						}
						$obj['student_status_id'] = $currentStatusId;

						$this->updateAll(['student_status_id' => $status], [
							'security_user_id' => $obj['security_user_id'],
							'institution_id' => $obj['institution_id'],
							'academic_period_id' => $academicPeriodId,
							'education_grade_id' => $educationGradeId
						]);

						if (isset($obj['education_grade_id'])) {
							$entity = $this->newEntity($obj);

							if ($this->save($entity)) {
							} else {
								$this->log($entity->errors(), 'debug');
							}
						}
					}
					$this->Alert->success('general.add.success');
				} else {
					$this->Alert->error('general.add.failed');
				}
			} else {
				$this->Alert->error('general.add.failed');
			}

			$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => $this->alias];
			$url = array_merge($url, $this->request->query, $this->request->pass);
			$url[0] = 'index';
			unset($url['mode']);

			return $this->controller->redirect($url);
		}
	}
}
