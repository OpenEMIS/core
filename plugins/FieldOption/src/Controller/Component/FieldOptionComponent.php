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
    
    private $fieldOptions = [];

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
        //POCOR-7363 start
        }elseif($key== "FoodTypes"){
            return "Meal.FoodTypes";
        }elseif($key== "MealRatings" ){
            return "Meal.MealRatings";
         //POCOR-7363 end
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
