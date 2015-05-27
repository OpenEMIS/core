<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyTemplatesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SurveyModules', ['className' => 'Survey.SurveyModules']);
		$this->belongsTo('ModifiedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'foreignKey' => 'modified_user_id'
		]);
		$this->belongsTo('CreatedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'foreignKey' => 'created_user_id'
		]);
		//$this->hasMany('SurveyQuestions', ['className' => 'Survey.SurveyQuestions']);
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

	public function getList() {
		$result = $this->find('list', [
			'conditions' => [
				$this->alias().'.visible' => 1
			],
			'order' => [
				$this->alias().'.order'
			]
		]);
		$list = $result->toArray();

		return $list;
	}
}
