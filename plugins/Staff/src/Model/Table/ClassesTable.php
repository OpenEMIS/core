<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_staff');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('InstitutionSiteClassStudents', ['className' => 'Institution.InstitutionSiteClassStudents']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}
