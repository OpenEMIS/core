<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentsTable extends AppTable {
	public function initialize(array $config) {

		$this->hasMany('Institution.InstitutionSiteStudents');


		// $this->belongsTo('InstitutionSiteLocalities', ['className' => 'Institution.InstitutionSiteLocalities']);
		// $this->belongsTo('InstitutionSiteProviders', ['className' => 'Institution.InstitutionSiteProviders']);
		// $this->belongsTo('InstitutionSiteTypes', ['className' => 'Institution.InstitutionSiteTypes']);
		// $this->belongsTo('InstitutionSiteOwnerships', ['className' => 'Institution.InstitutionSiteOwnerships']);
		// $this->belongsTo('InstitutionSiteSectors', ['className' => 'Institution.InstitutionSiteSectors']);
		// $this->belongsTo('InstitutionSiteGenders', ['className' => 'Institution.InstitutionSiteGenders']);
		// $this->belongsTo('InstitutionSiteStatuses', ['className' => 'Institution.InstitutionSiteStatuses']);

		// // $this->hasMany('InstitutionSiteStudents');
		
		// $this->hasMany('InstitutionSiteAttachments', ['className' => 'Institution.InstitutionSiteAttachments']);
	}

	public function test() {
		pr('normal test');
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}
}
