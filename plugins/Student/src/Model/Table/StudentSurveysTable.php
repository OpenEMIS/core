<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class StudentSurveysTable extends AppTable {
	private $institutionId = null;
	private $studentId = null;

	public function initialize(array $config) {
		$this->table('institution_student_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->addBehavior('Survey.Survey', [
			'module' => 'Student.StudentSurveys'
		]);
		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null,
			'fieldKey' => 'survey_question_id',
			'tableColumnKey' => 'survey_table_column_id',
			'tableRowKey' => 'survey_table_row_id',
			'formKey' => 'survey_form_id',
			// 'filterKey' => 'custom_filter_id',
			'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
			// 'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'recordKey' => 'institution_student_survey_id',
			'fieldValueClass' => ['className' => 'Student.StudentSurveyAnswers', 'foreignKey' => 'institution_student_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Student.StudentSurveyTableCells', 'foreignKey' => 'institution_student_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function beforeAction(Event $event) {
		//Add controls filter to index, view and edit page
		$toolbarElements = [
			['name' => 'Student.StudentSurveys/controls', 'data' => [], 'options' => []]
		];

		$this->controller->set('toolbarElements', $toolbarElements);

		$session = $this->controller->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		}
		$studentId = !is_null($this->request->query('user_id')) ? $this->request->query('user_id') : 0;

		// Build Survey Records
		$currentAction = $this->ControllerAction->action();
		if ($currentAction == 'index') {
			$this->_buildSurveyRecords($studentId);
		}
		// End

		// Academic Periods
		$periodOptions = $this->AcademicPeriods->getList();
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod, [
			'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSurveys')),
			'callable' => function($id) use ($institutionId, $studentId) {
				return $this
					->find()
					->where([
						$this->aliasField('institution_id') => $institutionId,
						$this->aliasField('student_id') => $studentId,
						$this->aliasField('academic_period_id') => $id,
						$this->aliasField('status !=') => -1	// Not expired
					])
					->count();
			}
		]);
		$this->controller->set('periodOptions', $periodOptions);
		// End

		// Survey Forms
		$surveyForms = $this->getForms();

		$formOptions = [];
		foreach ($surveyForms as $surveyFormId => $surveyForm) {
			$count = $this
				->find()
				->where([
					$this->aliasField('institution_id') => $institutionId,
					$this->aliasField('student_id') => $studentId,
					$this->aliasField('academic_period_id') => $selectedPeriod,
					$this->aliasField('survey_form_id') => $surveyFormId,
					$this->aliasField('status !=') => -1	// Not expired
				])
				->count();
			if ($count) {
				$formOptions[$surveyFormId] = $surveyForm;
			}
		}
		$selectedForm = $this->queryString('form', $formOptions);
		$this->advancedSelectOptions($formOptions, $selectedForm);
		$this->controller->set('formOptions', $formOptions);
		// End

		$this->ControllerAction->field('student_id', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('survey_form_id', ['type' => 'hidden']);
		$this->ControllerAction->field('status', ['type' => 'hidden', 'attr' => ['survey-status' => 1]]);

		$this->institutionId = $institutionId;
		$this->studentId = $studentId;
		$this->request->query['period'] = $selectedPeriod;
		$this->request->query['form'] = $selectedForm;

		$this->_redirect($institutionId, $studentId, $selectedPeriod, $selectedForm);
	}

	public function afterAction(Event $event) {
		$indexElements = [];
		$this->controller->set('indexElements', $indexElements);
	}

	public function indexBeforeAction(Event $event) {
		$this->setupTabElements();
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		$selectedStatus = !is_null($this->request->query('status')) ? $this->request->query('status') : 0;

		if (isset($toolbarButtons['list'])) {
			unset($toolbarButtons['list']);
		}

		if ($action == 'view') {
			if (isset($toolbarButtons['back'])) {
				unset($toolbarButtons['back']);
			}
			if ($selectedStatus == 2) {	//Completed
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		}
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$cancelButton = $buttons[1];
		$buttons[0] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Save As Draft'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[survey-status=1]\').val(1);']
		];
		$buttons[1] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Submit'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[survey-status=1]\').val(2);']
		];
		$buttons[2] = $cancelButton;
	}

	private function setupTabElements($entity=null) {
		$id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
		$userId = !is_null($this->request->query('user_id')) ? $this->request->query('user_id') : 0;

		$options = [
			'userRole' => 'Student',
			'action' => $this->action,
			'id' => $id,
			'userId' => $userId
		];

		$tabElements = $this->controller->getUserTabElements($options);

		if (!is_null($entity)) {
			$tabElements['StudentSurveys']['url'][0] = 'view';
			$tabElements['StudentSurveys']['url'][1] = $entity->id;
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function _buildSurveyRecords($studentId=null, $institutionId=null) {
		$session = $this->controller->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		}

		if (!is_null($studentId) && !is_null($institutionId)) {
			$surveyForms = $this->getForms();
			$todayDate = date("Y-m-d");
			$SurveyStatuses = $this->SurveyForms->SurveyStatuses;
			$SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

			// Update all New Survey to Expired by Institution ID and Student ID
			$this->updateAll(['status' => -1],
				[
					'institution_id' => $institutionId,
					'student_id' => $studentId,
					'status' => 0
				]
			);

			foreach ($surveyForms as $surveyFormId => $surveyForm) {
				$surveyStatusIds = $SurveyStatuses
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([
						$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
						$SurveyStatuses->aliasField('date_disabled >=') => $todayDate
					])
					->toArray();

				$academicPeriodIds = $SurveyStatusPeriods
					->find('list', ['keyField' => 'academic_period_id', 'valueField' => 'academic_period_id'])
					->where([$SurveyStatusPeriods->aliasField('survey_status_id IN') => $surveyStatusIds])
					->toArray();

				foreach ($academicPeriodIds as $key => $academicPeriodId) {
					$results = $this
						->find('all')
						->where([
							$this->aliasField('institution_id') => $institutionId,
							$this->aliasField('student_id') => $studentId,
							$this->aliasField('academic_period_id') => $academicPeriodId,
							$this->aliasField('survey_form_id') => $surveyFormId
						])
						->all();

					if ($results->isEmpty()) {
						// Insert New Survey if not found
						$data = [
							'institution_id' => $institutionId,
							'student_id' => $studentId,
							'academic_period_id' => $academicPeriodId,
							'survey_form_id' => $surveyFormId
						];
						$entity = $this->newEntity($data);
						if ($this->save($entity)) {
						} else {
							$this->log($entity->errors(), 'debug');
						}
					} else {
						// Update Expired Survey back to New
						$this->updateAll(['status' => 0],
							[
								'institution_id' => $institutionId,
								'student_id' => $studentId,
								'academic_period_id' => $academicPeriodId,
								'survey_form_id' => $surveyFormId,
								'status' => -1
							]
						);
					}
				}
			}
		}
	}

	public function _redirect($institutionId=null, $studentId=null, $periodId=0, $formId=0) {
		$currentAction = $this->ControllerAction->action();
		$paramsPass = $this->ControllerAction->paramsPass();

		$results = $this
			->find()
			->where([
				$this->aliasField('institution_id') => $institutionId,
				$this->aliasField('student_id') => $studentId,
				$this->aliasField('academic_period_id') => $periodId,
				$this->aliasField('survey_form_id') => $formId,
				$this->aliasField('status !=') => -1	// Not Expired
			])
			->first();

		if (!empty($results)) {
			$this->request->query['status'] = $results->status;

			$url = $this->ControllerAction->url('view');
			$url[1] = $results->id;

			if ($currentAction == 'index') {
				return $this->controller->redirect($url);
			} else {
				if ($results->id != current($paramsPass)) {
					return $this->controller->redirect($url);
				}
			}
		} else {
			$url = $this->ControllerAction->url('index');

			if ($currentAction == 'view' || $currentAction == 'edit') {
				return $this->controller->redirect($url);
			}
		}
	}
}
