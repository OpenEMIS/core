<?php
namespace FieldOption\Controller;

use ArrayObject;
use FieldOption\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;

class FieldOptionsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('FieldOption.FieldOption');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $header = 'Field Options';
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'index']);
        $session = $this->request->session();
        $action = $this->request->params['action'];

        $this->set('contentHeader', __($header));
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $alias = $model->alias;
        //POCOR-5890 starts
        if($alias == 'ImmunizationTypes'){
            $alias = __('Vaccinations Type');     
        }
        //POCOR-5890 ends
        $header = __('Field Options') . ' - ' . $model->getHeader($alias);

        $this->Navigation->addCrumb(str_replace ('Wash', 'WASH', $model->getHeader($alias)));

        $this->set('contentHeader', str_replace ('Wash', 'WASH', $header));
    }

    public function index()
    {
        return $this->redirect(['plugin' => $this->plugin, 'controller' => $this->name, 'action' => key($this->FieldOption->getFieldOptions())]);
    }

    public function Duties()                        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Localities()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Ownerships()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Providers()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Sectors()                       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Types()                         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffPositionGrades()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffPositionTitles()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function AllergyTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ConsultationTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Relationships()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Conditions()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ImmunizationTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TestTypes()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InsuranceProviders()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InsuranceTypes()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualityVisitTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureOwnerships()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureConditions()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualificationTitles()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualificationLevels()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function QualificationSpecialisations()  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function FeeTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function BudgetTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function IncomeSources()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function IncomeTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ExpenditureTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function EmploymentStatusTypes()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ExtracurricularTypes()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function IdentityTypes()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Languages()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function LicenseTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function LicenseClassifications()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StudentAbsenceReasons()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Nationalities()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function GuardianRelations()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffTypes()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffLeaveTypes()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function BehaviourClassifications()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StudentBehaviourCategories()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StudentTransferReasons()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StudentWithdrawReasons()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffBehaviourCategories()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function StaffTrainingCategories()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Banks()                         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function BankBranches()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SalaryAdditionTypes()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SalaryDeductionTypes()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function Countries()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ContactTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ShiftOptions()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingAchievementTypes()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingCourseTypes()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingFieldStudies()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingLevels()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingModeDeliveries()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingNeedCategories()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingNeedCompetencies()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingNeedStandards()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingNeedSubStandards()      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingPriorities()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingProviders()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingRequirements()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingResultTypes()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TrainingSpecialisations()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function CommentTypes()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TextbookConditions()            { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ReportCardCommentCodes()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function GuidanceTypes()                 { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function VisitPurposeTypes()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureNeedTypes()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureProjectFundingSources()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterTypes ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterFunctionalities ()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterProximities ()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterQuantities ()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterQualities ()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWaterAccessibilities ()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSanitationTypes ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSanitationUses ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSanitationQualities ()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSanitationAccessibilities ()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashHygieneTypes ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashHygieneSoapashAvailabilities ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashHygieneEducations ()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWasteTypes ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashWasteFunctionalities ()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSewageTypes ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InfrastructureWashSewageFunctionalities ()   { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityElectricityTypes ()                  { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityElectricityConditions ()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityInternetTypes ()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityInternetConditions ()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityInternetBandwidths ()                { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityTelephoneTypes ()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function UtilityTelephoneConditions ()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TransportFeatures()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function BusTypes()                      { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function TripTypes()                     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipFundingSources()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipAttachmentTypes()    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipPaymentFrequencies() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipRecipientActivityStatuses()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipDisbursementCategories()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipSemesters()          { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function ScholarshipInstitutionChoices()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function InstitutionCommitteeTypes()                         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function AssetTypes()                    { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function AssetConditions()               { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsTypes()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsAssessmentsTypes()              { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsDifficulties()       { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsReferrerTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsServiceTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function SpecialNeedsDeviceTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function DemographicWealthQuantileTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    
    public function MealTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function MealTargets()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function MealNutritions()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    public function MealImplementers()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
    //student Meal benefit type POCOR-5692
    public function MealBenefitTypes()     { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => $this->FieldOption->getClassName(__FUNCTION__)]); }
}
