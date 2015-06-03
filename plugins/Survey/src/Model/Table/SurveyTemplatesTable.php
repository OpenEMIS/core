<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyTemplatesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyModules', ['className' => 'Survey.SurveyModules']);
		$this->hasMany('SurveyQuestions', ['className' => 'Survey.SurveyQuestions']);
	}

	public function validationDefault(Validator $validator) {
		$validator
		->requirePresence('name')
		->notEmpty('name', 'Please enter a name.')
    	->add('name', [
    		'unique' => [
		        'rule' => ['validateUnique', ['scope' => 'survey_module_id']],
		        'provider' => 'table',
		        'message' => 'This name is already exists in the system'
		    ]
	    ])
	    ->requirePresence('survey_module_id')
		->notEmpty('survey_module_id', 'Please select a module.');

		return $validator;
	}
}
