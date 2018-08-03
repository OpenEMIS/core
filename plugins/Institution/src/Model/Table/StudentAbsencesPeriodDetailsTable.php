<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

use Cake\Log\Log;

class StudentAbsencesPeriodDetailsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_details');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes']);
        $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);

        // $this->addBehavior('Institution.Calendar');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view', 'add']
        ]);
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->absence_type_id == 0) {
            $this->delete($entity);
        }

        if ($entity->isNew() || $entity->dirty('absence_type_id')) {
            $this->updateStudentAbsencesRecord($entity);
        }
    }

    public function updateStudentAbsencesRecord($entity = null) 
    {
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $absenceTypeId = $entity->absence_type_id;

        $optionList = $StudentAttendanceMarkTypes->getAttendancePerDayOptionsByClass($classId, $academicPeriodId);
        if (!is_null($optionList)) {
            $periodCount = count($optionList);
            $totalRecordCount = $this
                ->find()
                ->where([
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('date') => $date,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('absence_type_id') => $absenceTypeId
                ])
                ->count();

            // if count matches, the student is absences for full day
            if ($totalRecordCount == $periodCount) {
                $fullDayRecordResult = $InstitutionStudentAbsences
                    ->find()
                    ->where([
                        $InstitutionStudentAbsences->aliasField('institution_class_id') => $classId,
                        $InstitutionStudentAbsences->aliasField('academic_period_id') => $academicPeriodId,
                        $InstitutionStudentAbsences->aliasField('date') => $date,
                        $InstitutionStudentAbsences->aliasField('institution_id') => $institutionId,
                        $InstitutionStudentAbsences->aliasField('student_id') => $studentId
                    ])
                    ->all();

                if (!$fullDayRecordResult->isEmpty()) {
                    $absenceEntity = $fullDayRecordResult->first();
                } else {
                    $absenceEntity = $InstitutionStudentAbsences->newEntity();
                }

                $data = [
                    'institution_class_id' => $classId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $date,
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'absence_type_id' => $absenceTypeId,
                ];

                $absenceEntity = $InstitutionStudentAbsences->patchEntity($absenceEntity, $data);
                $InstitutionStudentAbsences->save($absenceEntity);
            }
        }
    }
}
