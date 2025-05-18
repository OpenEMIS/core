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

	public function initialize(array $config): void {
		parent::initialize($config);
		$models = $this->getConfig('models');
		foreach ($models as $key => $model) {
			if (!is_null($model)) {
				$this->{$key} = TableRegistry::get($model);
				$this->{lcfirst($key).'Key'} = Inflector::underscore(Inflector::singularize($this->{$key}->getAlias())) . '_id';
			} else {
				$this->{$key} = null;
			}
		}
	}

	public function getForms($surveyFormId = null) {
		$module = $this->getConfig('module');
		$customModule = $this->CustomModules
			->find('all')
			->select([
				$this->CustomModules->aliasField('id')
			])
			->where([
				$this->CustomModules->aliasField('model') => $module
			])
			->first();
		$customModuleId = $customModule->id;

		$condition = [$this->SurveyForms->aliasField('custom_module_id') => $customModuleId];
		if (!is_null($surveyFormId)) {
			$condition[] = [$this->SurveyForms->aliasField('id') => $surveyFormId];
		}

		$surveyForms = $this->SurveyForms
			->find('list')
			->where($condition)
			->toArray();

		return $surveyForms;
	}
}
