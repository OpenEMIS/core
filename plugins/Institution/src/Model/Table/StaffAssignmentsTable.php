<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffAssignmentsTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('staff_assignments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('RequestingInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'requesting_institution_id']);
		$this->belongsTo('RequestingPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'requesting_position_id']);
	}
}
