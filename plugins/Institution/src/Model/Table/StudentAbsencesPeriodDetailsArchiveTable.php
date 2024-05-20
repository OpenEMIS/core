<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\I18n\Time;
use Cake\Filesystem\Folder;
use Cake\Mailer\Email;

use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

use Cake\ORM\ResultSet;
use Cake\Utility\Text;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;//POCOR-6658

class StudentAbsencesPeriodDetailsArchiveTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_details_archived');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes']);
        $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        // $this->addBehavior('Institution.Calendar');
        $this->addBehavior('ContactExcel', [ //POCOR-6898 change Excel to ContactExcel Behaviour
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ],
            'pages' => ['index']
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view', 'add']
        ]);
    }

//    public function findClassStudentsWithAbsenceArchive(Query $query, array $options)
//    {
//        //POCOR-7474-HINDOL fix unnecessary links
//        $institutionId = $options['institution_id'];
//        $academicPeriodId = $options['academic_period_id'];
//        $dayId = $options['day_id'];
//
//        if ($dayId != -1) {
//
//            $where = [
//                $this->aliasField("date >= '") . $dayId . "'",
//                $this->aliasField("date <= '") . $dayId . "'"
//            ];
//        } else {
//            $where = [
//                'OR' => [
//                    [
//                        $this->aliasField("date <= '") . $dayId . "'",
//                        $this->aliasField("date >= '") . $dayId . "'"
//                    ],
//                    [
//                        $this->aliasField("date <= '") . $dayId . "'",
//                        $this->aliasField("date >= '") . $dayId . "'"
//                    ],
//                    [
//                        $this->aliasField("date <= '") . $dayId . "'",
//                        $this->aliasField("date >= '") . $dayId . "'"
//                    ],
//                    [
//                        $this->aliasField("date <= '") . $dayId . "'",
//                        $this->aliasField("date >= '") . $dayId . "'"
//                    ]
//                ]
//            ];
//        }
//
//        $query = $query
//            ->matching('Users')
//            ->contain(['AbsenceTypes','StudentAbsenceReasons'])
//            ->where(
//                [
//                    $this->aliasField('institution_id') => $institutionId,
//                    $this->aliasField('academic_period_id') => $academicPeriodId,
//                    $where
//                ]
//            );
//        return $query;
//    }
//
//    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
//    {
//        $academic_period_id = $this->request->query['academic_period_id'];
//        $institution_class_id = $this->request->query['institution_class_id'];
//        $education_grade_id = $this->request->query['education_grade_id'];
//        $institution_id = $this->request->query['institution_id'];
//        $day_id = $this->request->query['day_id'];
//        $attendance_period_id = $this->request->query['attendance_period_id'];
//        $week_start_day = $this->request->query['week_start_day'];
//        $week_end_day = $this->request->query['week_end_day'];
//        $subject_id = $this->request->query['subject_id'];
//        $week_id = $this->request->query['week_id'];
//        $query
//        ->where([
//                $this->aliasField('institution_id') => $institution_id,
//                $this->aliasField('academic_period_id') =>$academic_period_id,
//                $this->aliasField('institution_class_id') => $institution_class_id,
//                // $this->aliasField('education_grade_id') => $education_grade_id,
//                $this->aliasField('date') => $day_id,
//                $this->aliasField('subject_id') => $subject_id
//                ]);
//    }
}
