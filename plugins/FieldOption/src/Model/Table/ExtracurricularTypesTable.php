<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ExtracurricularTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('StaffExtracurricular', ['className' => 'Staff.Extracurriculars', 'foreignKey' => 'extracurricular_type_id']);
		$this->hasMany('StudentExtracurricular', ['className' => 'Student.Extracurriculars', 'foreignKey' => 'extracurricular_type_id']);
	}
}
