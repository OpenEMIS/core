<?php
namespace FieldOption\Controller;

use ArrayObject;
use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

class FieldOptionsController extends AppController {
    public function initialize() {
        parent::initialize();
        $this->loadComponent('FieldOption.FieldOption');
        $this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search'], ['deleteStrategy' => 'transfer']);
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = 'Field Options';

<<<<<<< HEAD
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];
=======
		$controller = $this->name;
		$this->request->addParams([
			'accessMap' => [
				"$controller.Genders" 						=> "$controller.%s",
				"$controller.Localities" 					=> "$controller.%s",
				"$controller.Ownerships" 					=> "$controller.%s",
				"$controller.Providers" 					=> "$controller.%s",
				"$controller.Sectors" 						=> "$controller.%s",
				"$controller.Statuses" 						=> "$controller.%s",
				"$controller.Types" 						=> "$controller.%s",
				"$controller.NetworkConnectivities" 		=> "$controller.%s",
				"$controller.StaffPositionGrades" 			=> "$controller.%s",
				"$controller.StaffPositionTitles" 			=> "$controller.%s",
				"$controller.AllergyTypes" 					=> "$controller.%s",
				"$controller.ConsultationTypes" 			=> "$controller.%s",
				"$controller.Relationships" 				=> "$controller.%s",
				"$controller.Conditions" 					=> "$controller.%s",
				"$controller.ImmunizationTypes" 			=> "$controller.%s",
				"$controller.TestTypes" 					=> "$controller.%s",
				"$controller.QualityVisitTypes" 			=> "$controller.%s",
				"$controller.InfrastructureOwnerships" 		=> "$controller.%s",
				"$controller.InfrastructureConditions" 		=> "$controller.%s",
				"$controller.QualificationSpecialisations" 	=> "$controller.%s",
				"$controller.QualificationLevels" 			=> "$controller.%s",
				"$controller.FeeTypes" 						=> "$controller.%s",
				"$controller.EmploymentTypes" 				=> "$controller.%s",
				"$controller.ExtracurricularTypes" 			=> "$controller.%s",
				"$controller.IdentityTypes" 				=> "$controller.%s",
				"$controller.Languages" 					=> "$controller.%s",
				"$controller.LicenseTypes" 					=> "$controller.%s",
				"$controller.SpecialNeedTypes" 				=> "$controller.%s",
				"$controller.SpecialNeedDifficulties" 		=> "$controller.%s",
				"$controller.StaffAbsenceReasons" 			=> "$controller.%s",
				"$controller.StudentAbsenceReasons" 		=> "$controller.%s",
                "$controller.Nationalities" 				=> "$controller.%s",
                "$controller.ShiftOptions" 					=> "$controller.%s"
			]
		]);
	}
>>>>>>> origin/POCOR-2602-dev

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $alias = $model->alias;
        $header = __('Field Options') . ' - ' . $model->getHeader($alias);

        $this->Navigation->addCrumb($model->getHeader($alias));

        $this->set('contentHeader', $header);
    }

<<<<<<< HEAD
    public function Genders()                       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Localities()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Ownerships()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Providers()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Sectors()                       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Statuses()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Types()                         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function NetworkConnectivities()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffPositionGrades()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffPositionTitles()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function AllergyTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ConsultationTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Relationships()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Conditions()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ImmunizationTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TestTypes()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualityVisitTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureOwnerships()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureConditions()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualificationSpecialisations()  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualificationLevels()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function FeeTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function EmploymentTypes()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ExtracurricularTypes()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function IdentityTypes()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Languages()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function LicenseTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedTypes()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedDifficulties()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffAbsenceReasons()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StudentAbsenceReasons()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Nationalities()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function GuardianRelations()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffTypes()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffLeaveTypes()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }

=======
	public function Genders() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Genders']); }
	public function Localities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Localities']); }
	public function Ownerships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Ownerships']); }
	public function Providers() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Providers']); }
	public function Sectors() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Sectors']); }
	public function Statuses() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Institution.Statuses']); }
	public function Types() { $this->ControllerAction->process(['alias' => __FUNCTION__, 						'className' => 'Institution.Types']); }
	public function NetworkConnectivities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 		'className' => 'Institution.NetworkConnectivities']); }
	public function StaffPositionGrades() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Institution.StaffPositionGrades']); }
	public function StaffPositionTitles() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Institution.StaffPositionTitles']); }
	public function AllergyTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'Health.AllergyTypes']); }
	public function ConsultationTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Health.ConsultationTypes']); }
	public function Relationships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'Health.Relationships']); }
	public function Conditions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Health.Conditions']); }
	public function ImmunizationTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'Health.ImmunizationTypes']); }
	public function TestTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Health.TestTypes']); }
	public function QualityVisitTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.QualityVisitTypes']); }
	public function InfrastructureOwnerships() { $this->ControllerAction->process(['alias' => __FUNCTION__, 	'className' => 'FieldOption.InfrastructureOwnerships']); }
	public function InfrastructureConditions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 	'className' => 'FieldOption.InfrastructureConditions']); }
	public function QualificationSpecialisations() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'FieldOption.QualificationSpecialisations']); }
	public function QualificationLevels() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.QualificationLevels']); }
	public function FeeTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'FieldOption.FeeTypes']); }
	public function EmploymentTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'FieldOption.EmploymentTypes']); }
	public function ExtracurricularTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 		'className' => 'FieldOption.ExtracurricularTypes']); }
	public function IdentityTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'FieldOption.IdentityTypes']); }
	public function Languages() { $this->ControllerAction->process(['alias' => __FUNCTION__, 					'className' => 'Languages']); }
	public function LicenseTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'FieldOption.LicenseTypes']); }
	public function SpecialNeedTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.SpecialNeedTypes']); }
	public function SpecialNeedDifficulties() { $this->ControllerAction->process(['alias' => __FUNCTION__, 		'className' => 'FieldOption.SpecialNeedDifficulties']); }
	public function StaffAbsenceReasons() { $this->ControllerAction->process(['alias' => __FUNCTION__, 			'className' => 'FieldOption.StaffAbsenceReasons']); }
	public function StudentAbsenceReasons() { $this->ControllerAction->process(['alias' => __FUNCTION__, 		'className' => 'FieldOption.StudentAbsenceReasons']); }
    public function Nationalities() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'FieldOption.Nationalities']); }
    public function ShiftOptions() { $this->ControllerAction->process(['alias' => __FUNCTION__, 				'className' => 'Institution.ShiftOptions']); }
>>>>>>> origin/POCOR-2602-dev
}
