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
        $controller = $this->name;
        $this->loadComponent('FieldOption.FieldOption');
        $this->ControllerAction->model('FieldOption.FieldOptionValues', ['!search'], ['deleteStrategy' => 'transfer']);


        // $this->request->addParams([
        //     'accessMap' => [
        //         "$controller.Genders"                         => "$controller.%s",
        //         "$controller.Localities"                     => "$controller.%s",
        //         "$controller.Ownerships"                     => "$controller.%s",
        //         "$controller.Providers"                     => "$controller.%s",
        //         "$controller.Sectors"                         => "$controller.%s",
        //         "$controller.Statuses"                         => "$controller.%s",
        //         "$controller.Types"                         => "$controller.%s",
        //         "$controller.NetworkConnectivities"         => "$controller.%s",
        //         "$controller.StaffPositionGrades"             => "$controller.%s",
        //         "$controller.StaffPositionTitles"             => "$controller.%s",
        //         "$controller.AllergyTypes"                     => "$controller.%s",
        //         "$controller.ConsultationTypes"             => "$controller.%s",
        //         "$controller.Relationships"                 => "$controller.%s",
        //         "$controller.Conditions"                     => "$controller.%s",
        //         "$controller.ImmunizationTypes"             => "$controller.%s",
        //         "$controller.TestTypes"                     => "$controller.%s",
        //         "$controller.QualityVisitTypes"             => "$controller.%s",
        //         "$controller.InfrastructureOwnerships"         => "$controller.%s",
        //         "$controller.InfrastructureConditions"         => "$controller.%s",
        //         "$controller.QualificationSpecialisations"     => "$controller.%s",
        //         "$controller.QualificationLevels"             => "$controller.%s",
        //         "$controller.FeeTypes"                         => "$controller.%s",
        //         "$controller.EmploymentTypes"                 => "$controller.%s",
        //         "$controller.ExtracurricularTypes"             => "$controller.%s",
        //         "$controller.IdentityTypes"                 => "$controller.%s",
        //         "$controller.Languages"                     => "$controller.%s",
        //         "$controller.LicenseTypes"                     => "$controller.%s",
        //         "$controller.SpecialNeedTypes"                 => "$controller.%s",
        //         "$controller.SpecialNeedDifficulties"         => "$controller.%s",
        //         "$controller.StaffAbsenceReasons"             => "$controller.%s",
        //         "$controller.StudentAbsenceReasons"         => "$controller.%s",
  //               "$controller.Nationalities"                 => "$controller.%s",
  //               "$controller.GuardianRelations"             => "$controller.%s"
        //     ]
        // ]);
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $header = 'Field Options';

        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
        $alias = $model->alias;
        $header = __('Field Options') . ' - ' . $model->getHeader($alias);

        $this->Navigation->addCrumb($model->getHeader($alias));

        $this->set('contentHeader', $header);
    }

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

}
