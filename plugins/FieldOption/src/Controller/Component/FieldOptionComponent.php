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
        'Genders' => 'Institution.Genders',
        'Localities' => 'Institution.Localities',
        'Ownerships' => 'Institution.Ownerships',
        'Providers' => 'Institution.Providers',
        'Sectors' => 'Institution.Sectors',
        'Statuses' => 'Institution.Statuses',
        'Types' => 'Institution.Types',
        'NetworkConnectivities' => 'Institution.NetworkConnectivities',
        'StaffPositionGrades' => 'Institution.StaffPositionGrades',
        'StaffPositionTitles' => 'Institution.StaffPositionTitles',
        'AllergyTypes' => 'Health.AllergyTypes',
        'ConsultationTypes' => 'Health.ConsultationTypes',
        'Relationships' => 'Health.Relationships',
        'Conditions' => 'Health.Conditions',
        'ImmunizationTypes' => 'Health.ImmunizationTypes',
        'TestTypes' => 'Health.TestTypes',
        'QualityVisitTypes' => 'FieldOption.QualityVisitTypes',
        'InfrastructureOwnerships' => 'FieldOption.InfrastructureOwnerships',
        'InfrastructureConditions' => 'FieldOption.InfrastructureConditions',
        'QualificationSpecialisations' => 'FieldOption.QualificationSpecialisations',
        'QualificationLevels' => 'FieldOption.QualificationLevels',
        'FeeTypes' => 'FieldOption.FeeTypes',
        'EmploymentTypes' => 'FieldOption.EmploymentTypes',
        'ExtracurricularTypes' => 'FieldOption.ExtracurricularTypes',
        'IdentityTypes' => 'FieldOption.IdentityTypes',
        'Languages' => 'Languages',
        'LicenseTypes' => 'FieldOption.LicenseTypes',
        'SpecialNeedTypes' => 'FieldOption.SpecialNeedTypes',
        'SpecialNeedDifficulties' => 'FieldOption.SpecialNeedDifficulties',
        'StaffAbsenceReasons' => 'FieldOption.StaffAbsenceReasons',
        'StudentAbsenceReasons' => 'FieldOption.StudentAbsenceReasons',
        'Nationalities' => 'FieldOption.Nationalities',
        'GuardianRelations' => 'Student.GuardianRelations',
        'StaffTypes' => 'Staff.StaffTypes',
        'StaffLeaveTypes' => 'Staff.StaffLeaveTypes'
    ];

    // Is called before the controller's beforeFilter method.
    public function initialize(array $config)
    {
        $controller = $this->name;
        $this->controller = $this->_registry->getController();
        $accessMap = [];
        foreach ($this->fieldOptions as $key => $className) {
            $accessMap["$controller.$key"] = "$controller.%s";
        }
        $this->request->addParams($accessMap);
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


