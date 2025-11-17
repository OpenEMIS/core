<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleLessonRoomsTable extends ControllerActionTable
{
    public function initialize(array $config):void
    {
        $this->setTable('institution_schedule_lesson_rooms');
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }
}
