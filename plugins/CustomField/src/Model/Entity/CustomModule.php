<?php
namespace CustomField\Model\Entity;

use Cake\ORM\Entity;

class CustomModule extends Entity
{
    protected $_virtual = ['filter', 'behavior', 'supported_field_types'];
    private $moduleMap = [
        'Institution.Institutions' => [
            'filter' => 'Institution.Types',
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','STUDENT_LIST','FILE','COORDINATES','REPEATER','NOTE']
        ],
        'Student.Students' => [
            'filter' => null,
            'behavior' => 'Student',
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','FILE','COORDINATES','NOTE']
        ],
        'Staff.Staff' => [
            'filter' => null,
            'behavior' => 'Staff',
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','FILE','COORDINATES','NOTE']
        ],
        'Student.StudentSurveys' => [
            'filter' => null,
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','DATE','TIME']
        ],
        'InstitutionRepeater.RepeaterSurveys' => [
            'filter' => null,
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','DATE','TIME','CHECKBOX']
        ],

        // Infrastructure modules will share the same supported field types, if there is changes, please modify all the types of the infrastructure modules
        // Start Infrastructure Modules
        'Institution.InstitutionLands' => [
            'filter' => 'Infrastructure.LandTypes',
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES','NOTE']
        ],
        'Institution.InstitutionBuildings' => [
            'filter' => 'Infrastructure.BuildingTypes',
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES','NOTE']
        ],
        'Institution.InstitutionFloors' => [
            'filter' => 'Infrastructure.FloorTypes',
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES','NOTE']
        ],
        'Institution.InstitutionRooms' => [
            'filter' => 'Infrastructure.RoomTypes',
            'behavior' => null,
            'supported_field_types' => ['TEXT','NUMBER','DECIMAL','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES','NOTE']
        ]
        // End infrastructure modules
    ];

    protected function _getFilter()
    {
        return $this->moduleMap[$this->model]['filter'];
    }

    protected function _getBehavior()
    {
        return $this->moduleMap[$this->model]['behavior'];
    }

    protected function _getSupportedFieldTypes()
    {
        return $this->moduleMap[$this->model]['supported_field_types'];
    }
}
