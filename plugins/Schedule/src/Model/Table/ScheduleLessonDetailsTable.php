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

        $this->hasOne('ScheduleLessonRooms', [
            'className' => 'Schedule.ScheduleLessonRooms',
            'foreignKey' => 'institution_schedule_lesson_detail_id',
            //'dependent' => true, 
            //'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ScheduleTimetable' => ['index','add']
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
    
    public function findCheckSubjectExistSameTimeslot(Query $query, array $options){
        $day_of_week = $options['day_of_week'];
        $institution_schedule_timeslot_id = $options['institution_schedule_timeslot_id'];
        $institution_schedule_timetable_id = $options['institution_schedule_timetable_id'];
        $lesson_type = $options['lesson_type'];
        $institution_room_id = $options['institution_room_id'];
        $institution_subject_id = $options['institution_subject_id'];
        $ScheduleCurriculumLessons = TableRegistry::get('Schedule.ScheduleCurriculumLessons');
        $ScheduleLessonRooms = TableRegistry::get('Schedule.ScheduleLessonRooms');
        $query
            ->select(['count' => $query->func()->count('*')])
            ->contain([
                'ScheduleCurriculumLessons',
                'ScheduleLessonRooms'
            ])
      
            ->where([
                $this->aliasField('lesson_type') => $lesson_type,
                $this->aliasField('day_of_week') => $day_of_week,
                $this->aliasField('institution_schedule_timeslot_id') => $institution_schedule_timeslot_id,
                $this->aliasField('institution_schedule_timetable_id') => $institution_schedule_timetable_id,
                //$ScheduleCurriculumLessons->aliasField('institution_subject_id') => $institution_subject_id,
                $ScheduleLessonRooms->aliasField('institution_room_id') => $institution_room_id
            ]);
        return $query;
    }
    
    public function findDeleteTimetableLessionDetailsData(Query $query, array $options){
        $lessionId = $options['lession_id'];
        
        TableRegistry::get('Schedule.ScheduleCurriculumLessons')
                ->deleteAll(['institution_schedule_lesson_detail_id' => $lessionId]);
        TableRegistry::get('Schedule.ScheduleNonCurriculumLessons')
                ->deleteAll(['institution_schedule_lesson_detail_id' => $lessionId]);
        TableRegistry::get('Schedule.ScheduleLessonRooms')
                ->deleteAll(['institution_schedule_lesson_detail_id' => $lessionId]);
        return $this->deleteAll(['id' => $lessionId]);
    }
    
}
