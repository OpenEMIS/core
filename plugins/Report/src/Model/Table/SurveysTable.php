<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class SurveysTable extends AppTable  {
	const EXPIRED = -1;
	const NEW_SURVEY = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('Excel', [
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'moduleKey' => null,
			'model' => 'Institution.InstitutionSurveys',
			'formKey' => 'survey_form_id',
			'formFilterClass' => null,
			'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
		]);
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('survey_form', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('status', ['type' => 'hidden']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['options'] = $this->controller->getFeatureOptions($this->alias());
			$attr['onChangeReload'] = true;
			if (!(isset($this->request->data[$this->alias()]['feature']))) {
				$option = $attr['options'];
				reset($option);
				$this->request->data[$this->alias()]['feature'] = key($option);
			}
			return $attr;
		}
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);
		$surveyFormId = $requestData->survey_form;
		$academicPeriodId = $requestData->academic_period_id;
		$status = $requestData->status;
		$configCondition = $this->getCondition();
		$condition = [
			$this->aliasField('academic_period_id') => $academicPeriodId
		];
		$statusCondition = [];
		if ($status == self::COMPLETED) {
			$statusCondition = [
				$this->aliasField('status') => self::COMPLETED
			];
		} else {
			$statusCondition = [
				$this->aliasField('status').' IS NOT' => self::COMPLETED
			];
		}
		$condition = array_merge($condition, $statusCondition);
		$condition = array_merge($condition, $configCondition);
		$this->setCondition($condition);

		// For Surveys only
		$forms = $this->getForms($surveyFormId);
		foreach ($forms as $formId => $formName) {
			$this->excelContent($sheets, $formName, null, $formId);
		}

		// Stop the customfieldlist behavior onExcelBeforeStart function
		$event->stopPropagation();
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
		$query->select(['area_id' => 'Areas.name', 'area_administrative_id' => 'AreaAdministratives.name'])->contain(['Institutions.Areas', 'Institutions.AreaAdministratives']);
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {

		$fields[] = [
			'key' => 'Institutions.area_id',
			'field' => 'area_id',
			'type' => 'string',
			'label' => '',
		];

		$fields[] = [
			'key' => 'Institutions.area_administrative_id',
			'field' => 'area_administrative_id',
			'type' => 'string',
			'label' => '',
		];
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				if ($feature == $this->registryAlias()) {
					$surveyFormOptions = $this
						->find('list', [
							'keyField' => 'id',
							'valueField' => 'name'
						])
						->contain(['SurveyForms'])
						->select(['id' => 'SurveyForms.id', 'name' => 'SurveyForms.name'])
						->group([ 
							$this->aliasField('survey_form_id')
						])
						->toArray();
					$attr['options'] = $surveyFormOptions;
					$attr['onChangeReload'] = true;
					$attr['type'] = 'select';
					if (empty($this->request->data[$this->alias()]['survey_form'])) {
						$option = $attr['options'];
						reset($option);
						$this->request->data[$this->alias()]['survey_form'] = key($option);
					}
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature']) && isset($this->request->data[$this->alias()]['survey_form'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				$surveyForm = $this->request->data[$this->alias()]['survey_form'];
				if ($feature == $this->registryAlias() && !empty($surveyForm)) {
					$academicPeriodOptions = $this
						->find('list', [
							'keyField' => 'id',
							'valueField' => 'name'
						])
						->contain(['AcademicPeriods'])
						->select(['id' => 'AcademicPeriods.id', 'name' => 'AcademicPeriods.name'])
						->where([
							$this->aliasField('survey_form_id') => $surveyForm
						])
						->group([
							$this->aliasField('survey_form_id'), 
							$this->aliasField('academic_period_id')
						])
						->order(['AcademicPeriods.order'])
						->toArray();
					$attr['options'] = $academicPeriodOptions;
					$attr['onChangeReload'] = true;
					$attr['type'] = 'select';
					if (empty($this->request->data[$this->alias()]['academic_period_id'])) {
						$option = $attr['options'];
						reset($option);
						$this->request->data[$this->alias()]['academic_period_id'] = key($option);
					}
					return $attr;
				}
			}
		}
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature']) 
				&& isset($this->request->data[$this->alias()]['survey_form'])
				&& isset($this->request->data[$this->alias()]['academic_period_id'])) {

				$feature = $this->request->data[$this->alias()]['feature'];
				$surveyForm = $this->request->data[$this->alias()]['survey_form'];
				$academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];

				if ($feature == $this->registryAlias() && !empty($academicPeriodId)) {

					$attr['options'] = [
						self::COMPLETED => __('Completed'),
						'-1' => __('Not Completed')
					];

					$attr['type'] = 'select';

					$surveyTable = $this;
					$selected = self::COMPLETED;

					$this->advancedSelectOptions($attr['options'], $selected, [
						'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSurveys')),
						'callable' => function($id) use ($surveyTable, $surveyForm, $academicPeriodId) {

							$query = $surveyTable->find('list', [
								'keyField' => 'surveyStatus',
								'valueField' => 'statusCount'
							]);

							// Add a case to check if the survey is completed or not
							$completedSurvey = $query->newExpr()->addCase(
								[$query->newExpr()->eq($this->aliasField('status'), self::COMPLETED)], 
								[self::COMPLETED, -1], 
								['integer', 'integer']);

							$query->select([
									'surveyStatus' => $completedSurvey,
									'statusCount' => $query->func()->count($this->aliasField('id'))
								])
								->group(['surveyStatus'])
								->where([
									$surveyTable->aliasField('survey_form_id') => $surveyForm,
									$surveyTable->aliasField('academic_period_id') => $academicPeriodId
								]);

							return $query->having(['surveyStatus' => $id])->count();
						}
					]);
					return $attr;
				}
			}
		}
	}

	public function onExcelGetStatus(Event $event, Entity $entity) {
		$status = $entity->status;
		switch ($status) {
			case self::COMPLETED:
				return __('Completed');
				break;
			default:
				return __('Not Completed');
				break;
		}
	}
}
