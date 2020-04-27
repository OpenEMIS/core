<?php

namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\I18n\Time;
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
        $this->addBehavior('TrackActivity', ['target' => 'User.InstitutionStaffAttendanceActivities', 'key' => 'security_user_id', 'keyField' => 'staff_id']);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
                        ->allowEmpty('time_out')
                        ->add('time_out', 'timeInShouldNotEmpty', [
                            'rule' => function($value, $context) {
                                return !(!empty($context['data']['time_out']) && empty($context['data']['time_in']));
                            },
                            'message' => __('Record does not exist.')
                        ])
                        ->add('time_out', 'ruleCompareTimeReverse', [
                            'rule' => ['compareDateReverse', 'time_in', false],
                            'message' => __('Time Out cannot be earlier than Time In'),
                            'on' => function ($context) {
                        if (!(!empty($context['data']['time_out']) && empty($context['data']['time_in']))) {
                            return true;
                        }
                    }
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        if (!$entity->isNew()) {
            // delete record if user removes the time in and comment
            $time_in = $entity->time_in;
            $comment = $entity->comment;
            if (is_null($time_in) && is_null($comment)) {
                $this->delete($entity);
            }
        }
    }
    
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        
        $this->startUpdateStaffLateAttendance($entity->staff_id, $entity->date->format('Y-m-d'));
        
    }
   
    public function startUpdateStaffLateAttendance($staffId, $date) {
            
        
        $cmd  = ROOT . DS . 'bin' . DS . 'cake UpdateStaffLateAttendance '.$staffId.' '.$date;
        $logs = ROOT . DS . 'logs' . DS . 'UpdateStaffLateAttendance.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
//      shell_exec($cmd);
//              try {
//			shell_exec($shellCmd);
//			Log::write('debug', $shellCmd);
//		} catch(\Exception $ex) {
//			Log::write('error', __METHOD__ . ' exception when removing inactive roles : '. $ex);
//		}
    }

}
