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
    public function initialize(array $config)
    {
        $this->table('institution_schedule_lessons');
        parent::initialize($config);

        $this->belongsTo('Timetables', [
            'className' => 'Schedule.ScheduleTimetables', 
            'foreignKey' => 'institution_schedule_timetable_id'
        ]);

        $this->belongsTo('Timeslots', [
            'className' => 'Schedule.ScheduleTimeslots', 
            'foreignKey' => 'institution_schedule_timeslot_id'
        ]);

        $this->hasMany('ScheduleLessonDetails', [
            'className' => 'Schedule.ScheduleLessonDetails',
            'foreignKey' => ['day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id'],
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
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
        $lessonType = $this->ScheduleLessonDetails->getLessonTypeOptions(true);

        $query
            ->formatResults(function (ResultSetInterface $results) use ($lessonType) {
                return $lessonType;
            });

        return $query;
    }

    public function findAllLessons(Query $query, array $options)
    {
        $timetableId = $options['institution_schedule_timetable_id'];

        $query
            ->contain([
                'Timeslots',
                'ScheduleLessonDetails.ScheduleCurriculumLessons'=>[
                    'InstitutionSubject'=>['Classes','Teachers']
                ],
                'ScheduleLessonDetails.ScheduleNonCurriculumLessons',
                'ScheduleLessonDetails.ScheduleLessonRooms'=>[
                    'InstitutionRooms'
                ]
            ])
            ->where([
                $this->aliasField('institution_schedule_timetable_id') => $timetableId
            ]);

        return $query;
    }     
}
