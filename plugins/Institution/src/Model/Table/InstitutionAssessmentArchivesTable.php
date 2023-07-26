<?php
// POCOR-7339-HINDOL
namespace Institution\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InstitutionAssessmentArchivesTable extends ControllerActionTable
{
    private $institutionId;
    private $academicPeriodId;
    private $assessmentId;
    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

        $this->behaviors()->get('ControllerAction')->config('actions.add', false);
        $this->behaviors()->get('ControllerAction')->config('actions.search', false);
//        $this->addBehavior('Excel', [
//            'pages' => ['index'],
//            'orientation' => 'landscape'
//        ]);

        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return '-' . $relatedField;
        }
        return '+' . $relatedField;
    }


//    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
//        set_time_limit(0);//POCOR-7268 starts
//        ini_set('memory_limit', -1);
//        ini_set('max_execution_time', 9600); //POCOR-7268 ends
//
//        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
//        //POCOR-7268 starts
//        //$query = $InstitutionClassStudentsTable->find();
//        $session = $this->request->session();
//        $institutionId = $session->read('Institution.Institutions.id');
//        if(!empty($this->request->query('assessment_id'))){
//            $academic_period_id = $this->request->query('academic_period_id');
//            $assessmentId = $this->request->query('assessment_id');
//        }else{
//            // Assessments
//            $academic_period_id = $this->AcademicPeriods->getCurrent();
//            $Assessments = TableRegistry::get('Assessment.Assessments');
//            $assessmentOptions = $Assessments
//                ->find('list')
//                ->where([$Assessments->aliasField('academic_period_id') => $academic_period_id])
//                ->toArray();
//            if(!empty($assessmentOptions)){
//                $assessmentId = $assessmentOptions;
//                $assessmentId = array_keys($assessmentOptions)[0];
//            }
//        }
//
//        $limit = 10;
//        $loop_no = 0;
//        do {
//            $query = $InstitutionClassStudentsTable->find('all', array(
//                    'conditions' => array($InstitutionClassStudentsTable->aliasField('academic_period_id') => $academic_period_id, $InstitutionClassStudentsTable->aliasField('institution_id') => $institutionId),
//                    'limit'  => $limit,
//                    'offset' => $limit * $loop_no,
//                    //'order'  => 'id asc',
//                    'recursive' => -1)
//            );
//            $loop_no++;
//        } while (count($query) == $limit);//POCOR-7268 ends
//
//        // For filtering all classes and my classes
//        $AccessControl = $this->AccessControl;
//        $userId = $this->Session->read('Auth.User.id');
//        $institutionId = $this->Session->read('Institution.Institutions.id');
//
//        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
//
//        $allSubjectsPermission = true;
//        $mySubjectsPermission = true;
//        $allClassesPermission = true;
//        $myClassesPermission = true;
//
//        if (!$AccessControl->isAdmin())
//        {
//            if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) ) {
//                $allSubjectsPermission = false;
//                $mySubjectsPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
//            }
//
//            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
//                $allClassesPermission = false;
//                $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
//            }
//        }
//
//        if($assessmentId) {
//            $sheets[] = [
//                'name' => $this->alias,
//                'table' => $InstitutionClassStudentsTable,
//                'query' => $query,
//                'assessmentId' => $assessmentId,
//                'staffId' => $userId,
//                'institutionId' => $institutionId,
//                'academicPeriodId' => $this->academicPeriodId,
//                'assessmentId' => $assessmentId,
//                'mySubjectsPermission' => $mySubjectsPermission,
//                'allSubjectsPermission' => $allSubjectsPermission,
//                'allClassesPermission' => $allClassesPermission,
//                'myClassesPermission' => $myClassesPermission,
//                'orientation' => 'landscape',
//                'archive' => true
//            ];
//        }
//    }
//
//    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
//    {
//        $newFields = [];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'class_name',
//            'type' => 'string',
//            'label' => 'Class Name',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'assessment_name',
//            'type' => 'string',
//            'label' => 'Assessment',
//        ];
//        $newFields[] = [
//            'key' => '',
//            'field' => 'academic_period_id',
//            'type' => 'integer',
//            'label' => 'Academic Period',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'assessment_period_name',
//            'type' => 'string',
//            'label' => 'Assessment Period',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'education_subject_name',
//            'type' => 'string',
//            'label' => 'Subject',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'openemis_no',
//            'type' => 'string',
//            'label' => 'OpenEMIS ID',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'student_name',
//            'type' => 'string',
//            'label' => 'Student Name',
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'marks',
//            'type' => 'string',
//            'label' => 'Mark'
//        ];
//
//        // $newFields[] = [
//        //     'key' => 'Users.date_of_birth',
//        //     'field' => 'dob',
//        //     'type' => 'date',
//        //     'label' => '',
//        // ];
//
//        // $newFields[] = [
//        //     'key' => 'Examinations.education_grade',
//        //     'field' => 'education_grade',
//        //     'type' => 'string',
//        //     'label' => '',
//        // ];
//
//        // $newFields[] = [
//        //     'key' => 'InstitutionExaminationStudents.institution_id',
//        //     'field' => 'institution_id',
//        //     'type' => 'integer',
//        //     'label' => '',
//        // ];
//
//        $fields->exchangeArray($newFields);
//    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $user = self::getRelatedRecord('security_users', $entity->student_id);

        return $user['openemis_no'];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('institution_unit_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_course_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_shift_id', ['visible' => false]);
        $this->field('capacity', ['visible' => true]);
        $this->field('total_male_students', ['visible' => false]);
        $this->field('total_female_students', ['visible' => false]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'Student Assessment Archive', 'Students');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('assessment');
        $this->field('education_grade');
        $this->field('subjects');

        $this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'total_male_students', 'total_female_students']);

        // POCOR-7339-HINDOL

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $this->institutionId = $institutionId;
        list($query, $extra) = $this->setBasicQuery($query, $extra);
        list($query, $extra) = $this->setAccessControlledQuery($query, $extra);

        $Assessments = TableRegistry::get('Assessment.Assessments');
        $selectedAcademicPeriod = !is_null(
            $this->request->query('academic_period_id'))
            ?
            $this->request->query('academic_period_id')
            :
            $this->AcademicPeriods->getCurrent();
        $selectedAssessment = !is_null(
            $this->request->query('assessment_id'))
            ?
            $this->request->query('assessment_id')
            :
            null;

        $selectedAcademicPeriod = $this->setAcademicPeriodOptions($selectedAcademicPeriod);

        if (!empty($selectedAcademicPeriod)) {
            $selectedAssessment = $this->setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment);
            $where[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
            $this->academicPeriodId = $selectedAcademicPeriod;
        }
        if ($selectedAssessment != '-1') {
            $query->where([$Assessments->aliasField('id') => $selectedAssessment]);
            $this->assessmentId = $selectedAssessment;
        }

        $whereArchive = ['institution_id = ' . $institutionId];
        if ($selectedAssessment != '-1') {
            $whereArchive['assessment_id'] = $selectedAssessment;
        }
        if ($selectedAcademicPeriod) {
            $whereArchive['academic_period_id'] = $selectedAcademicPeriod;
        }
        $classes_array = [0];
        $archived_classes_array =
            ArchiveConnections::getArchiveClasses('assessment_item_results',
                $whereArchive);
        if (sizeof($archived_classes_array) > 0) {
            $classes_array = $archived_classes_array;
        }
        $query->where([$this->aliasField('id IN') => $classes_array]);

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else if ($field == 'capacity') {
            return __('Students With Assessments');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity)
    {
        $EducationGrade = self::getRelatedRecord('education_grades', $entity->education_grade_id);
        return $EducationGrade['programme_grade_name'];
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view']['url'])) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'AssessmentItemResultsArchived'
            ];

            $buttons['view']['url'] = $this->setQueryString($url, [
                'class_id' => $entity->institution_class_id,
                'institution_class_id' => $entity->id,
                'assessment_id' => $entity->assessment_id,
                'assessment_period_id' => $entity->assessment_period_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id
            ]);
        }

        return $buttons;
    }

    /**
     * Function to get Total Male Students on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return int
     */
    public function onGetCapacity(Event $event, Entity $entity)
    {
        //POCOR-7339-HINDOL check query string
        $whereArchive = [
            'institution_classes_id' => $entity->id,
            'assessment_id' => $entity->assessment_id,
            'education_grade_id' => $entity->education_grade_id
        ];
        $archived_students_array =
            ArchiveConnections::getArchiveStudents('assessment_item_results',
                $whereArchive);
        return sizeof($archived_students_array);
    }

    /**
     * Function to get class name on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return string
     */
    public function onGetName(Event $event, Entity $entity)
    {
        $class = self::getRelatedRecord('institution_classes', $entity->institution_class_id);
        return $class['name'];
    }


    /**
     * @param Query $query
     * @param ArrayObject $extra
     * @return array
     */
    private function setBasicQuery(Query $query, ArrayObject $extra)
    {
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $EducationGrades->aliasField('order') => 'asc',
            $Assessments->aliasField('code') => 'asc',
            $Assessments->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        $query
            ->select([
                'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
                'education_grade_id' => $Assessments->aliasField('education_grade_id'),
                'assessment_id' => $Assessments->aliasField('id'),
                'assessment' => $query->func()->concat([
                    $Assessments->aliasField('code') => 'literal',
                    " - ",
                    $Assessments->aliasField('name') => 'literal'
                ])
            ])
            ->distinct([$this->aliasField('id')])
            ->innerJoin(
                [$ClassGrades->alias() => $ClassGrades->table()],
                [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
            )
            ->innerJoin(
                [$Assessments->alias() => $Assessments->table()],
                [
                    $Assessments->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
                    $Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
                ]
            )
            ->innerJoin(
                [$EducationGrades->alias() => $EducationGrades->table()],
                [$EducationGrades->aliasField('id = ') . $Assessments->aliasField('education_grade_id')]
            )
            ->innerJoin(
                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
            )
            ->autoFields(true);
        return array($query, $extra);
    }

    /**
     * @param Query $query
     * @param $session
     * @param $institutionId
     * @return array
     */
    private function setAccessControlledQuery(Query $query, ArrayObject $extra)
    {
        $AccessControl = $this->AccessControl;
        if ($AccessControl->isAdmin()) {
            return array($query, $extra);
        }
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $userId = $session->read('Auth.User.id');
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
        if (!$AccessControl->isAdmin()) {
            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
                $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                if (!$classPermission && !$subjectPermission) {
                    $query->where(['1 = 0'], [], true);
                } else {
                    $query
                        ->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                            'InstitutionClasses.id = ' . $ClassGrades->aliasField('institution_class_id'),
                        ])
                        ->leftJoin(['ClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            'ClassesSecondaryStaff.institution_class_id = InstitutionClasses.id'
                        ]);

                    // If only class permission is available but no subject permission available
                    if ($classPermission && !$subjectPermission) {
                        $query->where([
                            'OR' => [
                                ['InstitutionClasses.staff_id' => $userId],
                                ['ClassesSecondaryStaff.secondary_staff_id' => $userId]
                            ]
                        ]);
                    } else {
                        $query
                            ->innerJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                                'InstitutionClassSubjects.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassSubjects.status =   1'
                            ])
                            ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id'
                            ]);

                        // If both class and subject permission is available
                        if ($classPermission && $subjectPermission) {
                            $query->where([
                                'OR' => [
                                    ['InstitutionClasses.staff_id' => $userId],
                                    ['ClassesSecondaryStaff.secondary_staff_id' => $userId],
                                    ['InstitutionSubjectStaff.staff_id' => $userId]
                                ]
                            ]);
                        } // If only subject permission is available
                        else {
                            $query->where(['InstitutionSubjectStaff.staff_id' => $userId]);
                        }
                    }
                }
            }
        }
        if (!$this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles)) {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }

        return array($query, $extra);
    }

    private function setAcademicPeriodOptions($selectedAcademicPeriod)
    {
// Academic Periods filter

        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
//
        $institutionId = $this->institutionId;
        $academicPeriodStudentAttendanceArray = ArchiveConnections::getArchiveYears('assessment_item_results',
            ['institution_id' => $institutionId]);
        if (!$selectedAcademicPeriod) {
            $selectedYear = end($academicPeriodStudentAttendanceArray);
        } else {
            $selectedYear = $selectedAcademicPeriod;
        }
        $periodOptions = $AcademicPeriod->getArchivedYearList($academicPeriodStudentAttendanceArray);
        if (empty($selectedAcademicPeriod)) {
            $this->request->query['academic_period_id'] = $selectedYear;
        }

        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        return $selectedPeriod;

    }

    /**
     * @param Query $selectedAcademicPeriod
     * @return array
     */
    private function setAssessmentOptions($selectedAcademicPeriod, $selectedAssessment = -1)
    {
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $assessment_array = [0];
        $institutionId = $this->institutionId;
        $whereArchive = ['academic_period_id' => $selectedAcademicPeriod,
            'institution_id' => $institutionId];
        $archived_assessment_array =
            ArchiveConnections::getArchiveAssessments('assessment_item_results',
                $whereArchive);
        if (sizeof($archived_assessment_array) > 0) {
            $assessment_array = $archived_assessment_array;
        }
//        $this->log('assessments_array', 'debug');
//        $this->log($assessment_array, 'debug');
        $where = [$Assessments->aliasField('academic_period_id') => $selectedAcademicPeriod,
            $Assessments->aliasField('id IN') => $assessment_array];

        $assessmentOptions = $Assessments
            ->find('list')
            ->where($where)
            ->toArray();
        $assessmentOptions = ['-1' => __('All Assessments')] + $assessmentOptions;
        $selectedAssessment = $this->advancedSelectOptions($assessmentOptions, $selectedAssessment);
        $this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
        return $selectedAssessment;
    }

}
