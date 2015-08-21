<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class StaffTrainingCategoriesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		
		$this->belongsTo('FieldOptions', ['className' => 'FieldOptions']);
		$this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'dependent' => true]);
	}
}
