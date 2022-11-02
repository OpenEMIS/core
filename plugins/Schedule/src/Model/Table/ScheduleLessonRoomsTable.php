<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleLessonRoomsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_lesson_rooms');
        parent::initialize($config);

        $this->belongsTo('ScheduleLessonDetails', [
            'className' => 'Schedule.ScheduleLessonDetails', 
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);

        $this->belongsTo('InstitutionRooms', [
            'className' => 'Institution.InstitutionRooms',
            'foreignKey' => 'institution_room_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }
}
