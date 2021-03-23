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
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        // $this->addBehavior('Institution.Calendar');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view', 'add']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $absencesList = $this->AbsenceTypes->getCodeList();
        $validator
            ->allowEmpty('student_absence_reason_id', function ($context) use ($absencesList) {
                if (isset($context['data']['absence_type_id']) && $context['data']['absence_type_id'] != 0) {
                    $absenceTypeId = $context['data']['absence_type_id'];
                    $code = $absencesList[$absenceTypeId];
                    return ($code != 'EXCUSED');
                }
                return true;
            });

        return $validator;
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        //For Import StudentAbsenceExcel only. Insert into student_attendace_mark_records once import sucessfully as attendance is counted as marked
        if($entity->has('record_source') && $entity->record_source == 'import_student_attendances')
        {
            $StudentAttendanceMarkedRecords = TableRegistry::get('StudentAttendanceMarkedRecords');

            $date = $entity->date->i18nFormat('YYY-MM-dd');

            $markRecordsData = [
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'institution_class_id' => $entity->institution_class_id,
                'education_grade_id' => $entity->education_grade_id,
                'date' => $date,
                'period' => $entity->period
            ];

            $markRecord = $StudentAttendanceMarkedRecords->newEntity($markRecordsData);
            if (!$markRecord->errors()) {
                $StudentAttendanceMarkedRecords->save($markRecord);
            }
        }

        if ($entity->absence_type_id == 0) {
            $this->delete($entity);
            $this->deleteStudentAbsence($entity);
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
        $educationGradeId = $entity->education_grade_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $absenceTypeId = $entity->absence_type_id;

        $optionList = $StudentAttendanceMarkTypes->getAttendancePerDayOptionsByClass($classId, $academicPeriodId, $date, $educationGradeId);
        if (!is_null($optionList)) {
            $periodCount = count($optionList);
            $totalRecordCount = $this
                ->find()
                ->where([
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('education_grade_id') => $educationGradeId,
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
                        $InstitutionStudentAbsences->aliasField('education_grade_id') => $educationGradeId,
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
                    'education_grade_id' => $educationGradeId,
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
    
    public function deleteStudentAbsence($entity = null){
        $classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $absenceTypeId = $entity->absence_type_id;
        $educationGradeId = $entity->education_grade_id;
        
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        
        $totalRecordCount = $this
                ->find()
                ->where([
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('education_grade_id') => $educationGradeId,
                    $this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('date') => $date,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('student_id') => $studentId
                ])
                ->count();
        
        if($totalRecordCount <= 0){
           
            $data = [
                        'institution_class_id' => $classId,
                        'education_grade_id' => $educationGradeId,
                        'academic_period_id' => $academicPeriodId,
                        'date' => $date->format('Y-m-d'),
                        'institution_id' => $institutionId,
                        'student_id' => $studentId
                    ];
            $InstitutionStudentAbsences->deleteAll($data);
        }
    }
}
