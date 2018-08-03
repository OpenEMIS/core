<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffAttendancesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('institution_staff_attendances');
		parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'InstitutionStaffAttendances' => ['index', 'view', 'edit']
        ]);
	}
}
