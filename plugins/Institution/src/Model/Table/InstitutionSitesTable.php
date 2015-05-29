<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSitesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteLocalities', ['className' => 'Institution.InstitutionSiteLocalities']);
		$this->belongsTo('InstitutionSiteProviders', ['className' => 'Institution.InstitutionSiteProviders']);
		$this->belongsTo('InstitutionSiteTypes', ['className' => 'Institution.InstitutionSiteTypes']);
		$this->belongsTo('InstitutionSiteOwnerships', ['className' => 'Institution.InstitutionSiteOwnerships']);
		$this->belongsTo('InstitutionSiteSectors', ['className' => 'Institution.InstitutionSiteSectors']);
		$this->belongsTo('InstitutionSiteGenders', ['className' => 'Institution.InstitutionSiteGenders']);
		$this->belongsTo('InstitutionSiteStatuses', ['className' => 'Institution.InstitutionSiteStatuses']);

		$this->belongsTo('Areas', ['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

		// $this->hasMany('InstitutionSiteStudents');
		
		$this->hasMany('InstitutionSiteAttachments', ['className' => 'Institution.InstitutionSiteAttachments']);
		$this->hasMany('InstitutionSiteCustomFields', ['className' => 'Institution.InstitutionSiteCustomFields']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		if ($this->action == 'index') {
			$this->Session->delete('InstitutionSites.id');
			$this->fields['alternative_name']['visible']['index'] = false;
			$this->fields['address']['visible']['index'] = false;
			$this->fields['postal_code']['visible']['index'] = false;
			$this->fields['telephone']['visible']['index'] = false;
			$this->fields['fax']['visible']['index'] = false;
			$this->fields['email']['visible']['index'] = false;
			$this->fields['website']['visible']['index'] = false;
			$this->fields['date_opened']['visible']['index'] = false;
			$this->fields['date_closed']['visible']['index'] = false;
			$this->fields['longitude']['visible']['index'] = false;
			$this->fields['latitude']['visible']['index'] = false;
			$this->fields['contact_person']['visible']['index'] = false;
		}

		$this->fields['year_opened']['visible'] = false;
		$this->fields['year_closed']['visible'] = false;
		$this->fields['security_group_id']['visible'] = false;

		$this->fields['institution_site_locality_id']['type'] = 'select';
		$this->fields['institution_site_type_id']['type'] = 'select';
		$this->fields['institution_site_ownership_id']['type'] = 'select';
		$this->fields['institution_site_status_id']['type'] = 'select';
		$this->fields['institution_site_sector_id']['type'] = 'select';
		$this->fields['institution_site_provider_id']['type'] = 'select';
		$this->fields['institution_site_gender_id']['type'] = 'select';
	}

}
