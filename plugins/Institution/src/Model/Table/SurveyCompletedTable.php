<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyCompletedTable extends AppTable {

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'fields' => ['AcademicPeriods.id', 'AcademicPeriods.order', 'AcademicPeriods.name']]);
		$this->belongsTo('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

		// public $belongsTo = array(
		// 	'Surveys.SurveyTemplate',
		// 	'InstitutionSite' => array(
		// 		'className' => 'InstitutionSite',
		// 		'fields' => array('InstitutionSite.id', 'InstitutionSite.code', 'InstitutionSite.name')
		// 	),
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

	public function beforeAction() {
		
	}

	// public $actsAs = array(
	// 	'Excel',
	// 	'ControllerAction2',
	// 	'Surveys.Survey' => array(
	// 		'module' => 'Institution',
	// 		'status' => 2,
	// 		'customfields' => array(
	// 			'modelValue' => 'InstitutionSiteSurveyAnswer',
	// 			'modelCell' => 'InstitutionSiteSurveyTableCell'
	// 		),
	// 		'conditions' => array(
	// 			'institution_site_id' => array('sessionKey' => 'InstitutionSite.id')
	// 		)
	// 	)
	// );

	// /* Excel Behaviour */
	// public function excelGetConditions() {
	// 	$_conditions = parent::excelGetConditions();

	// 	$conditions = array();
	// 	if (CakeSession::check('InstitutionSiteSurveyCompleted.id')) {
	// 		$id = CakeSession::read('InstitutionSiteSurveyCompleted.id');
	// 		$InstitutionSiteSurveyCompleted = ClassRegistry::init('InstitutionSiteSurveyCompleted');
	// 		$surveyTemplateId = $InstitutionSiteSurveyCompleted->field('survey_template_id', array('id' => $id));
	// 		$conditions = array(
	// 			'InstitutionSiteSurveyCompleted.id' => $id,
	// 			'SurveyTemplate.id' => $surveyTemplateId
	// 		);
	// 	}
	// 	$conditions[] = 'InstitutionSiteSurveyCompleted.survey_template_id = SurveyTemplate.id';
	// 	$conditions['InstitutionSiteSurveyCompleted.status'] = 2;
	// 	$conditions = array_merge($_conditions, $conditions);
	// 	return $conditions;
	// }

	// public function excelGetModels() {
	// 	$models = parent::excelGetModels();
	// 	$models = array(
	// 		array(
	// 			'model' => $this,
	// 			'include' => array(
	// 				'plugin' => 'Surveys',
	// 				'header' => 'SurveyQuestion',
	// 				'data' => 'InstitutionSiteSurveyAnswer',
	// 				'dataOptions' => 'SurveyQuestionChoice'
	// 			)
	// 		)
	// 	);
	// 	return $models;
	// }

	// public function beforeAction() {
	// 	parent::beforeAction();
	// 	$this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
	// 	$this->Navigation->addCrumb('Completed');
	// 	$this->setVar(compact('contentHeader'));
	// }

	// public function index($action=null) {
	// 	$data = $this->getSurveyTemplatesByModule();
	// 	if (is_null($action) && empty($data)) {
	// 		$this->Message->alert('general.noData');
	// 	}
	// 	$this->setVar(compact('data'));
	// }

	// public function view($id=0) {
	// 	if ($this->exists($id)) {
	// 		$this->contain('SurveyTemplate');
	// 		$template = $this->findById($id);
	// 		$template = $template['SurveyTemplate'];
	// 		$data = $this->getFormatedSurveyData($template['id']);
	// 		$dataValues = $this->getFormatedSurveyDataValues($id);

	// 		$model = 'SurveyQuestion';
	//     	$modelOption = 'SurveyQuestionChoice';
	//     	$modelValue = 'InstitutionSiteSurveyAnswer';
	//     	$modelRow = 'SurveyTableRow';
	//     	$modelColumn = 'SurveyTableColumn';
	// 		$modelCell = 'InstitutionSiteSurveyTableCell';
	// 		$action = 'view';

	// 		$this->Session->write($this->alias.'.id', $id);
	// 		$this->setVar(compact('id', 'template', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
	// 	} else {
	// 		$this->Message->alert('general.notExists');
	// 		return $this->redirect(array('action' => $this->alias, 'index'));
	// 	}
	// }

	// public function remove() {
	// 	if ($this->Session->check($this->alias.'.id')) {
	// 		$id = $this->Session->read($this->alias.'.id');
	// 		$result = $this->updateAll(
	// 		    array(
	// 		    	$this->alias.'.status' => 1
	// 		    ),
	// 		    array(
	// 		    	$this->alias.'.id' => $id
	// 		    )
	// 		);
	// 		if($result) {
	// 			$this->Message->alert('Survey.reject.success');
	// 		} else {
	// 			$this->log($this->validationErrors, 'debug');
	// 			$this->Message->alert('Survey.reject.failed');
	// 		}
	// 		$this->Session->delete($this->alias.'.id');
	// 		return $this->redirect(array('action' => $this->alias, 'index', $this->action));
	// 	}
	// }
}
