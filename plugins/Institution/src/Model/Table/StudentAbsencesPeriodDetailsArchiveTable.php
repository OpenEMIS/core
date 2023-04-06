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

class StudentAbsencesPeriodDetailsArchiveTable extends AppTable
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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view', 'add']
        ]);
    }

    public function findClassStudentsWithAbsenceArchive(Query $query, array $options)
    {
        $InstitutionStaffAttendances = TableRegistry::get('Institution.InstitutionStaffAttendancesArchive');
        $InstitutionStaffShiftsTable = TableRegistry::get('Institution.InstitutionStaffShifts');
        $StudentAttendances = TableRegistry::get('Institution.StudentAttendances');
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $StaffLeaveTable = TableRegistry::get('Institution.StaffLeaveArchived');
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $ownAttendanceView = $options['own_attendance_view'];
        $otherAttendanceView = $options['other_attendance_view'];
        $shiftId = $options['shift_id'];
        $weekStartDate = $options['week_start_day'];
        $weekEndDate = $options['week_end_day'];
        $dayId = $options['day_id'];

        if ($dayId != -1) {
            $weekStartDate = $dayDate;
            $weekEndDate = $dayDate;
            $where = [
                $this->aliasField("date >= '") . $dayId . "'",
                $this->aliasField("date <= '") . $dayId . "'"
            ];
        } else {
            $where = [
                'OR' => [
                    [
                        $this->aliasField("date <= '") . $dayId . "'",
                        $this->aliasField("date >= '") . $dayId . "'"
                    ],
                    [
                        $this->aliasField("date <= '") . $dayId . "'",
                        $this->aliasField("date >= '") . $dayId . "'"
                    ],
                    [
                        $this->aliasField("date <= '") . $dayId . "'",
                        $this->aliasField("date >= '") . $dayId . "'"
                    ],
                    [
                        $this->aliasField("date <= '") . $dayId . "'",
                        $this->aliasField("date >= '") . $dayId . "'"
                    ]
                ]
            ];
        }

        $query = $query
            ->matching('Users')
            ->contain(['AbsenceTypes','StudentAbsenceReasons'])
            ->where(
                [   
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $where
                ]
            );
        return $query;
    }
}
