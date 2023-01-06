<?php
namespace Schedule\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ScheduleCurriculumLessonsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_schedule_curriculum_lessons');
        parent::initialize($config);

        $this->belongsTo('LessonDetails', [
            'className' => 'Schedule.ScheduleLessonDetails', 
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);

        $this->belongsTo('InstitutionSubject', [
            'className' => 'Institution.InstitutionSubjects',
            'foreignKey' => 'institution_subject_id'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator;
    }

    public function validationAddCurriculumLesson(Validator $validator)
    {
        

        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }
}
