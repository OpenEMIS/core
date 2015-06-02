<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionsTable extends AppTable  {
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
		
		$this->hasMany('Attachments', ['className' => 'Institution.InstitutionSiteAttachments']);
		$this->hasMany('Additional', ['className' => 'Institution.Additional']);

		$this->hasMany('Positions', ['className' => 'Institution.InstitutionSitePositions']);
		$this->hasMany('Programmes', ['className' => 'Institution.InstitutionSiteProgrammes']);
		$this->hasMany('Shifts', ['className' => 'Institution.InstitutionSiteShifts']);
		$this->hasMany('Sections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->hasMany('Classes', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('Infrastructures', ['className' => 'Institution.InstitutionSiteInfrastructures']);

		$this->hasMany('StaffAbsences', ['className' => 'Institution.InstitutionSiteStaffAbsences']);
		$this->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences']);

		$this->hasMany('StaffBehaviours', ['className' => 'Staff.StaffBehaviours']);
		$this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours']);

		$this->hasMany('BankAccounts', ['className' => 'Institution.InstitutionSiteBankAccounts']);
		$this->hasMany('Fees', ['className' => 'Institution.InstitutionSiteFees']);
		$this->hasMany('StudentFees', ['className' => 'Institution.StudentFees']);

		$this->hasMany('NewSurveys', ['className' => 'Institution.SurveyNew']);
		$this->hasMany('InstitutionSiteSurveyDrafts', ['className' => 'Institution.InstitutionSiteSurveyDrafts']);
		$this->hasMany('InstitutionSiteSurveyCompleted', ['className' => 'Institution.InstitutionSiteSurveyCompleted']);

		$this->hasMany('InstitutionSiteAssessmentResults', ['className' => 'Institution.InstitutionSiteAssessmentResults']);

		// $this->hasMany('InstitutionSitePositions', ['className' => 'Institution.InstitutionSitePositions']);

		// $this->hasMany('InstitutionSiteCustomFields', ['className' => 'Institution.InstitutionSiteCustomFields']);

	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.beforeAction'] = 'beforeAction';
		return $events;
	}

	public function beforeAction($event) {
		if ($this->action == 'index') {
			$this->Session->delete('Institutions.id');
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
