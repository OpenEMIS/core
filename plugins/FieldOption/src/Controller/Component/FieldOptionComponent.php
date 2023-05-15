<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace FieldOption\Controller\Component;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class FieldOptionComponent extends Component
{
    private $controller;
    
    private $fieldOptions = [
    // Institution
    //     'Localities' => ['className' => 'Institution.Localities', 'parent' => 'Institution'],
    //     'Duties' => ['className' => 'Institution.StaffDuties', 'parent' => 'Institution'],
    //     'Ownerships' => ['className' => 'Institution.Ownerships', 'parent' => 'Institution'],
    //     'Sectors' => ['className' => 'Institution.Sectors', 'parent' => 'Institution'],
    //     'Providers' => ['className' => 'Institution.Providers', 'parent' => 'Institution'],
    //     'Types' => ['className' => 'Institution.Types', 'parent' => 'Institution'],
    //     'Unit' => ['className' => 'Institution.Unit', 'parent' => 'Institution'],
    //     'Course' => ['className' => 'Institution.Course', 'parent' => 'Institution'],
    //     'ShiftOptions' => ['className' => 'Institution.ShiftOptions', 'parent' => 'Institution'],
    //     'TextbookConditions' => ['className' => 'Textbook.TextbookConditions', 'parent' => 'Institution'],
    //     'ReportCardCommentCodes' => ['className' => 'ReportCard.ReportCardCommentCodes', 'parent' => 'Institution'],
    //     'InstitutionCommitteeTypes' => ['className' => 'Institution.InstitutionCommitteeTypes', 'parent' => 'Institution'],
    //     'InstitutionAttachmentTypes' => ['className' => 'Institution.InstitutionAttachmentTypes', 'parent' => 'Institution'], //START:POCOR-5067

    // // Student
    //     'StudentAbsenceReasons' => ['className' => 'Institution.StudentAbsenceReasons', 'parent' => 'Student'],
    //     'StudentBehaviourCategories' => ['className' => 'Student.StudentBehaviourCategories', 'parent' => 'Student'],
    //     'StudentTransferReasons' => ['className' => 'Student.StudentTransferReasons', 'parent' => 'Student'],
    //     'StudentWithdrawReasons' => ['className' => 'Student.StudentWithdrawReasons', 'parent' => 'Student'],
    //     'GuidanceTypes' => ['className' => 'Student.GuidanceTypes', 'parent' => 'Student'],
    //     'VisitPurposeTypes' => ['className' => 'Student.StudentVisitPurposeTypes', 'parent' => 'Student'],
    //     'StudentAttachmentTypes' => ['className' => 'Student.StudentAttachmentTypes', 'parent' => 'Student'], //START:POCOR-5067

    // // Meals    
    //     'MealTypes' => ['className' => 'Meal.MealType', 'parent' => 'Meals'],
    //     'MealTargets' => ['className' => 'Meal.MealTarget', 'parent' => 'Meals'],
    //     'MealNutritions' => ['className' => 'Meal.MealNutritions', 'parent' => 'Meals'],
    //     'MealImplementers' => ['className' => 'Meal.MealImplementer', 'parent' => 'Meals'],
    //     'MealBenefitTypes' => ['className' => 'Meal.MealBenefit', 'parent' => 'Meals'],

    // // Staff
    //     'StaffBehaviourCategories' => ['className' => 'Staff.StaffBehaviourCategories', 'parent' => 'Staff'],
    //     'StaffLeaveTypes' => ['className' => 'Staff.StaffLeaveTypes', 'parent' => 'Staff'],
    //     'StaffTypes' => ['className' => 'Staff.StaffTypes', 'parent' => 'Staff'],
    //     'StaffTrainingCategories' => ['className' => 'Staff.StaffTrainingCategories', 'parent' => 'Staff'],
    //     'StaffAttachmentTypes' => ['className' => 'Staff.StaffAttachmentTypes', 'parent' => 'Staff'], //START:POCOR-5067
    //     'StaffPositionCategories' => ['className' => 'Staff.StaffPositionCategories', 'parent' => 'Staff'], //START:POCOR-6949

    // // Finance
    //     'Banks' => ['className' => 'FieldOption.Banks', 'parent' => 'Finance'],
    //     'BankBranches' => ['className' => 'FieldOption.BankBranches', 'parent' => 'Finance'],
    //     'FeeTypes' => ['className' => 'FieldOption.FeeTypes', 'parent' => 'Finance'],
    //     'BudgetTypes' => ['className' => 'FieldOption.BudgetTypes', 'parent' => 'Finance'],
    //     'IncomeSources' => ['className' => 'FieldOption.IncomeSources', 'parent' => 'Finance'],
    //     'IncomeTypes' => ['className' => 'FieldOption.IncomeTypes', 'parent' => 'Finance'],
    //     'ExpenditureTypes' => ['className' => 'FieldOption.ExpenditureTypes', 'parent' => 'Finance'],

    // // Guardian
    //     'GuardianRelations' => ['className' => 'Student.GuardianRelations', 'parent' => 'Guardian'],

    // // Position
    //     'StaffPositionGrades' => ['className' => 'Institution.StaffPositionGrades', 'parent' => 'Position'],
    //     'StaffPositionTitles' => ['className' => 'Institution.StaffPositionTitles', 'parent' => 'Position'],

    // // Qualification
    //     'QualificationLevels' => ['className' => 'FieldOption.QualificationLevels', 'parent' => 'Qualification'],
    //     'QualificationTitles' => ['className' => 'FieldOption.QualificationTitles', 'parent' => 'Qualification'],
    //     'QualificationSpecialisations' => ['className' => 'FieldOption.QualificationSpecialisations', 'parent' => 'Qualification'],

    // // Quality

    //     'QualityVisitTypes' => ['className' => 'FieldOption.QualityVisitTypes', 'parent' => 'Quality'],

    // // Salary
    //     'SalaryAdditionTypes' => ['className' => 'Staff.SalaryAdditionTypes', 'parent' => 'Salary'],
    //     'SalaryDeductionTypes' => ['className' => 'Staff.SalaryDeductionTypes', 'parent' => 'Salary'],

    // Training
        // 'TrainingAchievementTypes' => ['className' => 'Training.TrainingAchievementTypes', 'parent' => 'Training'],
        // 'TrainingCourseTypes' => ['className' => 'Training.TrainingCourseTypes', 'parent' => 'Training'],
        // 'TrainingFieldStudies' => ['className' => 'Training.TrainingFieldStudies', 'parent' => 'Training'],
        // 'TrainingLevels' => ['className' => 'Training.TrainingLevels', 'parent' => 'Training'],
        // 'TrainingModeDeliveries' => ['className' => 'Training.TrainingModeDeliveries', 'parent' => 'Training'],
        // 'TrainingNeedCategories' => ['className' => 'Training.TrainingNeedCategories', 'parent' => 'Training'],
        // 'TrainingNeedCompetencies' => ['className' => 'Training.TrainingNeedCompetencies', 'parent' => 'Training'],
        // 'TrainingNeedStandards' => ['className' => 'Training.TrainingNeedStandards', 'parent' => 'Training'],
        // 'TrainingNeedSubStandards' => ['className' => 'Training.TrainingNeedSubStandards', 'parent' => 'Training'],
        // 'TrainingPriorities' => ['className' => 'Training.TrainingPriorities', 'parent' => 'Training'],
        // 'TrainingProviders' => ['className' => 'Training.TrainingProviders', 'parent' => 'Training'],
        // 'TrainingRequirements' => ['className' => 'Training.TrainingRequirements', 'parent' => 'Training'],
        /* START: POCOR-6391
        'TrainingResultTypes' => ['className' => 'Training.TrainingResultTypes', 'parent' => 'Training'],
        /* END: POCOR-6391  */
        // 'TrainingSpecialisations' => ['className' => 'Training.TrainingSpecialisations', 'parent' => 'Training'],
        // 'TrainingCourseCategories' => ['className' => 'Training.TrainingCourseCategories', 'parent' => 'Training'],//POCOR-5695 add Training Courses Categories

    // Others
        // 'ContactTypes' => ['className' => 'User.ContactTypes', 'parent' => 'Others'],
        // 'EmploymentStatusTypes' => ['className' => 'FieldOption.EmploymentStatusTypes', 'parent' => 'Others'],
        // 'ExtracurricularTypes' => ['className' => 'FieldOption.ExtracurricularTypes', 'parent' => 'Others'],
        // 'IdentityTypes' => ['className' => 'FieldOption.IdentityTypes', 'parent' => 'Others'],
        // 'Languages' => ['className' => 'Languages', 'parent' => 'Others'],
        // 'LicenseTypes' => ['className' => 'FieldOption.LicenseTypes', 'parent' => 'Others'],
        // 'LicenseClassifications' => ['className' => 'FieldOption.LicenseClassifications', 'parent' => 'Others'],
        // 'Countries' => ['className' => 'FieldOption.Countries', 'parent' => 'Others'],
        // 'Nationalities' => ['className' => 'FieldOption.Nationalities', 'parent' => 'Others'],
        // 'CommentTypes' => ['className' => 'User.CommentTypes', 'parent' => 'Others'],
        // 'BehaviourClassifications' => ['className' => 'Student.BehaviourClassifications', 'parent' => 'Others'],
        // 'DemographicWealthQuantileTypes' => ['className' => 'FieldOption.DemographicTypes', 'parent' => 'Others'],

    // Infrastructure
        // 'InfrastructureOwnerships' => ['className' => 'FieldOption.InfrastructureOwnerships', 'parent' => 'Infrastructure'],
        // 'InfrastructureConditions' => ['className' => 'FieldOption.InfrastructureConditions', 'parent' => 'Infrastructure'],
        // 'InfrastructureNeedTypes' => ['className' => 'Institution.InfrastructureNeedTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureProjectFundingSources' => ['className' => 'Institution.InfrastructureProjectFundingSources', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterTypes' => ['title' => 'Infrastructure WASH Water Types', 'className' => 'Institution.InfrastructureWashWaterTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterFunctionalities' => ['title' => 'Infrastructure WASH Water Functionalities', 'className' => 'Institution.InfrastructureWashWaterFunctionalities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterProximities' => ['title' => 'Infrastructure WASH Water Proximities', 'className' => 'Institution.InfrastructureWashWaterProximities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterQuantities' => ['title' => 'Infrastructure WASH Water Quantities', 'className' => 'Institution.InfrastructureWashWaterQuantities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterQualities' => ['title' => 'Infrastructure WASH Water Qualities','className' => 'Institution.InfrastructureWashWaterQualities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWaterAccessibilities' => ['title' => 'Infrastructure WASH Water Accessibilities', 'className' => 'Institution.InfrastructureWashWaterAccessibilities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSanitationTypes' => ['title' => 'Infrastructure WASH Sanitation Types', 'className' => 'Institution.InfrastructureWashSanitationTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSanitationUses' => ['title' => 'Infrastructure WASH Sanitation Uses', 'className' => 'Institution.InfrastructureWashSanitationUses', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSanitationQualities' => ['title' => 'Infrastructure WASH Sanitation Qualities', 'className' => 'Institution.InfrastructureWashSanitationQualities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSanitationAccessibilities' => ['title' => 'Infrastructure WASH Sanitation Accessibilities', 'className' => 'Institution.InfrastructureWashSanitationAccessibilities', 'parent' => 'Infrastructure'],

        // 'InfrastructureWashHygieneTypes' => ['title' => 'Infrastructure WASH Hygiene Types', 'className' => 'Institution.InfrastructureWashHygieneTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashHygieneSoapashAvailabilities' => ['title' => 'Infrastructure WASH Hygiene Soap/Ash Availabilities', 'className' => 'Institution.InfrastructureWashHygieneSoapashAvailabilities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashHygieneEducations' => ['title' => 'Infrastructure WASH Hygiene Educations', 'className' => 'Institution.InfrastructureWashHygieneEducations', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWasteTypes' => ['title' => 'Infrastructure WASH Waste Types', 'className' => 'Institution.InfrastructureWashWasteTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashWasteFunctionalities' => ['title' => 'Infrastructure WASH Waste Functionalities', 'className' => 'Institution.InfrastructureWashWasteFunctionalities', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSewageTypes' => ['title' => 'Infrastructure WASH Sewage Types', 'className' => 'Institution.InfrastructureWashSewageTypes', 'parent' => 'Infrastructure'],
        // 'InfrastructureWashSewageFunctionalities' => ['title' => 'Infrastructure WASH Sewage Functionalities', 'className' => 'Institution.InfrastructureWashSewageFunctionalities', 'parent' => 'Infrastructure'],
        // 'UtilityElectricityTypes' => ['className' => 'Institution.UtilityElectricityTypes', 'parent' => 'Infrastructure'],
        // 'UtilityElectricityConditions' => ['className' => 'Institution.UtilityElectricityConditions', 'parent' => 'Infrastructure'],
        // 'UtilityInternetTypes' => ['className' => 'Institution.UtilityInternetTypes', 'parent' => 'Infrastructure'],
        // 'UtilityInternetConditions' => ['className' => 'Institution.UtilityInternetConditions', 'parent' => 'Infrastructure'],
        // 'UtilityInternetBandwidths' => ['className' => 'Institution.UtilityInternetBandwidths', 'parent' => 'Infrastructure'],
        // 'UtilityTelephoneTypes' => ['className' => 'Institution.UtilityTelephoneTypes', 'parent' => 'Infrastructure'],
        // 'UtilityTelephoneConditions' => ['className' => 'Institution.UtilityTelephoneConditions', 'parent' => 'Infrastructure'],
        // 'AssetTypes' => ['className' => 'Institution.AssetTypes', 'parent' => 'Infrastructure'],
        // 'AssetConditions' => ['className' => 'Institution.AssetConditions', 'parent' => 'Infrastructure'],

    // Health
        // 'AllergyTypes' => ['className' => 'Health.AllergyTypes', 'parent' => 'Health'],
        // 'Conditions' => ['className' => 'Health.Conditions', 'parent' => 'Health'],
        // 'ConsultationTypes' => ['className' => 'Health.ConsultationTypes', 'parent' => 'Health'],
        // 'ImmunizationTypes' => ['title' => 'Vaccinations', 'className' => 'Health.ImmunizationTypes', 'parent' => 'Health'],
        // 'Relationships' => ['className' => 'Health.Relationships', 'parent' => 'Health'],
        // 'TestTypes' => ['className' => 'Health.TestTypes', 'parent' => 'Health'],
        // 'InsuranceProviders' => ['className' => 'Health.InsuranceProviders', 'parent' => 'Health'],
        // 'InsuranceTypes' => ['className' => 'Health.InsuranceTypes', 'parent' => 'Health'],

    // // Transport
    //     'TransportFeatures' => ['className' => 'Transport.TransportFeatures', 'parent' => 'Transport'],
    //     'BusTypes' => ['className' => 'Transport.BusTypes', 'parent' => 'Transport'],
    //     'TripTypes' => ['className' => 'Transport.TripTypes', 'parent' => 'Transport'],

    // // Scholarship
    //     'ScholarshipFundingSources' => ['className' => 'Scholarship.FundingSources', 'parent' => 'Scholarship'],
    //     'ScholarshipAttachmentTypes' => ['className' => 'Scholarship.AttachmentTypes', 'parent' => 'Scholarship'],
    //     'ScholarshipPaymentFrequencies' => ['className' => 'Scholarship.PaymentFrequencies', 'parent' => 'Scholarship'],
    //     'ScholarshipRecipientActivityStatuses' => ['className' => 'Scholarship.RecipientActivityStatuses', 'parent' => 'Scholarship'],
    //     'ScholarshipDisbursementCategories' => ['className' => 'Scholarship.DisbursementCategories', 'parent' => 'Scholarship'],
    //     'ScholarshipSemesters' => ['className' => 'Scholarship.Semesters', 'parent' => 'Scholarship'],
    //     'ScholarshipInstitutionChoices' => ['className' => 'Scholarship.InstitutionChoiceTypes', 'parent' => 'Scholarship'],
    //     'ScholarshipFinancialAssistances' => ['className' => 'Scholarship.ScholarshipFinancialAssistances', 'parent' => 'Scholarship'], //POCOR-6839

    // // Special Needs
    //     'SpecialNeedsTypes' => ['className' => 'SpecialNeeds.SpecialNeedsTypes', 'parent' => 'Special Needs'],
    //     'SpecialNeedsAssessmentsTypes' => ['className' => 'SpecialNeeds.SpecialNeedsAssessmentsTypes', 'parent' => 'Special Needs'],
    //     'SpecialNeedsDifficulties' => ['className' => 'SpecialNeeds.SpecialNeedsDifficulties', 'parent' => 'Special Needs'],
    //     'SpecialNeedsReferrerTypes' => ['className' => 'SpecialNeeds.SpecialNeedsReferrerTypes', 'parent' => 'Special Needs'],
    //     'SpecialNeedsServiceTypes' => ['className' => 'SpecialNeeds.SpecialNeedsServiceTypes', 'parent' => 'Special Needs'],

    //     'SpecialNeedsDeviceTypes' => ['className' => 'SpecialNeeds.SpecialNeedsDeviceTypes', 'parent' => 'Special Needs'],
    //     'PlanTypes' => ['className' => 'SpecialNeeds.SpecialNeedsPlanTypes', 'parent' => 'Special Needs'],   //POCOR-6873
    //     'DiagnosticTypeOfDisability' => ['className' => 'SpecialNeeds.SpecialNeedsDiagnosticsTypes', 'parent' => 'Special Needs'],  //POCOR-6873
    //     'DiagnosticDisabilityDegree' => ['className' => 'SpecialNeeds.SpecialNeedsDiagnosticsDegree', 'parent' => 'Special Needs'],  //POCOR-6873
    //     // 'DiagnosticsLevels' => ['className' => 'SpecialNeeds.SpecialNeedsDiagnosticsLevels', 'parent' => 'Special Needs'],  //POCOR-6873

    //     'SpecialNeedsServiceClassification' => ['className' => 'SpecialNeeds.SpecialNeedsServiceClassification', 'parent' => 'Special Needs'] //POCOR-6873
    ];

    public $components = ['AccessControl'];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)   
    {
        foreach ($this->fieldOptions as $key => $className) {
            $this->AccessControl->addAccessMap($key);
        }
    }

    public function getFieldOptions()
    {
        $FieldOptionTable = TableRegistry::get('field_options');
        $FieldOptions = $FieldOptionTable->find('all')->toArray();
        $option = [];
        foreach($FieldOptions as $key => $FieldOption1 ){
            $a = $FieldOption1->name;
            $search = 'Wash';
            if(preg_match("/{$search}/i", $a)) {
                $title = str_replace('Wash','WASH',$FieldOption1->name);
                $option[str_replace(' ','',$FieldOption1->name)] = [
                    "title" => $title,
                    "className"=> str_replace('_', '', ucwords($FieldOption1->table_name, '_')),
                    "parent" => $FieldOption1->category
                ];
                if($option["InfrastructureWashHygieneSoapashAvailabilities"]){
                    $option['InfrastructureWashHygieneSoapashAvailabilities']['title'] = "Infrastructure WASH Hygiene Soap/Ash Availabilities";
                }
            }else{
                $option[str_replace(' ','',$FieldOption1->name)] = [
                    "className"=> str_replace('_', '', ucwords($FieldOption1->table_name, '_')),
                    "parent" => $FieldOption1->category
                ];
            }
            
        }
        if($option["ImmunizationTypes"]){
            $option['ImmunizationTypes']['title'] = "Vaccinations";
        }
       return $option;
    }

    public function getClassName($key)
    {  
        $FieldOptionTable = TableRegistry::get('field_options');
        $Words = trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $key));
        //echo $key;die;
        $FieldOptions = $FieldOptionTable->find('all',['conditions'=>['name' => $Words]])->first();
        if( $FieldOptions->category == "Finance"){
            $FieldOptions->category = "FieldOption";
        }elseif( $FieldOptions->category == "Qualification"){
            $FieldOptions->category = "FieldOption";
        }elseif( $FieldOptions->category == "Quality"){
            $FieldOptions->category = "FieldOption";
        }elseif( $FieldOptions->category == "Others"){
            $FieldOptions->category = "FieldOption";
        }elseif( $FieldOptions->category == "Infrastructure"){
            $FieldOptions->category = "Institution";
        }

        if($key == "Duties"){
            return "Institution.StaffDuties";
        }elseif($key== "TextbookConditions" ){
            return "Textbook.TextbookConditions";

        }elseif($key== "ReportCardCommentCodes" ){
            return "ReportCard.ReportCardCommentCodes";
        }elseif($key== "StudentAbsenceReasons" ){
            return "Institution.StudentAbsenceReasons";
        }elseif($key== "VisitPurposeTypes" ){
            return "Student.StudentVisitPurposeTypes";
        }elseif($key== "MealTypes" ){
            return "Meal.MealType";
        }elseif($key== "MealTargets" ){
            return "Meal.MealTarget";
        }elseif($key== "MealNutritions" ){
            return "Meal.MealNutritions";
        }elseif($key== "MealImplementers" ){
            return "Meal.MealImplementer";
        }elseif($key== "MealBenefitTypes" ){
            return "Meal.MealBenefit";
        }elseif($key== "GuardianRelations" ){
            return "Student.GuardianRelations";
        }elseif($key== "StaffPositionGrades" ){
            return "Institution.StaffPositionGrades";
        }elseif($key== "StaffPositionTitles" ){
            return "Institution.StaffPositionTitles";
        }elseif($key== "SalaryAdditionTypes" ){
            return "Staff.SalaryAdditionTypes";
        }elseif($key== "SalaryDeductionTypes" ){
            return "Staff.SalaryDeductionTypes";
        }elseif($key== "ContactTypes" ){
            return "User.ContactTypes";
        }elseif($key== "Languages" ){
            return "Languages";
        }elseif($key== "LanguageProficiencies" ){
            return "User.LanguageProficiencies";
        }elseif($key== "CommentTypes" ){
            return "User.CommentTypes";
        }elseif($key== "BehaviourClassifications" ){
            return "Student.BehaviourClassifications";
        }elseif($key== "DemographicWealthQuantileTypes" ){
            return "FieldOption.DemographicTypes";

        }elseif($key== "ScholarshipFundingSources" ){
            return "Scholarship.FundingSources";
        }elseif($key== "ScholarshipAttachmentTypes" ){
            return "Scholarship.AttachmentTypes";
        }elseif($key== "ScholarshipPaymentFrequencies" ){
            return "Scholarship.PaymentFrequencies";
        }elseif($key== "ScholarshipRecipientActivityStatuses" ){
            return "Scholarship.RecipientActivityStatuses";
        }elseif($key== "InfrastructureWashSewageFunctionalities" ){
            return "Institution.InfrastructureWashSewageFunctionalities";
        }elseif($key== "ScholarshipFundingSources" ){
            return "Scholarship.FundingSources";
        }elseif($key== "ScholarshipAttachmentTypes" ){
            return "Scholarship.AttachmentTypes";
        }elseif($key== "ScholarshipPaymentFrequencies" ){
            return "Scholarship.PaymentFrequencies";
        }elseif($key== "ScholarshipRecipientActivityStatuses" ){
            return "Scholarship.RecipientActivityStatuses";
        }elseif($key== "ScholarshipDisbursementCategories" ){
            return "Scholarship.DisbursementCategories";
        }elseif($key== "ScholarshipSemesters" ){
            return "Scholarship.Semesters";
        }elseif($key== "ScholarshipInstitutionChoices" ){
            return "Scholarship.InstitutionChoiceTypes";


        }elseif($key== "InfrastructureOwnerships" ){
            return "FieldOption.InfrastructureOwnerships";
        }elseif($key== "InfrastructureConditions" ){
            return "FieldOption.InfrastructureConditions";
        }elseif($key== "PlanTypes" ){
            return "SpecialNeeds.SpecialNeedsPlanTypes";
        }elseif($key== "DiagnosticTypeOfDisability" ){
            return "SpecialNeeds.SpecialNeedsDiagnosticsTypes";
        }elseif($key== "DiagnosticDisabilityDegree" ){
            return "SpecialNeeds.SpecialNeedsDiagnosticsDegree";
        }else{ 
           $className =  $FieldOptions->category.".".$key;
           return $className;
         }
        return $this->fieldOptions[$key]['className'];
    }
}
