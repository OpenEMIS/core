<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleNonCurriculumLessonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_non_curriculum_lessons');
        parent::initialize($config);
        
        $this->belongsTo('ScheduleLessonDetails', [
            'className' => 'Schedule.ScheduleLessonDetails',
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }
    
    public function validationAddNonCurriculumLesson(Validator $validator)
    {
        $validator
            ->notEmpty('name');

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }
}
