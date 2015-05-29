<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffPositionGradesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
        parent::initialize($config);
        $this->addBehavior('FieldOptionValues');
		
		$this->hasMany('Positions', ['className' => 'Institution.Positions']);
	}
}
