<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_students');

		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
	}
}