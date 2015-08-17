<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class StudentPromotionTable extends AppTable {
	private $nextGrade = null;

	private $nextStatusId = null;	// promoted / graduated
	private $repeatStatusId = null;	// repeated
	private $currentStatusId = null;	// current
	private $statusOptions = [];
	private $statusMap = [];

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
		$institutionId = $this->Session->read('Institutions.id');
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

		if (!is_null($this->nextGrade)) {
			$gradeId = $this->nextGrade->id;
			$html .= $Form->hidden($fieldPrefix.".education_grade_id", ['value' => $gradeId]);
		}

		if (!is_null($this->request->query('mode'))) {
			return $html;
		}
	}

	public function onGetEducationGradeId(Event $event, Entity $entity) {
		$html = '';

		$id = $entity->user->id;
		$selectedGrade = $this->request->query('grade');
		$currentGrade = $this->EducationGrades->get($selectedGrade);

		if (!is_null($this->request->query('mode'))) {
			$html .= '<span class="grade_'.$id.'" id="grade_'.$id.'_'.$this->repeatStatusId.'" style="display: none">';
				$html .= $currentGrade->programme_grade_name;
			$html .= '</span>';

			if (!is_null($this->nextGrade)) {
				$html .= '<span class="grade_'.$id.'" id="grade_'.$id.'_'.$this->nextStatusId.'">';
					$html .= $this->nextGrade->programme_grade_name;
				$html .= '</span>';
			}
		} else {
			if ($entity->student_status_id == $this->repeatStatusId) {
				$html .= $currentGrade->programme_grade_name;
			} else {
				if (!is_null($this->nextGrade)) {
					$html .= $this->nextGrade->programme_grade_name;
				} else {
					$html .= '&nbsp;';
				}
			}
		}

		return $html;
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		$this->ControllerAction->field('openemis_no');
		$this->ControllerAction->field('academic_period_id', ['visible' => false]);
		$this->ControllerAction->field('start_date', ['visible' => false]);
		$this->ControllerAction->field('end_date', ['visible' => false]);
		$this->ControllerAction->field('institution_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['openemis_no', 'student_id', 'student_status_id', 'education_grade_id']);

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
			$GradeStudents = TableRegistry::get('Institution.StudentPromotion');

			// Academic Periods
			if (!is_null($this->request->query('mode'))) {
				// edit mode, disabled Periods control and restrict selectedPeriod to current
				$selectedPeriod = $this->AcademicPeriods->getCurrent();
			} else {
				// index mode
				$periodOptions = $this->AcademicPeriods->getList();
				if (empty($this->request->query['period'])) {
					$this->request->query['period'] = $this->AcademicPeriods->getCurrent();
				}
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
			}
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

				// Student Statuses
				$statuses = $this->StudentStatuses
					->find()
					->all();
				$statusOptions = [];
				foreach ($statuses as $entity) {
					$statusOptions[$entity->id] = $entity->name;
					$this->statusMap[$entity->code] = $entity->id;
				}

				$promotedOptions = [
					$this->statusMap['PROMOTED'] => $statusOptions[$this->statusMap['PROMOTED']],
					$this->statusMap['REPEATED'] => $statusOptions[$this->statusMap['REPEATED']]
				];

				$graduatedOptions = [
					$this->statusMap['GRADUATED'] => $statusOptions[$this->statusMap['GRADUATED']],
					$this->statusMap['REPEATED'] => $statusOptions[$this->statusMap['REPEATED']]
				];
				// End

				if (!is_null($this->nextGrade)) {
					$this->statusOptions = $promotedOptions;
					$this->nextStatusId = $this->statusMap['PROMOTED'];
				} else {
					$this->statusOptions = $graduatedOptions;
					$this->nextStatusId = $this->statusMap['GRADUATED'];
				}

				$this->repeatStatusId = $this->statusMap['REPEATED'];
				$this->currentStatusId = $this->statusMap['CURRENT'];
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
		$this->dataCount = $data->count();
	}

	public function afterAction(Event $event, ArrayObject $config) {
		if (!is_null($this->request->query('mode'))) {
			// Academic Period Elements
			$indexElements = $this->controller->viewVars['indexElements'];
			$selectedPeriod = $this->request->query('period');
			$currentPeriod = $this->AcademicPeriods->get($selectedPeriod);
			$startDate = date('Y-m-d', strtotime($currentPeriod->start_date));

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

			$nextPeriod = $this->AcademicPeriods
				->find()
				->find('visible')
				->where($where)
				->order([$this->AcademicPeriods->aliasField('start_date asc')])
				->first();
			if (!empty($nextPeriod)) {
				$this->advancedSelectOptions($periodOptions, $nextPeriod->id);
			} else {
				$this->Alert->warning('StudentPromotion.noPeriods');
			}

			$indexElements[] = [
				'name' => 'Institution.StudentGrades/periods',
				'data' => [
					'alias' => $this->EducationGrades->alias(),
					'period' => $currentPeriod->name,
					'periods' => $periodOptions
				],
				'options' => [],
				'order' => 1
			];
			$this->controller->set(compact('indexElements'));
			// End

			if ($this->dataCount > 0) {
				$config['formButtons'] = true;
				$config['url'] = $config['buttons']['index']['url'];
				$config['url'][0] = 'indexEdit';
			} else {
				$this->Alert->info('StudentPromotion.noData');
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
		} else {
			if ($this->AccessControl->check(['Institutions', 'Grades', 'indexEdit'])) {
				$graduateButton = $buttons['index'];
				$graduateButton['url'][0] = 'index';
				$graduateButton['url']['mode'] = 'edit';
				$graduateButton['type'] = 'button';
				$graduateButton['label'] = '<i class="fa kd-graduate"></i>';
				$graduateButton['attr'] = $attr;
				$graduateButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
				$graduateButton['attr']['title'] = __('Promotion') . ' / ' . __('Graduation');
				unset($graduateButton['url']['period']);

				$toolbarButtons['graduate'] = $graduateButton;
				$toolbarButtons['back'] = $buttons['back'];
				$toolbarButtons['back']['type'] = null;
			}
		}
	}

	public function indexEdit() {
		if ($this->request->is(['post', 'put'])) {
			$requestData = $this->request->data;

			if (array_key_exists($this->EducationGrades->alias(), $requestData)) {
				$academicPeriodId = $requestData[$this->EducationGrades->alias()]['academic_period_id'];
				$educationGradeId = $requestData[$this->EducationGrades->alias()]['education_grade_id'];
				$nextAcademicPeriodId = $requestData[$this->EducationGrades->alias()]['next_academic_period_id'];
				$nextStatusId = $requestData[$this->EducationGrades->alias()]['next_status_id'];
				$repeatStatusId = $requestData[$this->EducationGrades->alias()]['repeat_status_id'];
				$currentStatusId = $requestData[$this->EducationGrades->alias()]['current_status_id'];
				$nextPeriod = $this->AcademicPeriods->get($nextAcademicPeriodId);

				if (array_key_exists('student_promotion', $requestData[$this->EducationGrades->alias()])) {
					foreach ($requestData[$this->EducationGrades->alias()]['student_promotion'] as $key => $obj) {
						if ($obj['student_status_id'] == $repeatStatusId) {
							$status = $repeatStatusId;
							$obj['education_grade_id'] = $educationGradeId;
						} else {
							$status = $nextStatusId;
						}
						$obj['student_status_id'] = $currentStatusId;
						$obj['academic_period_id'] = $nextPeriod->id;
						$obj['start_date'] = date('Y-m-d', strtotime($nextPeriod->start_date));
						$obj['end_date'] = date('Y-m-d', strtotime($nextPeriod->end_date));

						$this->updateAll(['student_status_id' => $status], [
							'institution_id' => $obj['institution_id'],
							'student_id' => $obj['student_id'],
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
