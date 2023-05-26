<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class InstitutionAssessmentsTable extends ControllerActionTable {
    public function initialize(array $config) {
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
        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'orientation' => 'landscape'
        ]);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportAssessmentItemResults']);

        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {
        set_time_limit(0);//POCOR-7268 starts
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 9600); //POCOR-7268 ends
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionCode = $this->Institutions->get($institutionId)->code;
        $settings['file'] = str_replace($this->alias(), str_replace(' ', '_', $institutionCode).'_Results', $settings['file']);
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
        set_time_limit(0);//POCOR-7268 starts
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 9600); //POCOR-7268 ends

        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        //POCOR-7268 starts
        //$query = $InstitutionClassStudentsTable->find();
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        if(!empty($this->request->query('assessment_id'))){
            $academic_period_id = $this->request->query('academic_period_id');
            $assessmentId = $this->request->query('assessment_id');
        }else{
            // Assessments
            $academic_period_id = $this->AcademicPeriods->getCurrent();
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $assessmentOptions = $Assessments
                ->find('list')
                ->where([$Assessments->aliasField('academic_period_id') => $academic_period_id])
                ->toArray();
            if(!empty($assessmentOptions)){
                $assessmentId = $assessmentOptions;
                $assessmentId = array_keys($assessmentOptions)[0];
            }
        }
            
        $limit = 10;
        $loop_no = 0;
        do {
            $query = $InstitutionClassStudentsTable->find('all', array(
                'conditions' => array($InstitutionClassStudentsTable->aliasField('academic_period_id') => $academic_period_id, $InstitutionClassStudentsTable->aliasField('institution_id') => $institutionId),
                'limit'  => $limit,
                'offset' => $limit * $loop_no,
                //'order'  => 'id asc',
                'recursive' => -1)
            );
            $loop_no++;
        } while (count($query) == $limit);//POCOR-7268 ends
        
        // For filtering all classes and my classes
        $AccessControl = $this->AccessControl;
        $userId = $this->Session->read('Auth.User.id');
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);

        $allSubjectsPermission = true;
        $mySubjectsPermission = true;
        $allClassesPermission = true;
        $myClassesPermission = true;

        if (!$AccessControl->isAdmin())
        {
            if (!$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) ) {
                $allSubjectsPermission = false;
                $mySubjectsPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
            }

            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles)) {
                $allClassesPermission = false;
                $myClassesPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
            }
        }

        if($assessmentId) {
            $sheets[] = [
                'name' => $this->alias(),
                'table' => $InstitutionClassStudentsTable,
                'query' => $query,
                'assessmentId' => $assessmentId,
                'staffId' => $userId,
                'institutionId' => $institutionId,
                'mySubjectsPermission' => $mySubjectsPermission,
                'allSubjectsPermission' => $allSubjectsPermission,
                'allClassesPermission' => $allClassesPermission,
                'myClassesPermission' => $myClassesPermission,
                'orientation' => 'landscape'
            ];
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('class_number', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('institution_unit_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_course_id', ['visible' => false]);//POCOR-6863
        $this->field('institution_shift_id', ['visible' => false]);
        $this->field('capacity', ['visible' => false]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Student Assessment Archive','Students');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
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

    public function indexBeforeAction(Event $event, ArrayObject $extra) {
        $session = $this->Session;
        $archive_query_string = $session->read('archive_query_string.queryString');
        $extra['elements']['controls'] = ['name' => 'Institution.Assessment/controls', 'data' => [], 'options' => [], 'order' => 1];

        $this->field('assessment');
        $this->field('education_grade');
        $this->field('subjects');

        $this->setFieldOrder(['name', 'assessment', 'academic_period_id', 'education_grade', 'subjects', 'total_male_students', 'total_female_students']);

        // from onUpdateToolbarButtons
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $buttons = $extra['indexButtons'];
        $superAdmin = $session->read('Auth.User.super_admin');
        $is_connection_is_online = $session->read('is_connection_stablished');
        if( ($is_connection_is_online == 1) ){
            $extraButtons = [
                'archive' => [
                    // 'AssessmentItemResultsArchived' => ['Institutions', 'AssessmentItemResultsArchived', 'index', 'queryString' => $archive_query_string],
                    // 'action' => 'AssessmentItemResultsArchived',
                    // 'icon' => '<i class="fa fa-folder"></i>',
                    // 'title' => __('Archive'),
                    // 'queryString' => $archive_query_string

// POCOR-7339-HINDOL temp down
//                    'plugin' => 'Institutions',
//                    'controller' => 'Institution',
//                    'action' => 'AssessmentItemResultsArchived',
//                    'icon' => '<i class="fa fa-folder"></i>',
//                    'title' => __('Archive'),
//                    'queryString' => $archive_query_string
                    'plugin' => 'Institutions',
                    'controller' => 'Institution',
                    'action' => 'AssessmentArchives',
                    'icon' => '<i class="fa fa-folder"></i>',
                    'title' => __('Archive'),
                    'queryString' => $archive_query_string
                ]
            ];
    
            foreach ($extraButtons as $key => $attr) {
                if ($this->AccessControl->check($attr['permission'])) {
                    $button = [
                        'type' => 'button',
                        'attr' => $btnAttr,
                        'url' => ['queryString' => $archive_query_string]
                    ];
                    // echo "<pre>";print_r($attr);die;
                    $button['url']['action'] = $attr['action'];
                    $button['attr']['title'] = $attr['title'];
                    $button['label'] = $attr['icon'];
    
                    $extra['toolbarButtons'][$key] = $button;
                }
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $Assessments = TableRegistry::get('Assessment.Assessments');
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
        if (!$AccessControl->isAdmin())
        {
            if (!$AccessControl->check(['Institutions', 'AllClasses', 'index'], $roles) && !$AccessControl->check(['Institutions', 'AllSubjects', 'index'], $roles) )
            {
                $classPermission = $AccessControl->check(['Institutions', 'Classes', 'index'], $roles);
                $subjectPermission = $AccessControl->check(['Institutions', 'Subjects', 'index'], $roles);
                if (!$classPermission && !$subjectPermission)
                {
                    $query->where(['1 = 0'], [], true);
                } else
                {
                    $query
                        ->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                        'InstitutionClasses.id = '.$ClassGrades->aliasField('institution_class_id'),
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
                        }
                        // If only subject permission is available
                        else {
                            $query->where(['InstitutionSubjectStaff.staff_id' => $userId]);
                        }
                    }
                }
            }
        }

        // Academic Periods
        $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
        if (is_null($this->request->query('academic_period_id'))) {
            // default to current Academic Period
            $this->request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedPeriod = $this->queryString('academic_period_id', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noAssessments')),
            'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId) {
                return $Classes
                    ->find()
                    ->innerJoin(
                        [$ClassGrades->alias() => $ClassGrades->table()],
                        [
                            $ClassGrades->aliasField('institution_class_id = ') . $Classes->aliasField('id')
                        ]
                    )
                    ->innerJoin(
                        [$Assessments->alias() => $Assessments->table()],
                        [
                            $Assessments->aliasField('academic_period_id = ') . $Classes->aliasField('academic_period_id'),
                            $Assessments->aliasField('education_grade_id = ') . $ClassGrades->aliasField('education_grade_id')
                        ]
                    )
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        $this->controller->set(compact('periodOptions', 'selectedPeriod'));
        // End

        if (!empty($selectedPeriod)) {
            $query->where([$this->aliasField('academic_period_id') => $selectedPeriod]);

            // Assessments
            $assessmentOptions = $Assessments
                ->find('list')
                ->where([$Assessments->aliasField('academic_period_id') => $selectedPeriod])
                ->toArray();
            $assessmentOptions = /*['-1' => __('All Assessments')] +*/ $assessmentOptions; //comment `All Assessments` option POCOR-6906 
            $selectedAssessment = $this->queryString('assessment_id', $assessmentOptions);
            $this->advancedSelectOptions($assessmentOptions, $selectedAssessment, [
                'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
                'callable' => function($id) use ($Classes, $ClassGrades, $Assessments, $institutionId, $selectedPeriod) {
                    if ($id == -1) { return 1; }
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
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true) {
        if ($field == 'name') {
            return __('Class Name');
        } else if ($field == 'total_male_students') {
            return  __('Male Students');
        } else if ($field == 'total_female_students') {
            return  __('Female Students');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetEducationGrade(Event $event, Entity $entity) {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $grade = $EducationGrades->get($entity->education_grade_id);

        return $grade->programme_grade_name;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view']['url'])) {
            $url = [
                'plugin' => $this->controller->plugin,
                'controller' => $this->controller->name,
                'action' => 'Results'
            ];

            $buttons['view']['url'] = $this->setQueryString($url, [
                'class_id' => $entity->institution_class_id,
                'assessment_id' => $entity->assessment_id,
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
    public function onGetTotalMaleStudents(Event $event, Entity $entity) {
        $url = [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'AssessmentItemResultsArchived'
        ];
        //
        //POCOR-7339-HINDOL check query string
        //
        $archive_query_string_params = [
            'class_id' => $entity->institution_class_id,
            'assessment_id' => $entity->assessment_id,
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id
        ];
        $archive_query_string = $buttons['view']['url'] = $this->setQueryString($url, $archive_query_string_params);
        $session = $this->Session;

        $session->write('archive_query_string', $archive_query_string);

        $grade = $entity->education_grade_id;
        $class = $entity->institution_class_id;
        $institutionId = $entity->institution->id;
        $period = $entity->academic_period->id;
        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $Users = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $count = $InstitutionClassStudentsTable->find()
                ->leftJoin([$Users->alias() => $Users->table()], [
                    $Users->aliasField('id').' = ' . $InstitutionClassStudentsTable->aliasField('student_id')
                ])
                ->leftJoin([$Genders->alias() => $Genders->table()], [
                    $Genders->aliasField('id').' = ' . $Users->aliasField('gender_id')
                ])
                ->where([
                    $InstitutionClassStudentsTable->aliasField('institution_class_id') => $class,
                    $InstitutionClassStudentsTable->aliasField('education_grade_id') => $grade,
                    $InstitutionClassStudentsTable->aliasField('academic_period_id') => $period,
                    $InstitutionClassStudentsTable->aliasField('institution_id') => $institutionId,
                    $Genders->aliasField('code') => 'M',
                    $InstitutionClassStudentsTable->aliasField('student_status_id') => 1 //POCOR-6566
                ])->count();
        
        return $count;
    }

    /**
     * Function to get Total Female Students on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return int 
     */
    public function onGetTotalFemaleStudents(Event $event, Entity $entity) {
        $grade = $entity->education_grade_id;
        $class = $entity->institution_class_id;
        $institutionId = $entity->institution->id;
        $period = $entity->academic_period->id;
        $InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
        $Users = TableRegistry::get('Security.Users');
        $Genders = TableRegistry::get('User.Genders');
        $count = $InstitutionClassStudentsTable->find()
                ->leftJoin([$Users->alias() => $Users->table()], [
                    $Users->aliasField('id').' = ' . $InstitutionClassStudentsTable->aliasField('student_id')
                ])
                ->leftJoin([$Genders->alias() => $Genders->table()], [
                    $Genders->aliasField('id').' = ' . $Users->aliasField('gender_id')
                ])
                ->where([
                    $InstitutionClassStudentsTable->aliasField('institution_class_id') => $class,
                    $InstitutionClassStudentsTable->aliasField('education_grade_id') => $grade,
                    $InstitutionClassStudentsTable->aliasField('academic_period_id') => $period,
                    $InstitutionClassStudentsTable->aliasField('institution_id') => $institutionId,
                    $Genders->aliasField('code') => 'F',
                    $InstitutionClassStudentsTable->aliasField('student_status_id') => 1 //POCOR-6566
                ])->count();
        
        return $count;
    }

    /**
     * Function to get class name on index page - POCOR-6183
     * @param Entity $entity and Event $event
     * @return string 
     */
    public function onGetName(Event $event, Entity $entity) {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $class = $InstitutionClasses->get($entity->institution_class_id);

        return $class->name;
    }
}
