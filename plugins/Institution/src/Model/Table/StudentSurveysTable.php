<?php
namespace Institution\Model\Table;

// use ArrayObject;
use Cake\ORM\Entity;
// use Cake\ORM\Query;
// use Cake\ORM\TableRegistry;
// use Cake\Network\Request;
// use Cake\Utility\Inflector;
// use Cake\Validation\Validator;
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
			'module' => 'Student.Students'
		]);
		// $this->addBehavior('CustomField.Record', [
		// 	'moduleKey' => null,
		// 	'fieldKey' => 'survey_question_id',
		// 	'tableColumnKey' => 'survey_table_column_id',
		// 	'tableRowKey' => 'survey_table_row_id',
		// 	'formKey' => 'survey_form_id',
		// 	// 'filterKey' => 'custom_filter_id',
		// 	'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
		// 	// 'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
		// 	'recordKey' => 'institution_site_survey_id',
		// 	'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
		// 	'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		// ]);
	}

	public function beforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
			['name' => 'Institution.StudentSurveys/controls', 'data' => [], 'options' => []]
		];

		$this->controller->set('toolbarElements', $toolbarElements);

		$session = $this->controller->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		}
		$this->studentId = $this->request->query['student_id'];

		// Academic Periods
		$periodOptions = $this->AcademicPeriods->getList();
		$selectedPeriod = $this->queryString('period', $periodOptions);
		$this->advancedSelectOptions($periodOptions, $selectedPeriod);
		$this->controller->set('periodOptions', $periodOptions);
		// End

		// Survey Forms
		$formOptions = $this->getForms();
		$selectedForm = $this->queryString('form', $formOptions);
		$this->advancedSelectOptions($formOptions, $selectedForm);
		$this->controller->set('formOptions', $formOptions);
		// End
	}

	public function afterAction(Event $event) {
		$indexElements = [];
		$this->controller->set('indexElements', $indexElements);
	}

	public function indexBeforeAction(Event $event) {
		$this->setupTabElements();
		$this->_buildSurveyRecords($this->studentId);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity=null) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		$id = $this->request->query['id'];
		$studentId = $this->studentId;
		
		$tabElements = [
			'Students' => [
				'text' => __('Academic'),
				'url' => array_merge($url, ['action' => 'Students', 'view', $id])
			],
			'StudentUser' => [
				'text' => __('General'),
				'url' => array_merge($url, ['action' => 'StudentUser', 'view', $studentId, 'id' => $id])
			],
			'StudentSurveys' => ['text' => __('Survey')]
		];

		if ($this->action == 'index') {
			$tabElements['StudentSurveys']['url'] = array_merge($url, ['action' => $this->alias(), 'index', 'id' => $id, 'student_id' => $studentId]);
		} else {
			$tabElements['StudentSurveys']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id, 'student_id' => $studentId]);
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
}
