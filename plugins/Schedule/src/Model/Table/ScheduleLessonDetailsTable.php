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

class ScheduleLessonDetailsTable extends ControllerActionTable
{
    const CURRICULUM_LESSON = 1;
    const NON_CURRICULUM_LESSON = 2;

    public function initialize(array $config)
    {
        $this->table('institution_schedule_lesson_details');
        parent::initialize($config);

        $this->belongsTo('ScheduleLessons', [
            'className' => 'Schedule.ScheduleLessons', 
            'foreignKey' => ['day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id']
        ]);

        $this->hasOne('ScheduleCurriculumLessons', [
            'className' => 'Schedule.ScheduleCurriculumLessons', 
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);

        $this->hasOne('ScheduleNonCurriculumLessons', [
            'className' => 'Schedule.ScheduleNonCurriculumLessons',
            'foreignKey' => 'institution_schedule_lesson_detail_id'
        ]);

        $this->hasMany('ScheduleLessonRooms', [
            'className' => 'Schedule.ScheduleLessonRooms',
            'foreignKey' => 'institution_schedule_lesson_detail_id',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['add']
        ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $lessonType = $data['lesson_type'];
        if ($lessonType == self::NON_CURRICULUM_LESSON) {
            $options['associated']['ScheduleNonCurriculumLessons'] = [
                'validate' => 'addNonCurriculumLesson'
            ]; 
        } elseif ($lessonType == self::CURRICULUM_LESSON) {
            $options['associated']['ScheduleCurriculumLessons'] = [
                'validate' => 'addCurriculumLesson'
            ];
        }
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

    public function getLessonTypeOptions($select = false)
    {
        $lessonType = [
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

        if ($select) {
            $selectOption = [
                [
                    'id' => 0,
                    'name' => __('-- Select --')
                ]
            ];
            $lessonType = array_merge($selectOption, $lessonType);
        }

        return $lessonType;
    }
}
