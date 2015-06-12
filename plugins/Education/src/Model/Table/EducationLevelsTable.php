<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;

class EducationLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationLevelIsced', ['className' => 'Education.EducationLevelIsced']);
		$this->belongsTo('EducationSystems', ['className' => 'Education.EducationSystems']);
		$this->hasMany('EducationCycles', ['className' => 'Education.EducationCycles']);
	}

	public function findWithSystem(Query $query, array $options) {
		return $query
			->contain(['EducationSystems'])
			->order(['EducationSystems.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}
}
