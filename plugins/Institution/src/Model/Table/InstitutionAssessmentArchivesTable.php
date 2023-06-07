<?php
// POCOR-7339-HINDOL
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InstitutionAssessmentArchivesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);
        //START POCOR-7339-HINDOL todo return error or some message about need to create table
        $sourceTable = TableRegistry::get('Institution.AssessmentItemResults');
        $targetTableExists = $this->hasArchiveTable($sourceTable);
        if (!$targetTableExists) {
            return 0;
        }
        //END POCOR-7339-HINDOL
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true]);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'dependent' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true]);

        $this->behaviors()->get('ControllerAction')->config('actions.add', false);
        $this->behaviors()->get('ControllerAction')->config('actions.search', false);
        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'orientation' => 'landscape'
        ]);

        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function hasArchiveTable($sourceTable)
    {
        $sourceTableName = $sourceTable->table();
        $targetTableName = $sourceTableName . '_archived';
        $connection = ConnectionManager::get('default');
        $schemaCollection = new Collection($connection);
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);

        if ($tableExists) {
            return true;
        }

        $sourceTableSchema = $schemaCollection->describe($sourceTableName);

        // Create a new table schema for the target table
        $targetTableSchema = new Table($targetTableName);

        // Copy the columns from the source table to the target table
        foreach ($sourceTableSchema->columns() as $column) {
            $columnDefinition = $sourceTableSchema->column($column);
            $targetTableSchema->addColumn($column, $columnDefinition);
        }

        // Copy the indexes from the source table to the target table
        foreach ($sourceTableSchema->indexes() as $index) {
            $indexDefinition = $sourceTableSchema->index($index);
            $targetTableSchema->addIndex($index, $indexDefinition);
        }

        // Copy the constraints from the source table to the target table
        foreach ($sourceTableSchema->constraints() as $constraint) {
            $constraintDefinition = $sourceTableSchema->constraint($constraint);
            $targetTableSchema->addConstraint($constraint, $constraintDefinition);
        }

        // Generate the SQL statement to create the target table
        $createTableSql = $targetTableSchema->createSql($connection);

        // Execute the SQL statement to create the target table
        foreach ($createTableSql as $sql) {
            $connection->execute($sql);
        }

        // Check if the target table was created successfully
        $existingTables = $schemaCollection->listTables();
        $tableExists = in_array($targetTableName, $existingTables);
        if ($tableExists) {
            return true;
        }

        return false; // Return false if the table couldn't be created
    }

    public function onExcelBeforeGenerate(Event $event, ArrayObject $settings)
    {
        set_time_limit(0);//POCOR-7268 starts
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 9600); //POCOR-7268 ends
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionCode = $this->Institutions->get($institutionId)->code;
        $settings['file'] = str_replace($this->alias(), str_replace(' ', '_', $institutionCode) . '_Results_Archived', $settings['file']);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        // POCOR-7327 starts
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $conditions = [
            $this->aliasField('institution_id') => $institutionId,

            ];
        if(!empty($this->request->query)) {
            $selectedPeriodID =  $this->request->query['academic_period_id'];
            if(!empty($selectedPeriodID)){
            $conditions[$this->aliasField('academic_period_id')] = $selectedPeriodID;
            }

            $selectedAssessmentPeriodID = $this->request->query['assessment_period_id'];
            if(!empty($selectedAssessmentPeriodID)){
                $conditions[$this->aliasField('assessment_period_id')] = $selectedAssessmentPeriodID;
            }

        }
        $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');


        $Student = TableRegistry::get('User.Users');
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
        $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $query->innerJoin(
            [$AssessmentItemResultsArchived->alias() => $AssessmentItemResultsArchived->table()],
            [
                $AssessmentItemResultsArchived->aliasField('institution_classes_id = ') .  $this->aliasField('id'),
            ]
        )
            ->select([
            'institution_class_id' => $this->aliasField('id'),
            'id' => $this->aliasField('id'),
            'class_name' => $this->aliasField('name'),
            'name' => $this->aliasField('name'),
            'academic_period_id' => $AssessmentItemResultsArchived->aliasField('academic_period_id'),
            'assessment_id' => $AssessmentItemResultsArchived->aliasField('assessment_id'),
            'assessment_period_id' => $AssessmentItemResultsArchived->aliasField('assessment_period_id'),
            'education_subject_id' => $AssessmentItemResultsArchived->aliasField('education_subject_id'),
            'marks' => $AssessmentItemResultsArchived->aliasField('marks'),
            'assessment_name' => $Assessments->aliasField('name'),
            'assessment_period_name' => $AssessmentPeriods->aliasField('name'),
            'education_subject_name' => $EducationSubjects->aliasField('name'),
            'student_name' => $Student->find()->func()->concat([
                'Users.first_name' => 'literal',
                " ",
                'Users.last_name' => 'literal'
            ]),
            'openemis_no' => $Student->aliasField('openemis_no')
        ])
            ->innerJoin(
                [$Student->alias() => $Student->table()], [
                    $AssessmentItemResultsArchived->aliasField('student_id = ') . $Student->aliasField('id')
                ]
            )
            ->innerJoin(
                [$Assessments->alias() => $Assessments->table()], [
                    $AssessmentItemResultsArchived->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                ]
            )
            ->innerJoin(
                [$AssessmentPeriods->alias() => $AssessmentPeriods->table()], [
                    $AssessmentItemResultsArchived->aliasField('assessment_period_id = ') . $AssessmentPeriods->aliasField('id')
                ]
            )
            ->innerJoin(
                [$EducationSubjects->alias() => $EducationSubjects->table()], [
                    $AssessmentItemResultsArchived->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                ]
            )
            ->innerJoin(
                [$Assessments->alias() => $Assessments->table()], [
                    $AssessmentItemResultsArchived->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                ]
            )
            ->innerJoin(
                [$AssessmentPeriods->alias() => $AssessmentPeriods->table()], [
                    $AssessmentItemResultsArchived->aliasField('assessment_period_id = ') . $AssessmentPeriods->aliasField('id')
                ]
            )
            ->innerJoin(
                [$EducationSubjects->alias() => $EducationSubjects->table()], [
                    $AssessmentItemResultsArchived->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                ]
            )
            ->where($conditions)
            // POCOR-7339-HINDOL only archived will be shown
        ;
    }
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'class_name',
            'type' => 'string',
            'label' => 'Class Name',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'assessment_name',
            'type' => 'string',
            'label' => 'Assessment',
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => 'Academic Period',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'assessment_period_name',
            'type' => 'string',
            'label' => 'Assessment Period',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'education_subject_name',
            'type' => 'string',
            'label' => 'Subject',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => 'Student Name',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'marks',
            'type' => 'string',
            'label' => 'Mark'
        ];

        // $newFields[] = [
        //     'key' => 'Users.date_of_birth',
        //     'field' => 'dob',
        //     'type' => 'date',
        //     'label' => '',
        // ];

        // $newFields[] = [
        //     'key' => 'Examinations.education_grade',
        //     'field' => 'education_grade',
        //     'type' => 'string',
        //     'label' => '',
        // ];

        // $newFields[] = [
        //     'key' => 'InstitutionExaminationStudents.institution_id',
        //     'field' => 'institution_id',
        //     'type' => 'integer',
        //     'label' => '',
        // ];

        $fields->exchangeArray($newFields);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        set_time_limit(0);//POCOR-7268 starts
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 9600); //POCOR-7268 ends

