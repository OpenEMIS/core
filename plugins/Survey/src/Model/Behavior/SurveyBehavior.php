<?php
namespace Survey\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class SurveyBehavior extends Behavior {
	protected $_defaultConfig = [
		'models' => [
			'CustomModules'	=> 'CustomField.CustomModules',
			'SurveyForms'	=> 'Survey.SurveyForms'
		]
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$models = $this->config('models');
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = TableRegistry::get($model);
				$this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->alias())) . '_id';
			} else {
				$this->{$key} = null;
			}
		}
	}

	public function getForms() {
		$module = $this->config('module');
		$customModule = $this->CustomModules
			->find('all')
			->select([
				$this->CustomModules->aliasField('id'),
				$this->CustomModules->aliasField('filter')
			])
			->where([
				$this->CustomModules->aliasField('model') => $module
			])
			->first();
		$customModuleId = $customModule->id;

		$surveyForms = $this->SurveyForms
			->find('list')
			->where([$this->SurveyForms->aliasField('custom_module_id') => $customModuleId])
			->toArray();

		return $surveyForms;
	}
}
