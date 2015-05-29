<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		// 'Students.Student',
		// 'Students.StudentStatus',
		// 'InstitutionSiteProgramme' => array(
		// 	'className' => 'InstitutionSiteProgramme',
		// 	'foreignKey' => false,
		// 	'conditions' => array(
		// 		'InstitutionSiteProgramme.institution_site_id = InstitutionSiteStudent.institution_site_id',
		// 		'InstitutionSiteProgramme.education_programme_id = InstitutionSiteStudent.education_programme_id'
		// 	)
		// ),
		// 'EducationProgramme',
		// 'InstitutionSite'

		// $this->belongsTo('Students', ['className' => 'Student.Students']);
		// $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
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