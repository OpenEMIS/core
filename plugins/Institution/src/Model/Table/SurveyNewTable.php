<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyNewTable extends AppTable {

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'fields' => ['AcademicPeriods.id', 'AcademicPeriods.order', 'AcademicPeriods.name']]);
		$this->belongsTo('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

		// public $belongsTo = array(
		// 	'Surveys.SurveyTemplate',
		// 	'AcademicPeriod' => array(
		// 		'className' => 'AcademicPeriod',
		// 		'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name', 'AcademicPeriod.order')
		// 	),
		// 	'ModifiedUser' => array(
		// 		'className' => 'SecurityUser',
		// 		'fields' => array('first_name', 'last_name'),
		// 		'foreignKey' => 'modified_user_id'
		// 	),
		// 	'CreatedUser' => array(
		// 		'className' => 'SecurityUser',
		// 		'fields' => array('first_name', 'last_name'),
		// 		'foreignKey' => 'created_user_id'
		// 	)
		// );

		$this->hasMany('InstitutionSiteSurveyAnswers', ['className' => 'Institution.SurveyAnswers']);
		$this->hasMany('InstitutionSiteSurveyTableCells', ['className' => 'Institution.SurveyTableCells']);

		// public $hasMany = array(
		// 	'InstitutionSiteSurveyAnswer',
		// 	'InstitutionSiteSurveyTableCell'
		// );

	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	// public $actsAs = array(
	// 	'ControllerAction2',
	// 	'Surveys.Survey' => array(
	// 		'module' => 'Institution',
	// 		'status' => 0,
	// 		'customfields' => array(
	// 			'modelValue' => 'InstitutionSiteSurveyAnswer',
	// 			'modelCell' => 'InstitutionSiteSurveyTableCell'
	// 		),
	// 		'conditions' => array(
	// 			'institution_site_id' => array('sessionKey' => 'InstitutionSite.id')
	// 		)
	// 	)
	// );

	public function beforeAction() {
		// parent::beforeAction();
		// $this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
		// $this->Navigation->addCrumb('New');
		// $this->setVar(compact('contentHeader'));
	}

	// public function index($action=null) {
	// 	$data = $this->getSurveyTemplatesByModule();
	// 	if (is_null($action) && empty($data)) {
	// 		$this->Message->alert('general.noData');
	// 	}
	// 	$this->setVar(compact('data'));
	// }

	// public function add($templateId=0, $academicPeriodId=0) {
	// 	if ($this->SurveyTemplate->exists($templateId)) {
	// 		$this->SurveyTemplate->contain();
	// 		$template = $this->SurveyTemplate->findById($templateId);
	// 		$template = $template['SurveyTemplate'];
	// 		$data = $this->getFormatedSurveyData($templateId);
	// 		$dataValues = array();

	// 		$model = 'SurveyQuestion';
	//     	$modelOption = 'SurveyQuestionChoice';
	//     	$modelValue = 'InstitutionSiteSurveyAnswer';
	//     	$modelRow = 'SurveyTableRow';
	//     	$modelColumn = 'SurveyTableColumn';
	// 		$modelCell = 'InstitutionSiteSurveyTableCell';
	// 		$action = 'edit';

	// 		if ($this->request->is(array('post', 'put'))) {
	// 			$surveyData = $this->prepareSubmitSurveyData($this->request->data);

	// 			if ($this->saveAll($surveyData)) {
	// 				if($surveyData[$this->alias]['status'] == 2) {
	// 					$this->Message->alert('Survey.save.final');
	// 					return $this->redirect(array('action' => $this->alias, 'index', $this->action));
	// 				} else {
	// 					$this->Message->alert('Survey.save.draft');
	// 					return $this->redirect(array('action' => 'InstitutionSiteSurveyDraft', 'edit', $this->id));
	// 				}
	// 			} else {
	// 				//put back data when validation failed
	// 				$dataValues = $this->prepareFormatedDataValues($surveyData);
	// 				$this->log($this->validationErrors, 'debug');
	// 				$this->Message->alert('general.add.failed');
	// 			}
	// 		}

	// 		$this->setVar(compact('templateId', 'academicPeriodId', 'template', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
	// 	} else {
	// 		$this->Message->alert('general.notExists');
	// 		return $this->redirect(array('action' => $this->alias, 'index'));
	// 	}
	// }
}