//        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
//        //POCOR-7268 starts
//        //$query = $InstitutionClassStudentsTable->find();
//        $session = $this->request->session();
//        $institutionId = $session->read('Institution.Institutions.id');
//        $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');
//
//
//        $limit = 10;
//        $loop_no = 0;
//        $session = $this->request->session();
//        $institutionId = $session->read('Institution.Institutions.id');
//
//        $Classes = TableRegistry::get('Institution.InstitutionClasses');
//        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
//        $Assessments = TableRegistry::get('Assessment.Assessments');
//        $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');
//
//        $EducationGrades = TableRegistry::get('Education.EducationGrades');
//        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
//        $archive_query =         $query
//            ->select([
//                'institution_class_id' => $ClassGrades->aliasField('institution_class_id'),
//                'education_grade_id' => $Assessments->aliasField('education_grade_id'),
//                'assessment_id' => $Assessments->aliasField('id'),
//                'assessment' => $query->func()->concat([
//                    $Assessments->aliasField('code') => 'literal',
//                    " - ",
//                    $Assessments->aliasField('name') => 'literal'
//                ])
//            ])
//            ->innerJoin(
//                [$ClassGrades->alias() => $ClassGrades->table()],
//                [$ClassGrades->aliasField('institution_class_id = ') . $this->aliasField('id')]
//            )
//            ->innerJoin(
//                [$Assessments->alias() => $Assessments->table()],
//                [
//                    $Assessments->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id'),
//                    $Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
//                ]
//            )
//            // POCOR-7339-HINDOL only archived will be shown
//            ->innerJoin(
//                [$AssessmentItemResultsArchived->alias() => $AssessmentItemResultsArchived->table()],
//                [
//                    $AssessmentItemResultsArchived->aliasField('assessment_id = ') . $Assessments->aliasField('id'),
//                ]
//            )
//            ->innerJoin(
//                [$EducationGrades->alias() => $EducationGrades->table()],
//                [$EducationGrades->aliasField('id = ') . $Assessments->aliasField('education_grade_id')]
//            )
//            ->innerJoin(
//                [$EducationProgrammes->alias() => $EducationProgrammes->table()],
//                [$EducationProgrammes->aliasField('id = ') . $EducationGrades->aliasField('education_programme_id')]
//            )
//            ->group([
//                $ClassGrades->aliasField('institution_class_id'),
//                /** Ticket : POCOR-6480
//                 * Added because showing same records multiple time
//                 * $Assessments->aliasField('id')
//                 **/
//            ]);
//
//        do {
//            $query = $InstitutionClassStudentsTable->find('all', array(
//                    'conditions' => array($InstitutionClassStudentsTable->aliasField('academic_period_id') => $academic_period_id, $InstitutionClassStudentsTable->aliasField('institution_id') => $institutionId),
//                    'limit' => $limit,
//                    'offset' => $limit * $loop_no,
//                    //'order'  => 'id asc',
//                    'recursive' => -1)
//            )                // POCOR-7339-HINDOL only archived will be shown
//            ->innerJoin(
//                [$AssessmentItemResultsArchived->alias() => $AssessmentItemResultsArchived->table()],
//                [
//                    $AssessmentItemResultsArchived->aliasField('institution_classes_id = ') . $InstitutionClassStudentsTable->aliasField('institution_class_id'),
//                ]
//            )
//            ;
//            $loop_no++;
//        } while (count($query) == $limit);//POCOR-7268 ends
//
//        // For filtering all classes and my classes
//        $AccessControl = $this->AccessControl;
//        $userId = $this->Session->read('Auth.User.id');
//        $institutionId = $this->Session->read('Institution.Institutions.id');
//        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
//
//        $allSubjectsPermission = true;
//        $mySubjectsPermission = true;
//        $allClassesPermission = true;
//        $myClassesPermission = true;
//
//        if (!$AccessControl->isAdmin()) {
//            if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles)) {
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
//        if ($assessmentId) {
//            $sheets[] = [
//                'name' => $this->alias() . ' Archived Results',
//                'table' => $InstitutionClassStudentsTable,
//                'query' => $query,
//                'assessmentId' => $assessmentId,
//                'staffId' => $userId,
//                'institutionId' => $institutionId,
//                'mySubjectsPermission' => $mySubjectsPermission,
//                'allSubjectsPermission' => $allSubjectsPermission,
//                'allClassesPermission' => $allClassesPermission,
//                'myClassesPermission' => $myClassesPermission,
//                'orientation' => 'landscape'
//            ];
//        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('institution_unit_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_course_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_shift_id', ['visible' => false]);
        $this->field('capacity', ['visible' => false]);

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
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Assessments = TableRegistry::get('Assessment.Assessments');
        $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
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
            ->group([
                $ClassGrades->aliasField('institution_class_id'),
                /** Ticket : POCOR-6480
                 * Added because showing same records multiple time
                $Assessments->aliasField('id')
                 **/
            ])
            ->autoFields(true)
        ;
