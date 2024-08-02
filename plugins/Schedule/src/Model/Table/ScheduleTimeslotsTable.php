<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleTimeslotsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_schedule_timeslots');
        $this->setEntityClass('Schedule.ScheduleTimeslots');
        parent::initialize($config);

        $this->belongsTo('ScheduleIntervals', [
            'className' => 'Schedule.ScheduleIntervals', 
            'foreignKey' => 'institution_schedule_interval_id'
        ]);

        $this->hasMany('Lessons', [
            'className' => 'Schedule.ScheduleLessons',
            'foreignKey' => 'institution_schedule_timeslot_id',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->toggle('reorder', false);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('interval', 'create');
        return $validator;
    }

}
