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
			'fieldKey' => 'survey_question_id',
			'formKey' => 'survey_form_id',
			'recordKey' => 'institution_site_survey_id',
			'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
			'formFilterClass' => null,
			'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'condition' => [$this->aliasField('status') => self::COMPLETED],
		]);
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
		$this->ControllerAction->field('survey_form', ['type' => 'hidden']);
		$this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
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
		$configCondition = $this->getCondition();
		$condition = [
			$this->aliasField('academic_period_id') => $academicPeriodId
		];
		$condition = array_merge($configCondition, $condition);
		$this->setCondition($condition);

		// For Surveys only
		$forms = $this->getForms($surveyFormId);
		foreach ($forms as $formId => $formName) {
			$this->excelContent($sheets, $formName, null, $formId);
		}

		// Stop the customfieldlist behavior onExcelBeforeStart function
		$event->stopPropagation();
	}

	public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (isset($this->request->data[$this->alias()]['feature'])) {
				$feature = $this->request->data[$this->alias()]['feature'];
				if ($feature == $this->registryAlias()) {
					$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
					$surveyFormOptions = $InstitutionSurveyTable
						->find('list', [
							'keyField' => 'id',
							'valueField' => 'name'
						])
						->contain(['SurveyForms'])
						->select(['id' => 'SurveyForms.id', 'name' => 'SurveyForms.name'])
						->group([ 
							$InstitutionSurveyTable->aliasField('survey_form_id')
						])
						->where([
							$InstitutionSurveyTable->aliasField('status') => self::COMPLETED,
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
					$InstitutionSurveyTable = TableRegistry::get('Institution.InstitutionSurveys');
					$academicPeriodOptions = $InstitutionSurveyTable
						->find('list', [
							'keyField' => 'id',
							'valueField' => 'name'
						])
						->contain(['AcademicPeriods'])
						->select(['id' => 'AcademicPeriods.id', 'name' => 'AcademicPeriods.name'])
						->where([
							$InstitutionSurveyTable->aliasField('status') => self::COMPLETED,
							$InstitutionSurveyTable->aliasField('survey_form_id') => $surveyForm
						])
						->group([
							$InstitutionSurveyTable->aliasField('survey_form_id'), 
							$InstitutionSurveyTable->aliasField('academic_period_id')
						])
						->order(['AcademicPeriods.order'])
						->toArray();
					$attr['options'] = $academicPeriodOptions;
					$attr['type'] = 'select';
					return $attr;
				}
			}
		}
	}

	public function onExcelGetStatus(Event $event, Entity $entity) {
		$status = $entity->status;
		switch ($status) {
			case self::EXPIRED:
				return 'Expired';
				break;
			case self::NEW_SURVEY:
				return 'New';
				break;
			case self::DRAFT:
				return 'Draft';
				break;
			case self::COMPLETED:
				return 'Completed';
				break;
		}
	}
}
