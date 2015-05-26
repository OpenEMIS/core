<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyModulesTable extends AppTable {
	public function initialize(array $config) {
		$this->hasMany('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
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
