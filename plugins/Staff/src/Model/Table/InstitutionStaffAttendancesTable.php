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
        $this->addBehavior('TrackActivity', ['target' => 'User.InstitutionStaffAttendanceActivities', 'key' => 'security_user_id', 'session' => 'Staff.Staff.id']);
	}

   public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('time_in', 'ruleCompareTime', [
                'rule' => ['compareTime', 'time_out', false]
            ])
            ->add('time_in', 'ruleCheckTimeRange', [
                'rule' => ['checkTimeRange'],
                // 'provider' => 'table',
                // 'on' => function ($context) {
                //     if (array_key_exists('params', $context['data'])) {
                //         return !empty($context['data']['params']);
                //     }
                // }
            ]);
            // ->add('time_in', 'ruleInAcademicPeriod', [
            //     'rule' => ['inAcademicPeriod', 'academic_period_id', []]
            // ])
            // ->add('time_out', 'ruleCompareTimeReverse', [
            //     'rule' => ['compareDateReverse', 'start_time', false]
            // ]);
    }
}
