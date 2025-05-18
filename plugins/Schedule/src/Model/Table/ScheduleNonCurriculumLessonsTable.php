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
    public function initialize(array $config): void
    {
        $this->setTable('institution_schedule_non_curriculum_lessons');
        parent::initialize($config);
        
        $this->belongsTo('ScheduleLessonDetails', [
            'className' => 'Schedule.ScheduleLessonDetails',
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }
    
    public function validationAddNonCurriculumLesson(Validator $validator): Validator
    {
        $validator
            ->notEmpty('name');

        return $validator;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }
}
