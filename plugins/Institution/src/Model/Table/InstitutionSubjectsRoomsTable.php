<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionSubjectsRoomsTable extends AppTable
{
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->belongsTo('InstitutionRooms', ['className' => 'Institution.InstitutionRooms']);
	}
}
