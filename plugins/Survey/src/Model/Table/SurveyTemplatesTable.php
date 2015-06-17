<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFormsTable;
use Cake\Validation\Validator;

class SurveyTemplatesTable extends CustomFormsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFields', [
			'className' => 'Survey.SurveyQuestions',
			'joinTable' => 'survey_template_questions',
			'foreignKey' => 'survey_template_id',
			'targetForeignKey' => 'survey_question_id'
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'custom_module_id']],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->CustomModules->find('list')->where([$this->CustomModules->aliasField('parent_id') => 0])->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		return compact('moduleOptions', 'selectedModule');
	}
}
