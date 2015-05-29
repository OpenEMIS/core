]<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InsitutionSiteStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		// $this->belongsTo('InstitutionSiteProgramme', ['className' => 'Institution.InstitutionSiteProgrammes', 'foreignKey' => false, 'conditions' => [
		// 			'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
		// 			'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
		// 				]
		// 		]
		// 	);
		// $this->belongsTo('EducationProgrammes', ['className' => 'Institution.EducationProgrammes']);
		// $this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}
}