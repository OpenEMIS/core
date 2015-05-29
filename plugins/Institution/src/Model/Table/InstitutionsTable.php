<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_sites');
        parent::initialize($config);

		$this->belongsTo('InstitutionSiteLocalities', ['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', ['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', ['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', ['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', ['className' => 'Institution.Sectors']);
		$this->belongsTo('InstitutionSiteProviders', ['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', ['className' => 'Institution.Genders']);

		$this->belongsTo('Areas', ['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

		// $this->hasMany('InstitutionSiteStudents');
		
		$this->hasMany('Attachments', ['className' => 'Institution.Attachments']);
		$this->hasMany('Additional', ['className' => 'Institution.Additional']);

		$this->hasMany('Programmes', ['className' => 'Institution.Programmes']);
		$this->hasMany('Shifts', ['className' => 'Institution.Shifts']);
		$this->hasMany('Sections', ['className' => 'Institution.Sections']);
		$this->hasMany('Classes', ['className' => 'Institution.Classes']);
		$this->hasMany('Infrastructures', ['className' => 'Institution.Infrastructures']);

		$this->hasMany('StaffAbsences', ['className' => 'Institution.StaffAbsences']);
		$this->hasMany('StudentAbsences', ['className' => 'Institution.StudentAbsences']);

		$this->hasMany('StaffBehaviours', ['className' => 'Institution.StaffBehaviours']);
		$this->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours']);

		$this->hasMany('BankAccounts', ['className' => 'Institution.BankAccounts']);
		$this->hasMany('Fees', ['className' => 'Institution.BankAccounts']);

		$this->hasMany('NewSurveys', ['className' => 'Institution.SurveyNew']);
		$this->hasMany('InstitutionSiteSurveyDrafts', ['className' => 'Institution.InstitutionSiteSurveyDrafts']);
		$this->hasMany('InstitutionSiteSurveyCompleted', ['className' => 'Institution.InstitutionSiteSurveyCompleted']);

		$this->hasMany('InstitutionSiteAssessmentResults', ['className' => 'Institution.InstitutionSiteAssessmentResults']);

		// $this->hasMany('InstitutionSitePositions', ['className' => 'Institution.InstitutionSitePositions']);

		// $this->hasMany('InstitutionSiteCustomFields', ['className' => 'Institution.InstitutionSiteCustomFields']);

	}

    public function test() {
    	die('chak '.$this->alias());
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
