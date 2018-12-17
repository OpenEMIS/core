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

class ScheduleLessonsTable extends ControllerActionTable
{
    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

    public function initialize(array $config)
    {
        $this->table('institution_schedule_lessons');
        parent::initialize($config);

        $this->belongsTo('Timetables', ['className' => 'Schedule.ScheduleTimetables', 'foreignKey' => 'institution_schedule_timetable_id']);
        $this->belongsTo('Timeslots', ['className' => 'Schedule.ScheduleTimeslots', 'foreignKey' => 'institution_schedule_timeslot_id']);

        $this->hasMany('CurriculumLessons', [
            'className' => 'Schedule.ScheduleCurriculumLessons',
            'foreignKey' => 'institution_schedule_lesson_id',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('NonCurriculumLessons', [
            'className' => 'Schedule.ScheduleNonCurriculumLessons',
            'foreignKey' => 'institution_schedule_lesson_id',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index', 'view', 'add']
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

    // Finder
    public function findLessonType(Query $query, array $options)
    {
        $lessonType = [
            [
                'id' => 0,
                'name' => __('-- Select --')
            ],
            [
                'id' => self::NON_CURRICULUM_LESSON,
                'name' => __('Non Curriculum Lesson'),
                'title' => __('Non Curriculum')
            ],
            [
                'id' => self::CURRICULUM_LESSON,
                'name' => __('Curriculum Lesson'),
                'title' => __('Curriculum')
            ]
        ];

        return $query->formatResults(function (ResultSetInterface $results) use ($lessonType) {
            return $lessonType;
        });
    }

    public function findAllLessons(Query $query, array $options)
    {
        $timetableId = $options['institution_schedule_timetable_id'];

        $query
            ->contain([
                'Timeslots',
                'CurriculumLessons',
                'NonCurriculumLessons'
            ])
            ->where([
                $this->aliasField('institution_schedule_timetable_id') => $timetableId
            ]);

        return $query;
    }
}
