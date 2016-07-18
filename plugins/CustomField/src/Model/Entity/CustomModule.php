<?php
namespace CustomField\Model\Entity;

use Cake\ORM\Entity;

class CustomModule extends Entity
{
	protected $_virtual = ['filter'];

    protected function _getFilter() {
		$filterMap = [
			'Institution.Institutions' => 'Institution.Types',
			'Institution.InstitutionInfrastructures' => 'Infrastructure.InfrastructureTypes',
			'Institution.InstitutionRooms' => 'Infrastructure.RoomTypes'
		];

		return array_key_exists($this->model, $filterMap) ? $filterMap[$this->model] : null;
	}
}
