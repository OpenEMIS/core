<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;

class EducationLevelIscedTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels', 'cascadeCallbacks' => true]);
	}
}
