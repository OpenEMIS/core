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
			'supported_field_types' => ['TEXT','NUMBER','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','STUDENT_LIST','FILE','COORDINATES','REPEATER']
		],
		'Student.Students' => [
			'filter' => null,
			'behavior' => 'Student',
			'supported_field_types' => ['TEXT','NUMBER','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','FILE','COORDINATES']
		],
		'Staff.Staff' => [
			'filter' => null,
			'behavior' => 'Staff',
			'supported_field_types' => ['TEXT','NUMBER','TEXTAREA','DROPDOWN','CHECKBOX','TABLE','DATE','TIME','FILE','COORDINATES']
		],
		'Institution.InstitutionInfrastructures' => [
			'filter' => 'Infrastructure.InfrastructureTypes',
			'behavior' => null,
			'supported_field_types' => ['TEXT','NUMBER','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES']
		],
		'Student.StudentSurveys' => [
			'filter' => null,
			'behavior' => null,
			'supported_field_types' => ['TEXT','NUMBER','DROPDOWN']
		],
		'InstitutionRepeater.RepeaterSurveys' => [
			'filter' => null,
			'behavior' => null,
			'supported_field_types' => ['TEXT','NUMBER','DROPDOWN']
		],
		'Institution.InstitutionRooms' => [
			'filter' => 'Infrastructure.RoomTypes',
			'behavior' => null,
			'supported_field_types' => ['TEXT','NUMBER','TEXTAREA','DROPDOWN','CHECKBOX','DATE','TIME','FILE','COORDINATES']
		]
	];

    protected function _getFilter() {
    	return $this->moduleMap[$this->model]['filter'];
	}

	protected function _getBehavior() {
		return $this->moduleMap[$this->model]['behavior'];
	}

	protected function _getSupportedFieldTypes() {
		return $this->moduleMap[$this->model]['supported_field_types'];
	}
}
