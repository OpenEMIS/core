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

class FieldOptionComponent extends Component
{
    private $controller;
    private $fieldOptions = [
    // Institution
        'Localities' => ['className' => 'Institution.Localities', 'parent' => 'Institution'],
        'Ownerships' => ['className' => 'Institution.Ownerships', 'parent' => 'Institution'],
        'Sectors' => ['className' => 'Institution.Sectors', 'parent' => 'Institution'],
        'Providers' => ['className' => 'Institution.Providers', 'parent' => 'Institution'],
        'Types' => ['className' => 'Institution.Types', 'parent' => 'Institution'],
        'ShiftOptions' => ['className' => 'Institution.ShiftOptions', 'parent' => 'Institution'],
        'TextbookConditions' => ['className' => 'Textbook.TextbookConditions', 'parent' => 'Institution'],
        'ReportCardCommentCodes' => ['className' => 'ReportCard.ReportCardCommentCodes', 'parent' => 'Institution'],

    // Student
        'StudentAbsenceReasons' => ['className' => 'Institution.StudentAbsenceReasons', 'parent' => 'Student'],
        'StudentBehaviourCategories' => ['className' => 'Student.StudentBehaviourCategories', 'parent' => 'Student'],
        'StudentTransferReasons' => ['className' => 'Student.StudentTransferReasons', 'parent' => 'Student'],
        'StudentWithdrawReasons' => ['className' => 'Student.StudentWithdrawReasons', 'parent' => 'Student'],
        'GuidanceTypes' => ['className' => 'Student.GuidanceTypes', 'parent' => 'Student'],

    // Staff
        'StaffAbsenceReasons' => ['className' => 'Institution.StaffAbsenceReasons', 'parent' => 'Staff'],
        'StaffBehaviourCategories' => ['className' => 'Staff.StaffBehaviourCategories', 'parent' => 'Staff'],
        'StaffLeaveTypes' => ['className' => 'Staff.StaffLeaveTypes', 'parent' => 'Staff'],
        'StaffTypes' => ['className' => 'Staff.StaffTypes', 'parent' => 'Staff'],
        'StaffTrainingCategories' => ['className' => 'Staff.StaffTrainingCategories', 'parent' => 'Staff'],

    // Finance
        'Banks' => ['className' => 'FieldOption.Banks', 'parent' => 'Finance'],
        'BankBranches' => ['className' => 'FieldOption.BankBranches', 'parent' => 'Finance'],
        'FeeTypes' => ['className' => 'FieldOption.FeeTypes', 'parent' => 'Finance'],

    // Guardian
        'GuardianRelations' => ['className' => 'Student.GuardianRelations', 'parent' => 'Guardian'],

    // Position
        'StaffPositionGrades' => ['className' => 'Institution.StaffPositionGrades', 'parent' => 'Position'],
        'StaffPositionTitles' => ['className' => 'Institution.StaffPositionTitles', 'parent' => 'Position'],

    // Qualification
        'QualificationLevels' => ['className' => 'FieldOption.QualificationLevels', 'parent' => 'Qualification'],
        'QualificationTitles' => ['className' => 'FieldOption.QualificationTitles', 'parent' => 'Qualification'],
        'QualificationSpecialisations' => ['className' => 'FieldOption.QualificationSpecialisations', 'parent' => 'Qualification'],

    // Quality
        'QualityVisitTypes' => ['className' => 'FieldOption.QualityVisitTypes', 'parent' => 'Quality'],

    // Salary
        'SalaryAdditionTypes' => ['className' => 'Staff.SalaryAdditionTypes', 'parent' => 'Salary'],
        'SalaryDeductionTypes' => ['className' => 'Staff.SalaryDeductionTypes', 'parent' => 'Salary'],

    // Training
        'TrainingAchievementTypes' => ['className' => 'Training.TrainingAchievementTypes', 'parent' => 'Training'],
        'TrainingCourseTypes' => ['className' => 'Training.TrainingCourseTypes', 'parent' => 'Training'],
        'TrainingFieldStudies' => ['className' => 'Training.TrainingFieldStudies', 'parent' => 'Training'],
        'TrainingLevels' => ['className' => 'Training.TrainingLevels', 'parent' => 'Training'],
        'TrainingModeDeliveries' => ['className' => 'Training.TrainingModeDeliveries', 'parent' => 'Training'],
        'TrainingNeedCategories' => ['className' => 'Training.TrainingNeedCategories', 'parent' => 'Training'],
        'TrainingNeedCompetencies' => ['className' => 'Training.TrainingNeedCompetencies', 'parent' => 'Training'],
        'TrainingNeedStandards' => ['className' => 'Training.TrainingNeedStandards', 'parent' => 'Training'],
        'TrainingNeedSubStandards' => ['className' => 'Training.TrainingNeedSubStandards', 'parent' => 'Training'],
        'TrainingPriorities' => ['className' => 'Training.TrainingPriorities', 'parent' => 'Training'],
        'TrainingProviders' => ['className' => 'Training.TrainingProviders', 'parent' => 'Training'],
        'TrainingRequirements' => ['className' => 'Training.TrainingRequirements', 'parent' => 'Training'],
        'TrainingResultTypes' => ['className' => 'Training.TrainingResultTypes', 'parent' => 'Training'],
        'TrainingSpecialisations' => ['className' => 'Training.TrainingSpecialisations', 'parent' => 'Training'],

    // Others
        'ContactTypes' => ['className' => 'User.ContactTypes', 'parent' => 'Others'],
        'EmploymentStatusTypes' => ['className' => 'FieldOption.EmploymentStatusTypes', 'parent' => 'Others'],
        'ExtracurricularTypes' => ['className' => 'FieldOption.ExtracurricularTypes', 'parent' => 'Others'],
        'IdentityTypes' => ['className' => 'FieldOption.IdentityTypes', 'parent' => 'Others'],
        'Languages' => ['className' => 'Languages', 'parent' => 'Others'],
        'LicenseTypes' => ['className' => 'FieldOption.LicenseTypes', 'parent' => 'Others'],
        'LicenseClassifications' => ['className' => 'FieldOption.LicenseClassifications', 'parent' => 'Others'],
        'SpecialNeedTypes' => ['className' => 'FieldOption.SpecialNeedTypes', 'parent' => 'Others'],
        'SpecialNeedDifficulties' => ['className' => 'FieldOption.SpecialNeedDifficulties', 'parent' => 'Others'],
        'Countries' => ['className' => 'FieldOption.Countries', 'parent' => 'Others'],
        'Nationalities' => ['className' => 'FieldOption.Nationalities', 'parent' => 'Others'],
        'CommentTypes' => ['className' => 'User.CommentTypes', 'parent' => 'Others'],
        'BehaviourClassifications' => ['className' => 'Student.BehaviourClassifications', 'parent' => 'Others'],

    // Infrastructure
        'InfrastructureOwnerships' => ['className' => 'FieldOption.InfrastructureOwnerships', 'parent' => 'Infrastructure'],
        'InfrastructureConditions' => ['className' => 'FieldOption.InfrastructureConditions', 'parent' => 'Infrastructure'],
        'InfrastructureNeedTypes' => ['className' => 'Institution.InfrastructureNeedTypes', 'parent' => 'Infrastructure'],
        'InfrastructureProjectFundingSources' => ['className' => 'Institution.InfrastructureProjectFundingSources', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterTypes' => ['title' => 'Infrastructure WASH Water Types', 'className' => 'Institution.InfrastructureWashWaterTypes', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterFunctionalities' => ['title' => 'Infrastructure WASH Water Functionalities', 'className' => 'Institution.InfrastructureWashWaterFunctionalities', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterProximities' => ['title' => 'Infrastructure WASH Water Proximities', 'className' => 'Institution.InfrastructureWashWaterProximities', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterQuantities' => ['title' => 'Infrastructure WASH Water Quantities', 'className' => 'Institution.InfrastructureWashWaterQuantities', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterQualities' => ['title' => 'Infrastructure WASH Water Qualities','className' => 'Institution.InfrastructureWashWaterQualities', 'parent' => 'Infrastructure'],
        'InfrastructureWashWaterAccessibilities' => ['title' => 'Infrastructure WASH Water Accessibilities', 'className' => 'Institution.InfrastructureWashWaterAccessibilities', 'parent' => 'Infrastructure'],
        'InfrastructureWashSanitationTypes' => ['title' => 'Infrastructure WASH Sanitation Types', 'className' => 'Institution.InfrastructureWashSanitationTypes', 'parent' => 'Infrastructure'],
        'InfrastructureWashSanitationUses' => ['title' => 'Infrastructure WASH Sanitation Uses', 'className' => 'Institution.InfrastructureWashSanitationUses', 'parent' => 'Infrastructure'],
        'InfrastructureWashSanitationQualities' => ['title' => 'Infrastructure WASH Sanitation Qualities', 'className' => 'Institution.InfrastructureWashSanitationQualities', 'parent' => 'Infrastructure'],
        'InfrastructureWashSanitationAccessibilities' => ['title' => 'Infrastructure WASH Sanitation Accessibilities', 'className' => 'Institution.InfrastructureWashSanitationAccessibilities', 'parent' => 'Infrastructure'],

        'InfrastructureWashHygieneTypes' => ['title' => 'Infrastructure WASH Hygiene Types', 'className' => 'Institution.InfrastructureWashHygieneTypes', 'parent' => 'Infrastructure'],
        'InfrastructureWashHygieneSoapashAvailabilities' => ['title' => 'Infrastructure WASH Hygiene Soap/Ash Availabilities', 'className' => 'Institution.InfrastructureWashHygieneSoapashAvailabilities', 'parent' => 'Infrastructure'],
        'InfrastructureWashHygieneEducations' => ['title' => 'Infrastructure WASH Hygiene Educations', 'className' => 'Institution.InfrastructureWashHygieneEducations', 'parent' => 'Infrastructure'],
        'InfrastructureWashWasteTypes' => ['title' => 'Infrastructure WASH Waste Types', 'className' => 'Institution.InfrastructureWashWasteTypes', 'parent' => 'Infrastructure'],
        'InfrastructureWashWasteFunctionalities' => ['title' => 'Infrastructure WASH Waste Functionalities', 'className' => 'Institution.InfrastructureWashWasteFunctionalities', 'parent' => 'Infrastructure'],
        'InfrastructureWashSewageTypes' => ['title' => 'Infrastructure WASH Sewage Types', 'className' => 'Institution.InfrastructureWashSewageTypes', 'parent' => 'Infrastructure'],
        'InfrastructureWashSewageFunctionalities' => ['title' => 'Infrastructure WASH Sewage Functionalities', 'className' => 'Institution.InfrastructureWashSewageFunctionalities', 'parent' => 'Infrastructure'],
        'UtilityElectricityTypes' => ['className' => 'Institution.UtilityElectricityTypes', 'parent' => 'Infrastructure'],
        'UtilityElectricityConditions' => ['className' => 'Institution.UtilityElectricityConditions', 'parent' => 'Infrastructure'],
        'UtilityInternetTypes' => ['className' => 'Institution.UtilityInternetTypes', 'parent' => 'Infrastructure'],
        'UtilityInternetConditions' => ['className' => 'Institution.UtilityInternetConditions', 'parent' => 'Infrastructure'],
        'UtilityInternetBandwidths' => ['className' => 'Institution.UtilityInternetBandwidths', 'parent' => 'Infrastructure'],
        'UtilityTelephoneTypes' => ['className' => 'Institution.UtilityTelephoneTypes', 'parent' => 'Infrastructure'],
        'UtilityTelephoneConditions' => ['className' => 'Institution.UtilityTelephoneConditions', 'parent' => 'Infrastructure'],

    // Health
        'AllergyTypes' => ['className' => 'Health.AllergyTypes', 'parent' => 'Health'],
        'Conditions' => ['className' => 'Health.Conditions', 'parent' => 'Health'],
        'ConsultationTypes' => ['className' => 'Health.ConsultationTypes', 'parent' => 'Health'],
        'ImmunizationTypes' => ['className' => 'Health.ImmunizationTypes', 'parent' => 'Health'],
        'Relationships' => ['className' => 'Health.Relationships', 'parent' => 'Health'],
        'TestTypes' => ['className' => 'Health.TestTypes', 'parent' => 'Health'],
        'InsuranceProviders' => ['className' => 'Health.InsuranceProviders', 'parent' => 'Health'],
        'InsuranceTypes' => ['className' => 'Health.InsuranceTypes', 'parent' => 'Health'],

    // Transport
        'TransportFeatures' => ['className' => 'Transport.TransportFeatures', 'parent' => 'Transport'],
        'BusTypes' => ['className' => 'Transport.BusTypes', 'parent' => 'Transport'],
        'TripTypes' => ['className' => 'Transport.TripTypes', 'parent' => 'Transport'],

    // Scholarship
        'ScholarshipFundingSources' => ['className' => 'Scholarship.FundingSources', 'parent' => 'Scholarship'],
        'ScholarshipAttachmentTypes' => ['className' => 'Scholarship.AttachmentTypes', 'parent' => 'Scholarship'],
        'ScholarshipPaymentFrequencies' => ['className' => 'Scholarship.PaymentFrequencies', 'parent' => 'Scholarship'],
        'ScholarshipRecipientActivityStatuses' => ['className' => 'Scholarship.RecipientActivityStatuses', 'parent' => 'Scholarship'],
        'ScholarshipDisbursementCategories' => ['className' => 'Scholarship.DisbursementCategories', 'parent' => 'Scholarship'],
        'ScholarshipSemesters' => ['className' => 'Scholarship.Semesters', 'parent' => 'Scholarship'],
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
        return $this->fieldOptions;
    }

    public function getClassName($key)
    {
        return $this->fieldOptions[$key]['className'];
    }
}
