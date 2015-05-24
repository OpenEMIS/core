<?php
namespace Institution\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InstitutionSitesTable extends Table {
	public function initialize(array $config) {

		$this->belongsTo('InstitutionSiteLocalities', ['className' => 'Institution.InstitutionSiteLocalities']);
		$this->belongsTo('InstitutionSiteProviders', ['className' => 'Institution.InstitutionSiteProviders']);
		$this->belongsTo('InstitutionSiteTypes', ['className' => 'Institution.InstitutionSiteTypes']);
		$this->belongsTo('InstitutionSiteOwnerships', ['className' => 'Institution.InstitutionSiteOwnerships']);
		$this->belongsTo('InstitutionSiteSectors', ['className' => 'Institution.InstitutionSiteSectors']);
		$this->belongsTo('InstitutionSiteGenders', ['className' => 'Institution.InstitutionSiteGenders']);
		$this->belongsTo('InstitutionSiteStatuses', ['className' => 'Institution.InstitutionSiteStatuses']);

		// $this->hasMany('InstitutionSiteStudents');
		
		$this->hasMany('InstitutionSiteAttachments', ['className' => 'Institution.InstitutionSiteAttachments']);
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}
}
