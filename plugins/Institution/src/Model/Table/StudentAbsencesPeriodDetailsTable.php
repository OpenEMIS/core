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
                'subject_id' => $entity['subject_id'],
                'date' => $date,
                'period' => $entity->period
            ];

            $markRecord = $StudentAttendanceMarkedRecords->newEntity($markRecordsData);
            if (!$markRecord->errors()) {
                $StudentAttendanceMarkedRecords->save($markRecord);
            }
        }
        //POCOR-7165[START] Reason for commenting this is becouse its deleteting the data from parent table before the child table
        //which is creting foreign key constrain issue so its moved to before save.

        // if ($entity->absence_type_id == 0) {
        //     $this->delete($entity);
        //     $this->deleteStudentAbsence($entity);
        // }

        // if ($entity->isNew() || $entity->dirty('absence_type_id')) {
        //     $this->updateStudentAbsencesRecord($entity);
        // }
        //POCOR-7165[END]
    }

    /*
    * This Function is to update and delete data from child table bofore parent table
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-7165
    */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->absence_type_id == 0) {
            $this->delete($entity);
            $this->deleteStudentAbsence($entity);
        }

        // if ($entity->isNew() || $entity->dirty('absence_type_id')) {
        //     $this->updateStudentAbsencesRecord($entity);
        // }
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
                    $this->aliasField('student_id') => $studentId
                    // $this->aliasField('absence_type_id') => $absenceTypeId //POCOR-7205
                ])
                ->count();

                //POCOR-6584 :: START
            $shellName = "AlertAttendance";
            if ($this->isShellStopExist($shellName)) {
                $status = 0; // Stopped
            } else {
                $status = 1; // Running
            }
            if($status == 1){ 
                if($absenceTypeId == 1 || $absenceTypeId ==2){
                    $institutionTable = TableRegistry::get('institutions');
                    $institutionData = $institutionTable->get($entity->institution_id);
                    $institutionSecurityGroupId = $institutionData->security_group_id;
    
                    $alertRulesTable = TableRegistry::get('alert_rules');
                    $alertRuleData = $alertRulesTable->find('all',['conditions'=>['feature'=>'StudentAttendance']])->toArray();
                    if(!empty($alertRuleData)){      
                        foreach($alertRuleData as $alertRuleData1){ 
                            $alertRolesTable = TableRegistry::get('alerts_roles');
                            $alertRolesData = $alertRolesTable->find('all',['conditions'=>['alert_rule_id'=>$alertRuleData1->id],'fields'=>['security_role_id']])->toArray();
                            $securityRoleIds=[];
                            if(!empty($alertRolesData)){
                            
                                foreach($alertRolesData as $alertRole){
                                    $securityRoleIds[] = $alertRole->security_role_id;
                                }
                            
                                $securityGroupUsersTable = TableRegistry::get('security_group_users');
                                $securityGroupUsersData = $securityGroupUsersTable->find()
                                                            ->where(['security_group_id'=>$institutionSecurityGroupId,'security_role_id in'=> $securityRoleIds])
                                                            ->group(['security_user_id'])
                                                            ->toArray();
                                if(!empty($securityGroupUsersData)){                                                  
                                    foreach($securityGroupUsersData as $securityGU){ 
                                        $userTable = TableRegistry::get('security_users');
                                        $userData = $userTable->get($securityGU->security_user_id);
                                        $studentData = $userTable->get($entity->student_id);
                                        
                                        $insCode  = $institutionData->code;
                                        $insName  = $institutionData->name;
                                        $StudentOpenemis_no = $studentData->openemis_no;
                                        $StudentFirstName = $studentData->first_name;
                                        $StudentLastName =$studentData->last_name;
                                        $absenceCount = $this->find('all',['conditions' => ['student_id'=>$entity->student_id, 'institution_id'=>$entity->institution_id,'academic_period_id'=>$entity->academic_period_id
                                        ]])->count();
                                        
                                        if((($alertRuleData1->threshold)-1) <= $absenceCount){
                                            $absenceCount = $absenceCount+1;
                                            if(!empty($userData->email)){
                                                $email = new Email('openemis');
                                                $emailSubject = 'OpenEMIS Attendance Alert for '.$insCode." - ".$insName;
                                                $emailMessage = "[THIS IS AN AUTOMATED MESSAGE - PLEASE DO NOT REPLY DIRECTLY TO THIS EMAIL]\n\nDear Principal,\n\nPlease be informed that the student ".$StudentOpenemis_no." - ".$StudentFirstName." ". $StudentLastName." have missed ".$absenceCount." days of class in ".$insCode." - ".$insName;
                                                $email
                                                    ->to($userData->email)
                                                    ->subject($emailSubject)
                                                    ->send($emailMessage);
                                            }
                                        }
                                    }
                                }
                            }
		                
		                }
                        
                        
                        
                    }
                }
            }
           //POCOR-6584 :: END

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

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $this->updateStudentAbsencesRecord($entity);
    }
    

    //POCOR-6584
    public function isShellStopExist($shellName)
    {
        // folder to the shellprocesses.
        $dir = new Folder(ROOT . DS . 'tmp'); // path
        $filesArray = $dir->find($shellName.'.stop');
        return !empty($filesArray);
    }
	
	//POCOR-6584
    public function deleteStudentAbsence($entity = null){
        $classId = $entity->institution_class_id;
        $academicPeriodId = $entity->academic_period_id;
        $date = $entity->date;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;
        $absenceTypeId = $entity->absence_type_id;
        $educationGradeId = $entity->education_grade_id;
        
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $InstitutionStudentAbsenceDays = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
        
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
            
            //POCOR-7035[START]
            $data1 = [
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'start_date' => $date->format('Y-m-d'),//POCOR-7226
                'end_date' => $date->format('Y-m-d')//POCOR-7226
            ];
            $InstitutionStudentAbsenceDays->deleteAll($data1);
            //POCOR-7035[END]
        }
    }
}
