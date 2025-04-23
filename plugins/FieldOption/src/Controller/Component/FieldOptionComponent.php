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
    public function initialize(array $config): void
    {
        foreach ($this->fieldOptions as $key => $className) {
            $this->AccessControl->addAccessMap($key);
        }
    }

    public function getFieldOptions()
    {
        $FieldOptionTable = TableRegistry::get('FieldOption.FieldOptions');
        $FieldOptions = $FieldOptionTable->find('all')->toArray();
        $session = $this->getController()->getRequest()->getSession(); //POCOR-7396
        $FieldOptionPermissions = $session->read('Permissions.FieldOptions'); //POCOR-7396
        $option = [];
        foreach ($FieldOptions as $key => $FieldOption1) {
            $a = $FieldOption1->name;
            $search = 'Wash';
            if (preg_match("/{$search}/i", $a)) {
                $title = str_replace('Wash', 'WASH', $FieldOption1->name);
                $option[str_replace(' ', '', $FieldOption1->name)] = [
                    "title" => $title,
                    "className" => str_replace('_', '', ucwords($FieldOption1->table_name, '_')),
                    "parent" => $FieldOption1->category
                ];
                if ($option["InfrastructureWashHygieneSoapashAvailabilities"]) {
                    $option['InfrastructureWashHygieneSoapashAvailabilities']['title'] = "Infrastructure WASH Hygiene Soap/Ash Availabilities";
                }
            } else {
                $option[str_replace(' ', '', $FieldOption1->name)] = [
                    "className" => str_replace('_', '', ucwords($FieldOption1->table_name, '_')),
                    "parent" => $FieldOption1->category
                ];
            }
            //POCOR-7396 start
            $permissionName = str_replace(' ', '', $FieldOption1->name);
            if (!$session->check('Permissions.FieldOptions.' . $permissionName)) {
                $session->write('Permissions.FieldOptions.' . $permissionName, $FieldOptionPermissions);
            }
            //POCOR-7396 end
        }
        if ($option["ImmunizationTypes"]) {
            $option['ImmunizationTypes']['title'] = "Vaccinations";
        }
        return $option;
    }

    public function getClassName($key)
    {
        $FieldOptionTable = TableRegistry::get('FieldOption.FieldOptions');
        $Words = trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $key));
// echo $key;die;
        $FieldOptions = $FieldOptionTable->find('all', ['conditions' => ['name' => $Words]])->first();

// POCOR-8995 start
        $category = $FieldOptions->category ?? null;
// Normalize category
        $fieldOptionCategories = ["Finance",
            "Qualification",
            "Quality",
            "Others",
            "InfrastructureOwnerships",
            "InfrastructureConditions",
            "AssetMakes",
            "AssetModels",
            "ItemTypes",
            "StockUnits"
        ];
        if (in_array($key, $fieldOptionCategories)) { // POOCR_9074
            $category = "FieldOption";
        } elseif (in_array($category, $fieldOptionCategories)) {
            $category = "FieldOption";
        } elseif ($category === "Infrastructure") {
            $category = "Institution";
        } elseif ($category === "Special Needs") {
            $category = "SpecialNeeds";
        }

// Predefined key-to-class mappings
        $keyMappings = [
            // Cases
            "CasePriorities" => "Cases.CasePriorities",
            "CaseTypes" => "Cases.CaseTypes",

            // FieldOption
            "DemographicWealthQuantileTypes" => "FieldOption.DemographicTypes",

            // Institution
            "Duties" => "Institution.StaffDuties",
            "InfrastructureWashSewageFunctionalities" => "Institution.InfrastructureWashSewageFunctionalities",
            "StaffPositionGrades" => "Institution.StaffPositionGrades",
            "StaffPositionTitles" => "Institution.StaffPositionTitles",
            "StudentAbsenceReasons" => "Institution.StudentAbsenceReasons",

            // Meal
            "FoodTypes" => "Meal.FoodTypes",
            "MealBenefitTypes" => "Meal.MealBenefit",
            "MealImplementers" => "Meal.MealImplementer",
            "MealNutritions" => "Meal.MealNutritions",
            "MealRatings" => "Meal.MealRatings",
            "MealTargets" => "Meal.MealTarget",
            "MealTypes" => "Meal.MealType",

            // ReportCard
            "ReportCardCommentCodes" => "ReportCard.ReportCardCommentCodes",

            // Scholarship
            "ScholarshipAttachmentTypes" => "Scholarship.AttachmentTypes",
            "ScholarshipDisbursementCategories" => "Scholarship.DisbursementCategories",
            "ScholarshipFundingSources" => "Scholarship.FundingSources",
            "ScholarshipInstitutionChoices" => "Scholarship.InstitutionChoiceTypes",
            "ScholarshipPaymentFrequencies" => "Scholarship.PaymentFrequencies",
            "ScholarshipRecipientActivityStatuses" => "Scholarship.RecipientActivityStatuses",
            "ScholarshipSemesters" => "Scholarship.Semesters",

            // SpecialNeeds
            "DiagnosticDisabilityDegree" => "SpecialNeeds.SpecialNeedsDiagnosticsDegree",
            "DiagnosticTypeOfDisability" => "SpecialNeeds.SpecialNeedsDiagnosticsTypes",
            "PlanTypes" => "SpecialNeeds.SpecialNeedsPlanTypes",

            // Staff
            "SalaryAdditionTypes" => "Staff.SalaryAdditionTypes",
            "SalaryDeductionTypes" => "Staff.SalaryDeductionTypes",

            // Student
            "BehaviourClassifications" => "Student.BehaviourClassifications",
            "GuardianRelations" => "Student.GuardianRelations",
            "VisitPurposeTypes" => "Student.StudentVisitPurposeTypes",

            // Textbook
            "TextbookConditions" => "Textbook.TextbookConditions",
            "TextbookDimensions" => "Textbook.TextbookDimensions",
            "TextbookStatuses" => "Textbook.TextbookStatuses",

            // User
            "CommentTypes" => "User.CommentTypes",
            "ContactTypes" => "User.ContactTypes",
            "LanguageProficiencies" => "User.LanguageProficiencies",

            // Ungrouped or Core
            "Languages" => "Languages",
        ];

// Return mapped value if it exists
        if (isset($keyMappings[$key])) {
            return $keyMappings[$key];
        }

// Log fallback
        // Log::write('debug', "{$category}.{$key}");

// Default fallback
        return "{$category}.{$key}";
// POCOR-8995 end
    }
}
