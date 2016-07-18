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
        'Genders' => 'Institution.Genders',
        'NetworkConnectivities' => 'Institution.NetworkConnectivities',
        'Localities' => 'Institution.Localities',
        'Ownerships' => 'Institution.Ownerships',
        'Providers' => 'Institution.Providers',
        'Sectors' => 'Institution.Sectors',
        'Statuses' => 'Institution.Statuses',
        'Types' => 'Institution.Types',

    // Student
        'StudentAbsenceReasons' => 'Institution.StudentAbsenceReasons',
        'StudentBehaviourCategories' => 'Student.StudentBehaviourCategories',
        'StudentTransferReasons' => 'Student.StudentTransferReasons',
        'StudentDropoutReasons' => 'Student.StudentDropoutReasons',

    // Staff
        'StaffAbsenceReasons' => 'Institution.StaffAbsenceReasons',
        'StaffBehaviourCategories' => 'Staff.StaffBehaviourCategories',
        'StaffLeaveTypes' => 'Staff.StaffLeaveTypes',
        'StaffTypes' => 'Staff.StaffTypes',
        'StaffTrainingCategories' => 'Staff.StaffTrainingCategories',

    // Finance
        'Banks' => 'FieldOption.Banks',
        'BankBranches' => 'FieldOption.BankBranches',
        'FeeTypes' => 'FieldOption.FeeTypes',

    // Guardian
        'GuardianRelations' => 'Student.GuardianRelations',

    // Position
        'StaffPositionGrades' => 'Institution.StaffPositionGrades',
        'StaffPositionTitles' => 'Institution.StaffPositionTitles',

    // Qualification
        'QualificationLevels' => 'FieldOption.QualificationLevels',
        'QualificationSpecialisations' => 'FieldOption.QualificationSpecialisations',

    // Quality
        'QualityVisitTypes' => 'FieldOption.QualityVisitTypes',

    // Salary
        'SalaryAdditionTypes' => 'Staff.SalaryAdditionTypes',
        'SalaryDeductionTypes' => 'Staff.SalaryDeductionTypes',

    // Training
        'TrainingAchievementTypes' => 'Training.TrainingAchievementTypes',
        'TrainingCourseTypes' => 'Training.TrainingCourseTypes',
        'TrainingFieldStudies' => 'Training.TrainingFieldStudies',
        'TrainingLevels' => 'Training.TrainingLevels',
        'TrainingModeDeliveries' => 'Training.TrainingModeDeliveries',
        'TrainingNeedCategories' => 'Training.TrainingNeedCategories',
        'TrainingPriorities' => 'Training.TrainingPriorities',
        'TrainingProviders' => 'Training.TrainingProviders',
        'TrainingRequirements' => 'Training.TrainingRequirements',
        'TrainingResultTypes' => 'Training.TrainingResultTypes',
        'TrainingSpecialisations' => 'Training.TrainingSpecialisations',

    // Others
        'ContactTypes' => 'User.ContactTypes',
        'EmploymentTypes' => 'FieldOption.EmploymentTypes',
        'ExtracurricularTypes' => 'FieldOption.ExtracurricularTypes',
        'IdentityTypes' => 'FieldOption.IdentityTypes',
        'Languages' => 'Languages',
        'LicenseTypes' => 'FieldOption.LicenseTypes',
        'SpecialNeedTypes' => 'FieldOption.SpecialNeedTypes',
        'SpecialNeedDifficulties' => 'FieldOption.SpecialNeedDifficulties',
        'Countries' => 'FieldOption.Countries',
        'Nationalities' => 'FieldOption.Nationalities',
        'CommentTypes' => 'User.CommentTypes',

    // Infrastructure
        'InfrastructureOwnerships' => 'FieldOption.InfrastructureOwnerships',
        'InfrastructureConditions' => 'FieldOption.InfrastructureConditions',

    // Health
        'AllergyTypes' => 'Health.AllergyTypes',
        'Conditions' => 'Health.Conditions',
        'ConsultationTypes' => 'Health.ConsultationTypes',
        'ImmunizationTypes' => 'Health.ImmunizationTypes',
        'Relationships' => 'Health.Relationships',
        'TestTypes' => 'Health.TestTypes'
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $controller = $this->controller->name;
        $accessMap = [];
        foreach ($this->fieldOptions as $key => $className) {
            $accessMap["$controller.$key"] = "$controller.%s";
        }
        $this->request->addParams(['accessMap' => $accessMap]);
    }

    public function getFieldOptions()
    {
        return $this->fieldOptions;
    }

    public function getClassName($key)
    {
        return $this->fieldOptions[$key];
    }
}


