<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyDraftsTable extends AppTable {

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

	public function beforeAction() {
		// parent::beforeAction();
		// $this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
		// $this->Navigation->addCrumb('Draft');
		// $this->setVar(compact('contentHeader'));		
	}


	
	// public $actsAs = array(
	// 	'ControllerAction2',
	// 	'Surveys.Survey' => array(
	// 		'module' => 'Institution',
	// 		'status' => 1,
	// 		'customfields' => array(
	// 			'modelValue' => 'InstitutionSiteSurveyAnswer',
	// 			'modelCell' => 'InstitutionSiteSurveyTableCell'
	// 		),
	// 		'conditions' => array(
	// 			'institution_site_id' => array('sessionKey' => 'InstitutionSite.id')
	// 		)
	// 	)
	// );


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

	// public function edit($id=0) {
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
	// 		$action = 'edit';

	// 		if ($this->request->is(array('post', 'put'))) {
	// 			$surveyData = $this->prepareSubmitSurveyData($this->request->data);

	// 			$dataSource = $this->getDataSource();
	// 			$dataSource->begin();
	// 			$this->InstitutionSiteSurveyAnswer->deleteAll(array(
	// 				'InstitutionSiteSurveyAnswer.institution_site_survey_id' => $id
	// 			), false);
	// 			$this->InstitutionSiteSurveyTableCell->deleteAll(array(
	// 				'InstitutionSiteSurveyTableCell.institution_site_survey_id' => $id
	// 			), false);

	// 			if ($this->saveAll($surveyData)) {
	// 				$dataSource->commit();
	// 				if($surveyData[$this->alias]['status'] == 2) {
	// 					$this->Message->alert('Survey.save.final');
	// 					return $this->redirect(array('action' => $this->alias, 'index', $this->action));
	// 				} else {
	// 					$this->Message->alert('Survey.save.draft');
	// 					return $this->redirect(array('action' => $this->alias, 'edit', $id));
	// 				}
	// 			} else {
	// 				$dataSource->rollback();
	// 				//put back data when validation failed
	// 				$dataValues = $this->prepareFormatedDataValues($surveyData);
	// 				$this->log($this->validationErrors, 'debug');
	// 				$this->Message->alert('general.add.failed');
	// 			}
	// 		}

	// 		$this->setVar(compact('id', 'template', 'data', 'dataValues', 'model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));
	// 	} else {
	// 		$this->Message->alert('general.notExists');
	// 		return $this->redirect(array('action' => $this->alias, 'index'));
	// 	}
	// }

	// public function remove() {
	// 	if ($this->Session->check($this->alias.'.id')) {
	// 		$id = $this->Session->read($this->alias.'.id');

	// 		$dataSource = $this->getDataSource();
	// 		$dataSource->begin();
	// 		$this->InstitutionSiteSurveyAnswer->deleteAll(array(
	// 			'InstitutionSiteSurveyAnswer.institution_site_survey_id' => $id
	// 		), false);
	// 		$this->InstitutionSiteSurveyTableCell->deleteAll(array(
	// 			'InstitutionSiteSurveyTableCell.institution_site_survey_id' => $id
	// 		), false);

	// 		if($this->delete($id)) {
	// 			$dataSource->commit();
	// 			$this->Message->alert('general.delete.success');
	// 		} else {
	// 			$dataSource->rollback();
	// 			$this->log($this->validationErrors, 'debug');
	// 			$this->Message->alert('general.delete.failed');
	// 		}
	// 		$this->Session->delete($this->alias.'.id');
	// 		return $this->redirect(array('action' => $this->alias, 'index', $this->action));
	// 	}
	// }
}
