<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyTableCellsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_survey_table_cells');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteSurveyNew', ['className' => 'Institution.SurveyNew']);
		$this->belongsTo('InstitutionSiteSurveyDrafts', ['className' => 'Institution.SurveyDrafts']);
		$this->belongsTo('InstitutionSiteSurveyCompleted', ['className' => 'Institution.SurveyCompleted']);
		$this->belongsTo('SurveyTableRows', ['className' => 'Survey.SurveyTableRows']);
		$this->belongsTo('SurveyTableColumns', ['className' => 'Survey.SurveyTableColumns']);

		// public $belongsTo = array(
		// 	'InstitutionSiteSurveyNew',
		// 	'InstitutionSiteSurveyDraft',
		// 	'Surveys.SurveyTableRow',
		// 	'Surveys.SurveyTableColumn',
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
		// $validator->add('name', 'notBlank', [
		// 	'rule' => 'notBlank'
		// ]);
		return $validator;
	}

	public function beforeAction() {
		// parent::beforeAction();
		// $this->Navigation->addCrumb('Surveys', array('action' => $this->alias, $this->action));
		// $this->Navigation->addCrumb('New');
		// $this->setVar(compact('contentHeader'));
	}
	
}
