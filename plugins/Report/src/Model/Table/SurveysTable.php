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
	private $surveyStatuses = [];

	public function initialize(array $config) {
		$this->table('institution_surveys');
		parent::initialize($config);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->addBehavior('Excel', [
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.CustomFieldList', [
			'moduleKey' => null,
			'model' => 'Institution.InstitutionSurveys',
			'formKey' => 'survey_form_id',
			'formFilterClass' => null,
			'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);

		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('survey_form', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
		$this->ControllerAction->field('status', ['type' => 'hidden']);
		$this->ControllerAction->field('postfix', ['type' => 'hidden']);
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

	public function onExcelAfterHeader(Event $event, ArrayObject $settings) {
		if ($settings['renderNotComplete']) {
			$fields = $settings['sheet']['fields'];
			$requestData = json_decode($settings['process']['params']);
			$surveyFormId = $requestData->survey_form;
			$academicPeriodId = $requestData->academic_period_id;
			$surveyFormName = $this->SurveyForms->get($surveyFormId)->name;
			$academicPeriodName = $this->AcademicPeriods->get($academicPeriodId)->name;
			$userId = $requestData->user_id;
			$superAdmin = $requestData->super_admin;
			$InstitutionsTable = $this->Institutions;
			
			$missingRecords = $InstitutionsTable->find()
				->where(['NOT EXISTS ('.
					$this->find()->where([
						$this->aliasField('academic_period_id').' = '.$academicPeriodId,
						$this->aliasField('survey_form_id').' = '.$surveyFormId,
						$this->aliasField('institution_id').' = '.$InstitutionsTable->aliasField('id')
					])
				.')'])
				->innerJoinWith('Areas')
				->leftJoinWith('AreaAdministratives')
				->select([
					'institution_id' => $InstitutionsTable->aliasField('name'),
					'code' => $InstitutionsTable->aliasField('code'),
					'area' => 'Areas.name',
					'area_administrative' => 'AreaAdministratives.name'
				]);

			if (!$superAdmin) {
				$missingRecords->find('ByAccess', ['userId' => $userId]);
			}

			$writer = $settings['writer'];
			$sheetName = $settings['sheet']['name'];
			$mappingArray = ['status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'code'];

			foreach ($missingRecords->all() as $record) {
				$record->academic_period_id = $academicPeriodName;
				$record->survey_form_id = $surveyFormName;
				$record->status_id = __('Not Completed');

				$row = [];
				foreach ($fields as $field) {
					if (in_array($field['field'], $mappingArray)) {
						$row[] = $record->$field['field'];
					} else if ($field['field'] == 'area_id') {
						$row[] = $record->area;
					} else if ($field['field'] == 'area_administrative_id') {
						$row[] = $record->area_administrative;
					} else {
						$row[] = '';
					}
				}
				$writer->writeSheetRow($sheetName, $row);
			}
			$settings['renderNotComplete'] = false;
		}
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {

		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);
		$surveyFormId = $requestData->survey_form;
		$academicPeriodId = $requestData->academic_period_id;
		$status = $requestData->status;
		$WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

		if (!empty($academicPeriodId)) {
			$surveyStatuses = $WorkflowStatusesTable->WorkflowModels->getWorkflowStatusesCode('Institution.InstitutionSurveys');
			if ($surveyStatuses[$status] == 'NOT_COMPLETED') {
				$settings['renderNotComplete'] = true;
			} else {
				$settings['renderNotComplete'] = false;
			}
		} else {
			$academicPeriodId = 0;
		}

		$configCondition = $this->getCondition();
		$condition = [
			$this->aliasField('academic_period_id') => $academicPeriodId
		];

		$surveyStatuses = $WorkflowStatusesTable->getWorkflowSteps($status);
		
		$this->surveyStatuses = $WorkflowStatusesTable->getWorkflowStepStatusNameMappings('Institution.InstitutionSurveys');
		if (!empty($surveyStatuses)) {
			$statusCondition = [
				$this->aliasField('status_id').' IN ' => array_keys($surveyStatuses)
			];
			$condition = array_merge($condition, $statusCondition);
		}
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
		$requestData = json_decode($settings['process']['params']);
		$userId = $requestData->user_id;
		$superAdmin = $requestData->super_admin;
		$query
			->select([
				'code' => 'Institutions.code', 
				'area' => 'Areas.name', 
				'area_administrative' => 'AreaAdministratives.name'
			])
			->contain([
				'Institutions.Areas', 
				'Institutions.AreaAdministratives'
			]);
		if (!$superAdmin) {
			$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('institution_id')]);
		}
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {

		// To update to this code when upgrade server to PHP 5.5 and above
		// unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

		foreach ($fields as $key => $field) {
			if ($field['field'] == 'institution_id') {
				unset($fields[$key]);
				break;
			}
		}

		$fields[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$fields[] = [
			'key' => 'InstitutionSurveys.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
		];

		$fields[] = [
			'key' => 'Institutions.area_id',
			'field' => 'area',
			'type' => 'string',
			'label' => '',
		];

		$fields[] = [
			'key' => 'Institutions.area_administrative_id',
			'field' => 'area_administrative',
			'type' => 'string',
			'label' => '',
		];
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				if ($feature == $this->registryAlias()) {
					$surveyFormOptions = $this->SurveyForms
						->find('list')
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
					$SurveyStatusTable = $this->SurveyForms->surveyStatuses;
					$academicPeriodOptions = $SurveyStatusTable
						->find('list', [
							'keyField' => 'academic_id',
							'valueField' => 'academic_name'
						])
						->matching('AcademicPeriods')
						->select(['academic_id' => 'AcademicPeriods.id', 'academic_name' => 'AcademicPeriods.name'])
						->where([
							$SurveyStatusTable->aliasField('survey_form_id') => $surveyForm,
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
					$surveyStatuses = $this->Workflow->getWorkflowStatuses('Institution.InstitutionSurveys');
					$attr['type'] = 'select';
					$surveyTable = $this;
					$attr['options'] = $surveyStatuses;
					return $attr;
				}
			}
		}
	}

	public function onExcelGetStatusId(Event $event, Entity $entity) {
		$surveyStatuses = $this->surveyStatuses;
		$status = $entity->status_id;
		return __($surveyStatuses[$status]);
	}

	public function onUpdateFieldPostfix(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['survey_form'])) {
				$surveyForm = $this->request->data[$this->alias()]['survey_form'];
				if (!empty($surveyForm)) {
					$attr['value'] = $this->SurveyForms->get($surveyForm)->name;
					return $attr;
				}
			}
		}
	}
}
