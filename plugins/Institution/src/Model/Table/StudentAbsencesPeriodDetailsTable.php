<?php

namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Mailer\Email;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Locator\TableLocator;

class StudentAbsencesPeriodDetailsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_student_absence_details');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
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

    public function validationDefault(Validator $validator): Validator
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
        if ($entity->has('record_source') && $entity->record_source == 'import_student_attendances') {
            $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');

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
            if (!$markRecord->getErrors()) {
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

    public function deleteStudentAbsence($entity = null)
    {
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

        if ($totalRecordCount <= 0) {

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

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $this->updateStudentAbsencesRecord($entity);
    }


    //POCOR-6584

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
            if ($status == 1) {
                if ($absenceTypeId == 1 || $absenceTypeId == 2) {
                    $institutionTable = TableRegistry::get('Institution.Institutions');
                    $institutionData = $institutionTable->get($entity->institution_id);
                    $institutionSecurityGroupId = $institutionData->security_group_id;

                    $alertRulesTable = TableRegistry::get('Alert.AlertRules');
                    $alertRuleData = $alertRulesTable->find('all', ['conditions' => ['feature' => 'StudentAttendance', 'enabled' => 1]])->toArray(); //POCOR-7397
                    if (!empty($alertRuleData)) {
                        foreach ($alertRuleData as $alertRuleData1) {
                            $alertRolesTable = TableRegistry::get('Alert.AlertsRoles');
                            $alertRolesData = $alertRolesTable->find('all', ['conditions' => ['alert_rule_id' => $alertRuleData1->id], 'fields' => ['security_role_id']])->toArray();
                            $securityRoleIds = [];
                            if (!empty($alertRolesData)) {

                                foreach ($alertRolesData as $alertRole) {
                                    $securityRoleIds[] = $alertRole->security_role_id;
                                }
                                $securityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
                                $securityGroupUsersData = $securityGroupUsersTable->find()
                                    ->where(['security_group_id' => $institutionSecurityGroupId, 'security_role_id in' => $securityRoleIds])
                                    ->group(['security_user_id'])
                                    ->toArray();
                                if (!empty($securityGroupUsersData)) {
                                    foreach ($securityGroupUsersData as $securityGU) {
                                        $userTable = TableRegistry::get('User.Users');
                                        $userData = $userTable->get($securityGU->security_user_id);
                                        $studentData = $userTable->get($entity->student_id);
                                        ///POCOR-7266::Start
                                        $nationalitiesLocator = new TableLocator();
                                        $nationalyTable = $nationalitiesLocator ->get('nationalities');
                                        // $nationalyTable = TableRegistry::get('nationalities');
                                        $genderTable = TableRegistry::get('User.Genders');
                                        $idTypeTable = TableRegistry::get('FieldOption.IdentityTypes');
                                        if(isset($studentData->nationality_id)){
                                            $nationalData = $nationalyTable->find('all',['conditions'=>['id'=>$studentData->nationality_id ]])->first();
                                        }
                                        if(isset($studentData->gender_id)){
                                            $genderData = $genderTable->find('all',['conditions'=>['id'=>$studentData->gender_id ]])->first();
                                        }
                                        if(isset($studentData->identity_type_id)){
                                            $idtypeData = $idTypeTable->find('all',['conditions'=>['id'=>$studentData->identity_type_id ]])->first();
                                        }
                                        //POCOR-7266::End

                                        $insCode = $institutionData->code;
                                        $insName = $institutionData->name;
                                        $StudentOpenemis_no = $studentData->openemis_no;
                                        $StudentFirstName = $studentData->first_name;
                                        $StudentLastName = $studentData->last_name;
                                        $absenceCount = $this->find('all', ['conditions' => ['student_id' => $entity->student_id, 'institution_id' => $entity->institution_id, 'academic_period_id' => $entity->academic_period_id
                                        ]])->count();
                                        //POCOR-7266::Start
                                        $StudentMiddleName = $studentData->middle_name;
                                        $StudentThirdName = $studentData->third_name;
                                        $StudentPreferredName = $studentData->preferred_name;
                                        $StudentEmail = $studentData->email;
                                        $StudentAddress = $studentData->address;
                                        $StudentPostalCode = $studentData->postal_code;
                                        $StudentDOB = $studentData->date_of_birth;
                                        $StudentIDNO = $studentData->identity_number;
                                        $idTypeName = $idtypeData->name;
                                        $nationalName = $nationalData->name;
                                        $genderName = $genderData->name;

                                        $InsAddress = $institutionData->address;
                                        $InsPostalCode = $institutionData->postal_code;
                                        $InsContactPerson = $institutionData->contact_person;
                                        $InsPhone = $institutionData->telephone;
                                        $InsFax = $institutionData->fax;
                                        $InsEmail = $institutionData->email;
                                        $InsWebsite = $institutionData->website;
                                        $threshold = $alertRuleData1->threshold;

                                        $alertRuleMessage = $alertRuleData1->message;
                                        

                                        $searchKey1 = "/{$total_days}/i";  // Corrected syntax
                                        $searchKey11 = '${total_days}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey1)) {
                                            $alertRuleMessage = str_replace($searchKey11, $absenceCount, $alertRuleMessage);
                                        }

                                        
                                        $searchKey2 = "/{$threshold}/i";  // Corrected syntax
                                        $searchKey22 = '${threshold}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey2)) {
                                            $alertRuleMessage = str_replace($searchKey22, $threshold, $alertRuleMessage);
                                        }
                                        
                                        // Ensure that $userData is an object, not an array
                                        $searchKey3 = "/{$userData->openemis_no}/i";  // Corrected syntax
                                        $searchKey33 = '${user.openemis_no}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey3)) {
                                            $alertRuleMessage = str_replace($searchKey33, $StudentOpenemis_no, $alertRuleMessage);
                                        }

                                        $searchKey4 = "/{$userData->first_name}/i";  // Corrected syntax
                                        $searchKey44 = '${user.first_name}';
                                        if (!empty($searchKey4)) {
                                            $alertRuleMessage = str_replace($searchKey44, $StudentFirstName, $alertRuleMessage);
                                        }

                                        $searchKey5 = "/{$userData->middle_name}/i";  // Corrected syntax
                                        $searchKey55 = '${user.middle_name}';
                                        if (!empty($searchKey5)) {
                                            $alertRuleMessage = str_replace($searchKey55, $StudentMiddleName, $alertRuleMessage);
                                        }

                                        $searchKey6 = "/{$userData->third_name}/i";  // Corrected syntax
                                        $searchKey66 = '${user.third_name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey6)) {
                                            $alertRuleMessage = str_replace($searchKey66, $StudentThirdName, $alertRuleMessage);
                                        }

                                        $searchKey7 = "/{$userData->last_name}/i";  // Corrected syntax
                                        $searchKey77 = '${user.last_name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey7)) {
                                            $alertRuleMessage = str_replace($searchKey77, $StudentLastName, $alertRuleMessage);
                                        }

                                        $searchKey8 = "/{$userData->preferred_name}/i";  // Corrected syntax
                                        $searchKey88 = '${user.preferred_name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchKey8)) {
                                            $alertRuleMessage = str_replace($searchKey88, $StudentPreferredName, $alertRuleMessage);
                                        }

                                        $searchEmail = "/{$userData->email}/i";  // Corrected syntax
                                        $searchKeyEmail = '${user.email}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchEmail)) {
                                            $alertRuleMessage = str_replace($searchKeyEmail, $StudentEmail, $alertRuleMessage);
                                        }

                                        $searchAddress = "/{$userData->address}/i";  // Corrected syntax
                                        $searchKeyAddress = '${user.address}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchAddress)) {
                                            $alertRuleMessage = str_replace($searchKeyAddress, $StudentAddress, $alertRuleMessage);
                                        }

                                        $searchPC = "/{$userData->postal_code}/i";  // Corrected syntax
                                        $searchKeyPC = '${user.postal_code}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchPC)) {
                                            $alertRuleMessage = str_replace($searchKeyPC, $StudentPostalCode, $alertRuleMessage);
                                        }

                                        $searchDOB = "/{$userData->date_of_birth}/i";  // Corrected syntax
                                        $searchKeyDOB = '${user.date_of_birth}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchDOB)) {
                                            $alertRuleMessage = str_replace($searchKeyDOB, $StudentDOB, $alertRuleMessage);
                                        }

                                        $searchIDNO = "/{$userData->identity_number}/i";  // Corrected syntax
                                        $searchKeyIDNO = '${user.identity_number}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchIDNO)) {
                                            $alertRuleMessage = str_replace($searchKeyIDNO, $StudentIDNO, $alertRuleMessage);
                                        }

                                        $searchIDTypeName = "/{$idtypeData->identity_number}/i";  // Corrected syntax
                                        $searchKeyIDTypeName = '${user.main_identity_type.name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchIDTypeName)) {
                                            $alertRuleMessage = str_replace($searchKeyIDTypeName, $idTypeName, $alertRuleMessage);
                                        }
                                        $searchGender = "/{$genderData->name}/i";  // Corrected syntax
                                        $searchKeyGender = '${user.gender.name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchGender)) {
                                            $alertRuleMessage = str_replace($searchKeyGender, $genderName, $alertRuleMessage);
                                        }
                                        
                                        $searchNationality = "/{$nationalData->name}/i";  // Corrected syntax
                                        $searchKeyNationality = '${user.main_nationality.name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchNationality)) {
                                            $alertRuleMessage = str_replace($searchKeyNationality, $nationalName, $alertRuleMessage);
                                        }

                                        $searchName = "/{$institutionData->name}/i";  // Corrected syntax
                                        $searchKeyInsName = '${institution.name}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchName)) {
                                            $alertRuleMessage = str_replace($searchKeyInsName, $insName, $alertRuleMessage);
                                        }

                                        $searchCode = "/{$institutionData->code}/i";  // Corrected syntax
                                        $searchKeyCode = '${institution.code}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchCode)) {
                                            $alertRuleMessage = str_replace($searchKeyCode, $insCode, $alertRuleMessage);
                                        }

                                        $searchInsAddress = "/{$institutionData->address}/i";  // Corrected syntax
                                        $searchKeyInsAddress = '${institution.address}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchInsAddress)) {
                                            $alertRuleMessage = str_replace($searchKeyInsAddress, $InsAddress, $alertRuleMessage);
                                        }

                                        $searchPCode = "/{$institutionData->postal_code}/i";  // Corrected syntax
                                        $searchKeyPCode = '${institution.postal_code}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchPCode)) {
                                            $alertRuleMessage = str_replace($searchKeyPCode, $InsPostalCode, $alertRuleMessage);
                                        }

                                        $searchContactPerson = "/{$institutionData->contact_person}/i";  // Corrected syntax
                                        $searchKeyContactPerson = '${institution.contact_person}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchContactPerson)) {
                                            $alertRuleMessage = str_replace($searchKeyContactPerson, $InsContactPerson, $alertRuleMessage);
                                        }

                                        $searchPhone = "/{$institutionData->telephone}/i";  // Corrected syntax
                                        $searchKeyPhone = '${institution.telephone}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchPhone)) {
                                            $alertRuleMessage = str_replace($searchKeyPhone, $InsPhone, $alertRuleMessage);
                                        }

                                        $searchFax = "/{$institutionData->fax}/i";  // Corrected syntax
                                        $searchKeyFax = '${institution.fax}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchFax)) {
                                            $alertRuleMessage = str_replace($searchKeyFax, $InsFax, $alertRuleMessage);
                                        }

                                        $searchInsEmail = "/{$institutionData->email}/i";  // Corrected syntax
                                        $searchKeyInsEmail = '${institution.email}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchInsEmail)) {
                                            $alertRuleMessage = str_replace($searchKeyInsEmail, $InsEmail, $alertRuleMessage);
                                        }

                                        $searchWebsite = "/{$institutionData->website}/i";  // Corrected syntax
                                        $searchKeyWebsite = '${institution.website}';  // Corrected variable assignment

                                        // Ensure $searchKey3 is a valid regex pattern
                                        if (!empty($searchWebsite)) {
                                            $alertRuleMessage = str_replace($searchKeyWebsite, $InsWebsite, $alertRuleMessage);
                                        }
                                        
                                        //Comment for V4[START]
                                        //POCOR-7266::End
                                         //POCOR-8650::START
                                        if (($alertRuleData1->threshold) == $absenceCount) { //POCOR-7398 just changed <= to == also removed -1 after threshold
                                            $absenceCount = $absenceCount + 1;
                                            if (!empty($userData->email)) {
                                                $email = new Email('openemis');
                                                $emailSubject = 'OpenEMIS Attendance Alert for ' . $insCode . " - " . $insName;
                                                $emailMessage = $alertRuleMessage; //POCOR-7266
                                                // POCOR-8039 start
                                                try {
                                                    $email
                                                        ->setTo($userData->email)
                                                        ->setSubject($emailSubject)
                                                        ->send($emailMessage);
                                                } catch (\Exception $exception) {
                                                    $this->log($exception->getMessage(), 'error');
                                                }
                                                // POCOR-8039 end
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
                    $absenceEntity = $InstitutionStudentAbsences->newEmptyEntity();
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
            //POCOR-8631[START] webhook implementation 
            if (!empty($studentId)) {
                $Webhooks = TableRegistry::get('Webhook.Webhooks');

                $body = [
                    'institution_class_id' => $classId,
                    'education_grade_id' => $educationGradeId,
                    'academic_period_id' => $academicPeriodId,
                    'date' => $date,
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'absence_type_id' => $absenceTypeId,
                ];

                $body = json_encode($body);
                $Webhooks->triggerShell('attendance_update', ['username' => ''], $body);
            }
            //POCOR-8631[END]
        }
    }

    //POCOR-6584

    public function isShellStopExist($shellName)
    {
        // folder to the shellprocesses.
        $dir = new Folder(ROOT . DS . 'tmp'); // path
        $filesArray = $dir->find($shellName . '.stop');
        return !empty($filesArray);
    }
}
