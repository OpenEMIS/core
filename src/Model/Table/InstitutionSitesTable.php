<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class InstitutionSitesTable extends Table {
	public function initialize(array $config) {
		//$this->table('institution_sites');

		$this->belongsTo('InstitutionSiteLocalities');
		$this->belongsTo('InstitutionSiteProviders');
		$this->belongsTo('InstitutionSiteTypes');
		$this->belongsTo('InstitutionSiteOwnerships');
		$this->belongsTo('InstitutionSiteSectors');
		$this->belongsTo('InstitutionSiteGenders');
		$this->belongsTo('InstitutionSiteStatuses');

		$this->hasMany('InstitutionSiteStudents');
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}
}
