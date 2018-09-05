<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
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
            'InstitutionStaffAttendances' => ['index', 'view', 'add', 'edit'],
        ]);
        $this->addBehavior('CompositeKey');
        // cannot work for Institution > Attendance > Staff
        // $this->addBehavior('TrackActivity', ['target' => 'User.InstitutionStaffAttendanceActivities', 'key' => 'security_user_id', 'session' => 'Staff.Staff.id']);
	}

   public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('time_in', 'ruleCompareTime', [
                'rule' => ['compareTime', 'time_out', false],
                'on' => function ($context) {
                    return !empty($context['data']['time_out']);
                }
            ]);
    }
}