//        $query
//            ->select([
//                'institution_class_id' => $this->aliasField('id'),
//                'education_grade_id' => $AssessmentItemResultsArchived->aliasField('education_grade_id'),
//                'assessment_id' => $AssessmentItemResultsArchived->aliasField('assessment_id'),
//                'assessment' => $query->func()->concat([
//                    $Assessments->aliasField('code') => 'literal',
//                    " - ",
//                    $Assessments->aliasField('name') => 'literal'
//                ])
//            ])
//            ->distinct($this->aliasField('id'))
//            ->innerJoin(
//                [$AssessmentItemResultsArchived->alias() => $AssessmentItemResultsArchived->table()],
//                [
//                    $AssessmentItemResultsArchived->aliasField('institution_classes_id = ') . $this->aliasField('id'),
//                ]
//            )
//            ->innerJoin(
//                [$Assessments->alias() => $Assessments->table()],
//                [
//                    $AssessmentItemResultsArchived->aliasField('assessment_id = ') . $Assessments->aliasField('id'),
////                    $Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
//                ]
//            )
//            // POCOR-7339-HINDOL only archived will be shown
////            ->where([$AssessmentItemResultsArchived->aliasField('institution_id') => $institutionId])
//            ->autoFields(true);

        $extra['options']['order'] = [
            $EducationProgrammes->aliasField('order') => 'asc',
            $EducationGrades->aliasField('order') => 'asc',
            $Assessments->aliasField('code') => 'asc',
            $Assessments->aliasField('name') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        // For filtering all classes and my classes
        $AccessControl = $this->AccessControl;
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

        // Academic Periods
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true]);
        //POCOR-7339-HINDOL Get uneditable years as well
        if (is_null($this->request->query('academic_period_id'))) {
            // default to current Academic Period
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
// POCOR-7339-HINDOL TO ADD            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAssessments')),
            'message' => '{{label}} - No Archived Assessments ',
            'callable' => function ($id) use ($institutionId, $AssessmentItemResultsArchived) {
                return $AssessmentItemResultsArchived
                    ->find()
                    ->distinct([$AssessmentItemResultsArchived->aliasField('academic_period_id')])
                    ->where([
                        $AssessmentItemResultsArchived->aliasField('institution_id') => $institutionId,
                        $AssessmentItemResultsArchived->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        //POCOR-7339-HINDOL end
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        if (!empty($selectedPeriod)) {
            $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

            // Assessments
            $assessmentOptions = $Assessments
                ->find('list')
                ->where([$Assessments->aliasField('academic_period_id') => $selectedPeriod])
                ->toArray();
            $assessmentOptions = /*['-1' => __('All Assessments')] +*/
                $assessmentOptions; //comment `All Assessments` option POCOR-6906
            $selectedAssessment = $this->queryString('assessment_id', $assessmentOptions);
            $this->advancedSelectOptions($assessmentOptions, $selectedAssessment, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
                'callable' => function ($id) use ($Classes, $ClassGrades, $Assessments, $institutionId, $selectedPeriod) {
                    if ($id == -1) {
                        return 1;
                    }
                    $selectedGrade = $Assessments->get($id)->education_grade_id;
                    return $Classes
                        ->find()
                        ->innerJoin(
                            [$ClassGrades->alias() => $ClassGrades->table()],
                            [
                                $ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id'),
                                $ClassGrades->aliasField('education_grade_id') => $selectedGrade
                            ]
                        )
                        ->where([
                            $Classes->aliasField('institution_id') => $institutionId,
                            $Classes->aliasField('academic_period_id') => $selectedPeriod
                        ])
                        ->count();
                }
            ]);
            $this->controller->set(compact('assessmentOptions', 'selectedAssessment'));
            // End

            if ($selectedAssessment != '-1') {
                $query->where([$Assessments->aliasField('id') => $selectedAssessment]);
            }
        }

        $assessmentId = $this->request->query('assessment_id');

        if ($assessmentId == -1 || !$assessmentId || !$this->AccessControl->check(['Institutions', 'Assessments', 'excel'], $roles)) {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }

//        $query->bind(':param1', ' - ');


//        $sql = $query->sql();
//        $this->log("institutionId $institutionId", 'debug');
//        $this->log("selectedAssessment$selectedAssessment", 'debug');
//        $this->log("selectedPeriod $selectedPeriod", 'debug');
//
//        $this->log($sql, 'debug');

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'name') {
            return __('Class Name');
        } else if ($field == 'total_male_students') {
            return __('Male Students');
        } else if ($field == 'total_female_students') {
            return __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $grade = $EducationGrades->get($entity->education_grade_id);

        return $grade->programme_grade_name;
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
    public function onGetTotalMaleStudents(Event $event, Entity $entity)
    {
        //POCOR-7339-HINDOL check query string
        $genderCode = "M";
        $count = $this->getGenderStudentsCount($entity, $genderCode);
        return $count;
    }

    /**
     * Function to get Total Female Students on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return int
     */
    public function onGetTotalFemaleStudents(Event $event, Entity $entity)
    {
        $genderCode = 'F';
        $count = $this->getGenderStudentsCount($entity, $genderCode);
        return $count;
    }

    /**
     * Function to get class name on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return string
     */
    public function onGetName(Event $event, Entity $entity)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $class = $InstitutionClasses->get($entity->institution_class_id);
        return $class->name;
    }

    /**
     * @param Entity $entity
     * @param $genderCode
     * @return int|mixed|string
     */
    private function getGenderStudentsCount(Entity $entity, $genderCode)
    {

        $grade = $entity->education_grade_id;
        $class = $entity->institution_class_id;
        $institutionId = $entity->institution->id;
        $period = $entity->academic_period->id;
//        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $Users = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $AssessmentItemResultsArchived = TableRegistry::get('Institution.AssessmentItemResultsArchived');
        $count = $AssessmentItemResultsArchived->find()
            ->distinct([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
            ->select([$AssessmentItemResultsArchived->aliasField('student_id')])// POCOR-7339-HINDOL
            ->innerJoin([$Users->alias() => $Users->table()], [
                $Users->aliasField('id') . ' = ' . $AssessmentItemResultsArchived->aliasField('student_id')
            ])
            ->innerJoin([$Genders->alias() => $Genders->table()], [
                $Genders->aliasField('id') . ' = ' . $Users->aliasField('gender_id')
            ])
            ->where([
                $AssessmentItemResultsArchived->aliasField('institution_classes_id') => $class,
                $AssessmentItemResultsArchived->aliasField('education_grade_id') => $grade,
                $AssessmentItemResultsArchived->aliasField('academic_period_id') => $period,
                $AssessmentItemResultsArchived->aliasField('institution_id') => $institutionId,
                $Genders->aliasField('code') => $genderCode,
//                    $InstitutionClassStudentsTable->aliasField('student_status_id') => 1 // POCOR-7339-HINDOL results for all types of students
            ])->first();
        if($count) {
            $count = "<i class='fa fa-check'></i>"; //POCOR-HINDOL just optimize count
        }
        if(!$count) {
            $count = 0;
        }
        return $count;
    }
}
