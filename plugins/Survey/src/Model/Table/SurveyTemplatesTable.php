<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class SurveyTemplatesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		//$this->belongsTo('SurveyModules', ['className' => 'Survey.SurveyModules']);
		//$this->hasMany('SurveyQuestions', ['className' => 'Survey.SurveyQuestions', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	/*
	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'survey_module_id']],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}
	*/
}
