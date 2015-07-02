<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricStatusRolesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('RubricStatuses', ['className' => 'Rubric.SurveyStatuses']);
		$this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
	}
}
