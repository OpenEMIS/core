<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyAnswersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_survey_answers');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteSurveys', ['className' => 'Institution.Surveys']);
		$this->belongsTo('SurveyQuestions', ['className' => 'Survey.SurveyQuestions']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

		// $this->belongsTo('InstitutionSiteSurveyNew', ['className' => 'Institution.SurveyNew', 'foreignKey' => 'institution_site_survey_id']);
		// $this->belongsTo('InstitutionSiteSurveyDrafts', ['className' => 'Institution.SurveyDrafts', 'foreignKey' => 'institution_site_survey_id']);

		// public $belongsTo = array(
		// 	'InstitutionSiteSurveyNew',
		// 	'InstitutionSiteSurveyDraft',
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
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction() {
		
	}

	// public $actsAs = array(
	// 	'ControllerAction2',
	// 	'Surveys.SurveyAnswer' => array(
	// 		'customfields' => array(
	// 			'modelValue' => 'InstitutionSiteSurveyAnswer',
	// 			'modelCell' => 'InstitutionSiteSurveyTableCell'
	// 		)
	// 	)
	// );

}
