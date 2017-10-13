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
        'NetworkConnectivities' => ['className' => 'Institution.NetworkConnectivities', 'parent' => 'Institution'],
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
        'Competencies' => ['className' => 'Staff.Competencies', 'parent' => 'Staff'],
        'CompetencySets' => ['className' => 'Staff.CompetencySets', 'parent' => 'Staff'],

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
        'EmploymentTypes' => ['className' => 'FieldOption.EmploymentTypes', 'parent' => 'Others'],
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

    // Health
        'AllergyTypes' => ['className' => 'Health.AllergyTypes', 'parent' => 'Health'],
        'Conditions' => ['className' => 'Health.Conditions', 'parent' => 'Health'],
        'ConsultationTypes' => ['className' => 'Health.ConsultationTypes', 'parent' => 'Health'],
        'ImmunizationTypes' => ['className' => 'Health.ImmunizationTypes', 'parent' => 'Health'],
        'Relationships' => ['className' => 'Health.Relationships', 'parent' => 'Health'],
        'TestTypes' => ['className' => 'Health.TestTypes', 'parent' => 'Health'],

    // Transport
        'TransportStatuses' => ['className' => 'Transport.TransportStatuses', 'parent' => 'Transport'],
        'TransportFeatures' => ['className' => 'Transport.TransportFeatures', 'parent' => 'Transport'],
        'BusTypes' => ['className' => 'Transport.BusTypes', 'parent' => 'Transport'],
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
