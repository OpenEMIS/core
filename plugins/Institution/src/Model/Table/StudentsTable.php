<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;

use App\Model\Table\ControllerActionTable;

class StudentsTable extends ControllerActionTable
{
    const PENDING_TRANSFER = -2;
    const PENDING_ADMISSION = -3;
    const PENDING_DROPOUT = -4;

    private $dashboardQuery = null;

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        // Associations
        $this->belongsTo('Users',           ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions',    ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        // Behaviors
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Institution.StudentCascadeDelete'); // for cascade delete on student related tables from an institution
        $this->addBehavior('AcademicPeriod.AcademicPeriod'); // to make sure it is compatible with v4

        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year'],
            'pages' => ['index']
        ]);

        $this->addBehavior('HighChart', [
            'number_of_students_by_year' => [
                '_function' => 'getNumberOfStudentsByYear',
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Years')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_students_by_grade' => [
                '_function' => 'getNumberOfStudentsByGrade',
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Education')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'institution_student_gender' => [
                '_function' => 'getNumberOfStudentsByGender'
            ],
            'institution_student_age' => [
                '_function' => 'getNumberOfStudentsByAge'
            ],
            'institution_class_student_grade' => [
                '_function' => 'getNumberOfStudentsByGradeByInstitution'
            ]
        ]);
        $this->addBehavior('Import.ImportLink');

        /**
         * Advance Search Types.
         * AdvanceSearchBehavior must be included first before adding other types of advance search.
         * If no "belongsTo" relation from the main model is needed, include its foreign key name in AdvanceSearch->exclude options.
         */
        $advancedSearchFieldOrder = [
            'first_name', 'middle_name', 'third_name', 'last_name',
            'contact_number', 'identity_type', 'identity_number'
        ];

        $this->addBehavior('AdvanceSearch', [
            'exclude' => [
                'student_id',
                'institution_id',
                'education_grade_id',
                'academic_period_id',
                'student_status_id',
            ],
            'order' => $advancedSearchFieldOrder
        ]);

        $this->addBehavior('User.AdvancedIdentitySearch', [
            'associatedKey' => $this->aliasField('student_id')
        ]);
        $this->addBehavior('User.AdvancedContactNumberSearch', [
            'associatedKey' => $this->aliasField('student_id')
        ]);
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch', [
            'modelToSearch' => $this->Users
        ]);
        /**
         * End Advance Search Types
         */
        $this->addBehavior('ControllerAction.Image'); // To be verified
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', false]
            ])
            ->add('end_date', [
            ])
            ->add('student_status_id', [
            ])
            ->add('academic_period_id', [
            ])
            ->add('education_grade_id', [
            ])
            ->allowEmpty('student_name')
            ->add('student_name', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleStudentNotCompletedGrade', [
                'rule' => ['studentNotCompletedGrade', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->allowEmpty('class')
            ->add('class', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit'],
                'on' => 'create'
            ])
            ;
        return $validator;
    }

    // to be verified
    public function validationNewStudent(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator->remove('student_name');
        return $validator;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $query->where([$this->aliasField('institution_id') => $institutionId]);
        $query->contain([
                    'Users.Nationalities.NationalitiesLookUp',
                    'Users.Genders'
                ]);
        $query->select([
                    'openemis_no' => 'Users.openemis_no',
                    'identity_number' => 'Users.identity_number',
                    'gender_name' => 'Genders.name',
                    'date_of_birth' => 'Users.date_of_birth',
                    'code' => 'Institutions.code'
                ]);
        $periodId = $this->request->query['academic_period_id'];
        if ($periodId > 0) {
            $query->where([$this->aliasField('academic_period_id') => $periodId]);
        }
        $query->leftJoin(['ClassStudents' => 'institution_class_students'], [
                'ClassStudents.student_id = '.$this->aliasField('student_id'),
                'ClassStudents.education_grade_id = '.$this->aliasField('education_grade_id'),
                'ClassStudents.student_status_id = '.$this->aliasField('student_status_id')
            ])->leftJoin(['Classes' => 'institution_classes'], [
                'Classes.id = ClassStudents.institution_class_id',
                'Classes.institution_id = '.$this->aliasField('institution_id'),
                'Classes.academic_period_id = '.$this->aliasField('academic_period_id')
            ])->select(['institution_class_name' => 'Classes.name']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $fieldCopy = $fields->getArrayCopy();
        $newFields = [];

        foreach ($fieldCopy as $key => $field) {
            if ($field['field'] != 'institution_id') {
            $newFields[] = $field;
                if ($field['field'] == 'education_grade_id') {
                    $newFields[] = [
                        'key' => 'StudentClasses.institution_class_id',
                        'field' => 'institution_class_name',
                        'type' => 'string',
                        'label' => ''
                    ];
                }
            }
        }

        $extraField[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $extraField[] = [
            'key' => 'Students.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $extraField[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __($identity->name)
        ];

        $extraField[] = [
            'key' => 'Nationalities.country_id',
            'field' => 'country_id',
            'type' => 'nationalities',
            'label' => __('Nationalities')
        ];

        $newFields = array_merge($extraField, $newFields);
        $fields->exchangeArray($newFields);
    }

    // public function onExcelRenderIdentities(Event $event, Entity $entity, array $attr) {
    //     $str = '';
    //     if(!empty($entity['user']['identities'])) {
    //         $identities = $entity['user']['identities'];
    //         foreach ($identities as $identity) {
    //             $number = $identity['number'];
    //             $identityType = $identity['identity_type']['name'];
    //             $str .= '('.$identityType.') '.$number.', ';
    //         }
    //     }
    //     if (!empty($str)) {
    //         $str = substr($str, 0, -2);
    //     }
    //     return $str;
    // }

    public function onExcelRenderNationalities(Event $event, Entity $entity, array $attr)
    {
        $str = '';
        if(!empty($entity['user']['nationalities'])) {
            $nationalities = $entity['user']['nationalities'];
            foreach ($nationalities as $nationality) {
                if (isset($nationality['nationalities_look_up']['name'])) {
                    $str .= $nationality['nationalities_look_up']['name'].', ';
                }
            }
        }
        if (!empty($str)) {
            $str = substr($str, 0, -2);
        }
        return $str;
    }

    // returns error message if validation false
    public function validateEnrolledInAnyInstitution($studentId, $systemId, $options = [])
    {
        $newOptions['getInstitutions'] = true;
        $options = array_merge($options, $newOptions);

        // targetInstitutionId is used to determine the error message, whether it is enrolled in 'this' or 'other' institution
        $targetInstitutionId = (array_key_exists('targetInstitutionId', $options))? $options['targetInstitutionId']: null;

        $enrolledInstitutionIds = $this->enrolledInAnyInstitution($studentId, $systemId, $options);

        if (is_array($enrolledInstitutionIds) && !empty($enrolledInstitutionIds)) {
            if (!empty($targetInstitutionId) && in_array($targetInstitutionId, $enrolledInstitutionIds)) {
                // 'Student is already enrolled in target school.'
                return $this->getMessage('Institution.Students.student_name.ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem.inTargetSchool');
            } else {
                // 'Student is already enrolled in another school.'
                return $this->getMessage('Institution.Students.student_name.ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem.inAnotherSchool');
            }
        } else {
            return false;
        }
    }

    private function enrolledInAnyInstitution($studentId, $systemId, $options = [])
    {
        $newOptions['select'] = ['institution_id', 'education_grade_id'];
        $options = array_merge($options, $newOptions);
        $getInstitutions = (array_key_exists('getInstitutions', $options))? $options['getInstitutions']: false;

        $EducationGradesTable = TableRegistry::get('Education.EducationGrades');

        $options['studentId'] = $studentId;
        $enrolledRecords = $this->find('byStatus', $options)->toArray();

        $existingRecordsInSameSystem = [];
        foreach ($enrolledRecords as $key => $value) {
            $enrolledRecords[$key]->education_system_id = $EducationGradesTable->getEducationSystemId($value->education_grade_id);
            if ($value->education_system_id == $systemId) {
                $existingRecordsInSameSystem[] = $value;
            }
        }

        // returns a true/false if !getInstitutions else returns an array of institution_ids
        if (!$getInstitutions) {
            return !empty($existingRecordsInSameSystem);
        } else {
            $institutionIds = [];
            foreach ($existingRecordsInSameSystem as $key => $value) {
                $institutionIds[$value->institution_id] = $value->institution_id;
            }
            return $institutionIds;
        }
    }

    public function findByStatus(Query $query, array $options)
    {
        $studentId = $options['studentId'];
        $statusCode = 'CURRENT';
        if (array_key_exists('code', $options)) {
            $statusCode = $options['code'];
        }
        $status = $this->StudentStatuses->getIdByCode($statusCode);

        $conditions = [
            $this->aliasField('student_id') => $studentId,
            $this->aliasField('student_status_id') => $status
        ];

        if (array_key_exists('excludeInstitutions', $options) && !empty($options['excludeInstitutions'])) {
            $conditions[$this->aliasField('institution_id') . ' NOT IN '] = $options['excludeInstitutions'];
        }

        if (array_key_exists('select', $options) && !empty($options['select'])) {
            $query->select($options['select']);
        }

        $query->where($conditions);
        return $query;
    }

    public function findWithClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $periodId = $options['period_id'];

        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');

        return $query
            ->select([$Classes->aliasField('name')])
            ->leftJoin(
                [$ClassStudents->alias() => $ClassStudents->table()],
                [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id')
                ]
            )
            ->leftJoin(
                [$Classes->alias() => $Classes->table()],
                [
                    $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id'),
                    $Classes->aliasField('academic_period_id') => $periodId,
                    $Classes->aliasField('institution_id') => $institutionId
                ]
            )
            ->autoFields(true);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_student_id', ['type' => 'hidden']);
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $studentStatuses = $this->StudentStatuses->findCodeList();
        // if user tries to delete record that is not enrolled
        if ($entity->student_status_id != $studentStatuses['CURRENT']) {
            $event->stopPropagation();
            return false;
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['visible' => false]);
        $this->field('class', ['after' => 'education_grade_id']);
        $this->field('student_status_id', ['after' => 'class']);
        $this->fields['start_date']['visible'] = false;
        $this->fields['end_date']['visible'] = false;
        $this->fields['class']['sort'] = ['field' => 'InstitutionClasses.name'];
        $this->fields['student_id']['sort'] = ['field' => 'Users.first_name'];

        $this->controller->set('ngController', 'AdvancedSearchCtrl');

        $StudentStatusesTable = $this->StudentStatuses;
        $status = $StudentStatusesTable->findCodeList();
        $selectedStatus = $this->request->query('status_id');

        // To redirect to Pending statuses page
        $pendingStatuses = [
            $StudentStatusesTable->PENDING_ADMISSION => 'StudentAdmission',
            $StudentStatusesTable->PENDING_TRANSFER => 'TransferRequests',
            $StudentStatusesTable->PENDING_DROPOUT => 'StudentDropout'
        ];

        if (array_key_exists($selectedStatus, $pendingStatuses)) {
            $url = ['plugin' => 'Institution', 'controller' => 'Institutions'];
            $url['action'] = $pendingStatuses[$selectedStatus];
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        // from onUpdateToolbarButtons
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $buttons = $extra['indexButtons'];

        $extraButtons = [
            'graduate' => [
                'permission' => ['Institutions', 'Promotion', 'add'],
                'action' => 'Promotion',
                'icon' => '<i class="fa kd-graduate"></i>',
                'title' => __('Promotion / Graduation')
            ],
            'transfer' => [
                'permission' => ['Institutions', 'Transfer', 'add'],
                'action' => 'Transfer',
                'icon' => '<i class="fa kd-transfer"></i>',
                'title' => __('Transfer')
            ],
            'undo' => [
                'permission' => ['Institutions', 'Undo', 'add'],
                'action' => 'Undo',
                'icon' => '<i class="fa kd-undo"></i>',
                'title' => __('Undo')
            ]
        ];

        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'add']
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $query->contain(['EducationGrades']);

        // Student Statuses
        $statusOptions = $this->StudentStatuses
            ->find('list')
            ->toArray();
        $StudentStatusesTable = $this->StudentStatuses;
        $pendingStatus = [
            $StudentStatusesTable->PENDING_TRANSFER => __('Pending Transfer'),
            $StudentStatusesTable->PENDING_ADMISSION => __('Pending Admission'),
            $StudentStatusesTable->PENDING_DROPOUT => __('Pending Dropout'),
        ];

        $statusOptions = $statusOptions + $pendingStatus;

        // Academic Periods
        $academicPeriodOptions = $this->AcademicPeriods->getList(['restrictLevel' => ['1'], 'withLevels' => false]);

        // Education Grades
        $InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');
        $session = $this->Session;
        $institutionId = $session->read('Institution.Institutions.id');

        $educationGradesOptions = $InstitutionEducationGrades
            ->find('list', [
                    'keyField' => 'EducationGrades.id',
                    'valueField' => 'EducationGrades.name'
                ])
            ->select([
                    'EducationGrades.id', 'EducationGrades.name'
                ])
            ->contain(['EducationGrades'])
            ->where(['institution_id' => $institutionId])
            ->group('education_grade_id')
            ->toArray();

        $educationGradesOptions = ['-1' => __('All Grades')] + $educationGradesOptions;

        // Query Strings

        if (empty($request->query['academic_period_id'])) {
            $request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedStatus = $this->queryString('status_id', $statusOptions);
        $selectedEducationGrades = $this->queryString('education_grade_id', $educationGradesOptions);
        $selectedAcademicPeriod = $this->queryString('academic_period_id', $academicPeriodOptions);

        // To add the academic_period_id to export
        if (isset($extra['toolbarButtons']['export']['url'])) {
            $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedAcademicPeriod;
        }

        // Advanced Select Options
        $this->advancedSelectOptions($statusOptions, $selectedStatus);
        $studentTable = $this;
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function($id) use ($studentTable, $institutionId) {
                return $studentTable->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
            }
        ]);

        $request->query['academic_period_id'] = $selectedAcademicPeriod;

        $this->advancedSelectOptions($educationGradesOptions, $selectedEducationGrades);


        if ($selectedEducationGrades != -1) {
            $query->where([$this->aliasField('education_grade_id') => $selectedEducationGrades]);
        }

        $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);

        // Start: sort by class column
        $session = $request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $query->find('withClass', ['institution_id' => $institutionId, 'period_id' => $selectedAcademicPeriod]);

        $sortList = ['openemis_no', 'first_name', 'InstitutionClasses.name'];
        if (array_key_exists('sortWhitelist', $extra)) {
            $sortList = array_merge($extra['sortWhitelist'], $sortList);
        }
        $extra['sortWhitelist'] = $sortList;
        // End

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        } else {
            if (!$this->isAdvancedSearchEnabled() && $selectedStatus != -1) {
                $query->where([$this->aliasField('student_status_id') => $selectedStatus]);
            }
        }

        // POCOR-2869 implemented to hide the retrieval of records from another school resulting in duplication - proper fix will be done in SOJOR-437
        $query->group([$this->aliasField('student_id'), $this->aliasField('academic_period_id'), $this->aliasField('institution_id'), $this->aliasField('education_grade_id'), $this->aliasField('student_status_id')]);
// pr($query->sql());
        $this->controller->set(compact('statusOptions', 'academicPeriodOptions', 'educationGradesOptions'));
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra)
    {
        $this->dashboardQuery = clone $query;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('photo_content', ['type' => 'image', 'before' => 'openemis_no']);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
        $this->fields['student_id']['order'] = 10;
        $extra['toolbarButtons']['back']['url']['action'] = 'StudentProgrammes';
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
        $studentStatusId = $entity->student_status_id;
        $statuses = $this->StudentStatuses->findCodeList();
        $code = array_search($studentStatusId, $statuses);

        if ($code == 'DROPOUT' || $code == 'TRANSFERRED') {
            $this->field('reason', ['type' => 'custom_status_reason']);
            $this->field('comment');
            $this->setFieldOrder([
                'photo_content', 'openemis_no', 'student_id', 'student_status_id', 'reason', 'comment'
            ]);
        } else if ($code != 'CURRENT') { // only enrolled students can be edited or removed
            $this->toggle('remove', false);
            $this->toggle('edit', false);
        }

        $this->Session->write('Student.Students.id', $entity->student_id);
        $this->Session->write('Student.Students.name', $entity->user->name);
        $this->setupTabElements($entity);
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        // Start PHPOE-1897
        $statuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $statuses['CURRENT']) {
            $event->stopPropagation();
            $urlParams = $this->url('view');
            return $this->controller->redirect($urlParams);
        // End PHPOE-1897
        } else {
            $this->field('student_id', [
                'type' => 'readonly',
                'order' => 10,
                'attr' => ['value' => $entity->user->name_with_id]
            ]);

            $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $entity->education_grade->programme_grade_name]]);
            $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
            $this->field('student_status_id', ['type' => 'readonly', 'attr' => ['value' => $entity->student_status->name]]);

            $period = $entity->academic_period;
            $dateOptions = [
                'startDate' => $period->start_date->format('d-m-Y'),
                'endDate' => $period->end_date->format('d-m-Y')
            ];

            $this->fields['start_date']['date_options'] = $dateOptions;
            $this->fields['end_date']['date_options'] = $dateOptions;

            $this->Session->write('Student.Students.id', $entity->student_id);
            $this->Session->write('Student.Students.name', $entity->user->name);
            $this->setupTabElements($entity);
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $InstitutionArray = [];
            $institutionStudentQuery = clone $this->dashboardQuery;
            $studentCount = $institutionStudentQuery->group([$this->aliasField('student_id')])->count();
            unset($institutionStudentQuery);

            //Get Gender
            $InstitutionArray[__('Gender')] = $this->getDonutChart('institution_student_gender',
                ['query' => $this->dashboardQuery, 'key' => __('Gender')]);

            // Get Age
            $InstitutionArray[__('Age')] = $this->getDonutChart('institution_student_age',
                ['query' => $this->dashboardQuery, 'key' => __('Age')]);

            // Get Grades
            $InstitutionArray[__('Grade')] = $this->getDonutChart('institution_class_student_grade',
                ['query' => $this->dashboardQuery, 'key' => __('Grade')]);

            $indexDashboard = 'dashboard';

            $indexElements = (isset($this->controller->viewVars['indexElements']))?$this->controller->viewVars['indexElements'] :[] ;

            $indexElements[] = ['name' => 'Institution.Students/controls', 'data' => [], 'options' => [], 'order' => 0];

            if (!$this->isAdvancedSearchEnabled()) { //function to determine whether dashboard should be shown or not
                $indexElements[] = [
                    'name' => $indexDashboard,
                    'data' => [
                        'model' => 'students',
                        'modelCount' => $studentCount,
                        'modelArray' => $InstitutionArray,
                    ],
                    'options' => [],
                    'order' => 2
                ];
            }

            foreach ($indexElements as $key => $value) {
                if ($value['name']=='advanced_search') {
                    $indexElements[$key]['order'] = 1;
                } else if ($value['name']=='OpenEmis.ControllerAction/index') {
                    $indexElements[$key]['order'] = 3;
                } else if ($value['name']=='OpenEmis.pagination') {
                    $indexElements[$key]['order'] = 4;
                }
            }

            $extra['elements'] = array_merge($extra['elements'], $indexElements);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Institution.StudentAdmission'),
            TableRegistry::get('Institution.InstitutionClassStudents'),
            $this->Users
        ];
        $this->dispatchEventToModels('Model.Students.afterSave', [$entity], $this, $listeners);

        //if new record has no previous_institution_student_id value yet, then try to update it.
        if (!$entity->has('previous_institution_student_id')) {
            $prevInstitutionStudent = $this
                                ->find()
                                ->where([
                                    $this->aliasField('student_id') => $entity->student_id,
                                    $this->aliasField('id <> ') => $entity->id,
                                ])
                                ->order([
                                    'created' => 'desc',
                                    'start_date' => 'desc'
                                ])
                                ->first();

            if ($prevInstitutionStudent) { //if has previous record.
                $this->updateAll(
                    ['previous_institution_student_id' => $prevInstitutionStudent->id],
                    ['id' => $entity->id]
                );
            }
        }
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name;
        } else {
            $value = $entity->_matchingData['Users']->name;
        }
        return $value;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_grade')) {
            $value = $entity->education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetClass(Event $event, Entity $entity)
    {
        $value = '';
        $academicPeriodId = $entity->academic_period_id;
        $studentId = $entity->user->id;
        $educationGradeId = $entity->education_grade->id;
        $institutionId = $entity->institution_id;

        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $class = $ClassStudents->find()
        ->select(['class.name'])
        ->innerJoin(
            ['class' => 'institution_classes'],
            [
                'class.id = ' . $ClassStudents->aliasField('institution_class_id'),
                'class.academic_period_id' => $academicPeriodId,
                'class.institution_id' => $institutionId
            ]
        )
        ->where([
            $ClassStudents->aliasField('student_id') => $studentId,
            $ClassStudents->aliasField('education_grade_id') => $educationGradeId
        ])
        ->first();

        if ($class) {
            $value = $class->class['name'];
        }

        return $value;
    }

    public function onGetCustomStatusReasonElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($this->action == 'view') {
            $studentStatusId = $entity->student_status_id;
            $statuses = $this->StudentStatuses->findCodeList();
            $code = array_search($studentStatusId, $statuses);
            $institutionId = $entity->institution_id;
            $educationGradeId = $entity->education_grade_id;
            $studentId = $entity->student_id;
            $academicPeriodId = $entity->academic_period_id;

            switch ($code) {
                case 'TRANSFERRED':
                    $TransferApprovalsTable = TableRegistry::get('Institution.TransferApprovals');
                    $transferReason = $TransferApprovalsTable->find()
                        ->matching('StudentTransferReasons')
                        ->where([
                            // Type = 2 is transfer type
                            $TransferApprovalsTable->aliasField('type') => 2,
                            $TransferApprovalsTable->aliasField('academic_period_id') => $academicPeriodId,
                            $TransferApprovalsTable->aliasField('previous_institution_id') => $institutionId,
                            $TransferApprovalsTable->aliasField('education_grade_id') => $educationGradeId,
                            $TransferApprovalsTable->aliasField('academic_period_id') => $academicPeriodId
                        ])
                        ->first();

                    $entity->comment = $transferReason->comment;

                    return $transferReason->_matchingData['StudentTransferReasons']->name;
                    break;

                case 'DROPOUT':
                    $DropoutRequestsTable = TableRegistry::get('Institution.DropoutRequests');

                    $dropoutReason = $DropoutRequestsTable->find()
                        ->matching('StudentDropoutReasons')
                        ->where([
                            $DropoutRequestsTable->aliasField('academic_period_id') => $academicPeriodId,
                            $DropoutRequestsTable->aliasField('institution_id') => $institutionId,
                            $DropoutRequestsTable->aliasField('education_grade_id') => $educationGradeId,
                            $DropoutRequestsTable->aliasField('academic_period_id') => $academicPeriodId
                        ])
                        ->first();

                    $entity->comment = $dropoutReason->comment;

                    return $dropoutReason->_matchingData['StudentDropoutReasons']->name;
                    break;
            }
        }
    }

    public function onGetComment(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return nl2br($entity->comment);
        }
    }

    // Start PHPOE-1897
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $url = $this->url('view');
            $userId = $this->paramsEncode(['id' => $entity->user->id]);
            $buttons['view']['url'] = array_merge($url, ['action' => 'StudentUser', 'id' => $entity->id, $userId]);
        }

        // Remove in POCOR-3010
        if (isset($buttons['edit'])) {
            unset($buttons['edit']);
        }

        // if student is not currently enrolled in this institution, remove the delete button
        $studentStatuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $studentStatuses['CURRENT']) {
            if (isset($buttons['remove'])) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }
    // End PHPOE-1897

    private function setupTabElements($entity)
    {
        $options['type'] = 'student';
        $tabElements = TableRegistry::get('Institution.StudentUser')->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Programmes');
    }

    public function checkEnrolledInInstitution($studentId, $institutionId)
    {
        $statuses = TableRegistry::get('Student.StudentStatuses')->findCodeList();
        $status = $this
            ->find()
            ->where([$this->aliasField('student_id') => $studentId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->count();
        return $status > 0;
    }

    public function checkIfCanTransfer($student, $institutionId)
    {
        $gradeId = ($student->has('education_grade_id'))? $student->education_grade_id: null;
        $studentId = ($student->has('student_id'))? $student->student_id: null;
        if (empty($gradeId) || empty($studentId)) {
            // missing critical parameter - grade, student_id - cant transfer
            return false;
        }

        // check if student exists in current year
        $academicPeriodId = ($student->has('academic_period_id'))? $student->academic_period_id: null;
        $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
        if ($academicPeriodId != $currentAcademicPeriod) {
            return false;
        }

        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $studentStatusList = array_flip($StudentStatuses->findCodeList());

        $checkIfCanTransfer = (in_array($studentStatusList[$student->student_status_id], ['CURRENT', 'PROMOTED', 'GRADUATED']));

        // check ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem && ruleStudentNotCompletedGrade
        $newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);
        $validateEnrolledInAnyInstitutionResult = $this->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['excludeInstitutions' => $institutionId]);

        if ($checkIfCanTransfer) {
            if (!empty($validateEnrolledInAnyInstitutionResult) ||
                $this->completedGrade($gradeId, $studentId)) {
                $checkIfCanTransfer = false;
            }
        }

        // additional logic for PROMOTED
        if ($checkIfCanTransfer && $studentStatusList[$student->student_status_id] == 'PROMOTED') {
            //'Promoted' status - this feature will be available if the student is at the last grade that the school offers
            // Education Grades
            $InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGrades = TableRegistry::get('Education.EducationGrades');

            $studentEducationGrade = $EducationGrades
                ->find()
                ->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $gradeId])
                ->first();

            $currentProgrammeGrades = $EducationGrades
                ->find('list', [
                    'keyField' => 'id',

                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->where([
                    $this->EducationGrades->aliasField('order').' > ' => $studentEducationGrade->order,
                    $this->EducationGrades->aliasField('education_programme_id') => $studentEducationGrade->education_programme_id
                ])
                ->toArray();

            $EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
            $educationProgrammeId = $studentEducationGrade->education_programme_id;
            $nextEducationGradeList = $EducationProgrammesNextProgrammesTable->getNextGradeList($educationProgrammeId);
            $moreAdvancedEducationGrades = $currentProgrammeGrades + $nextEducationGradeList;

            $studentEducationGradeOrder = [];
            if (!empty($studentEducationGrade)) {
                $studentEducationGradeOrder = $studentEducationGrade->order;
            }

            $advancedGradeOptionsLeft = $InstitutionEducationGrades
                ->find('list', [
                        'keyField' => 'EducationGrades.id',
                        'valueField' => 'EducationGrades.name'
                    ])
                ->select([
                        'EducationGrades.id', 'EducationGrades.name', 'EducationGrades.order'
                    ])
                ->contain(['EducationGrades'])
                ->where(['EducationGrades.order > ' => $studentEducationGradeOrder])
                ->where(['institution_id' => $institutionId])
                ->group('education_grade_id')
                ->toArray()
                ;

            // if there are more advanced grades available to the student, the student cannot transfer
            if (count(array_intersect_key($moreAdvancedEducationGrades, $advancedGradeOptionsLeft))>0) {
                $checkIfCanTransfer = false;
            }
        }
        return $checkIfCanTransfer;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByGender($params=[])
    {
        $query = $params['query'];
        $InstitutionRecords = clone $query;
        $InstitutionStudentCount = $InstitutionRecords
            ->matching('Users.Genders')
            ->select([
                'count' => $InstitutionRecords->func()->count('DISTINCT ' . $this->aliasField('student_id')),
                'gender' => 'Genders.name'
            ])
            ->group(['gender'], true);

        // Creating the data set
        $dataSet = [];
        foreach ($InstitutionStudentCount->toArray() as $value) {
            //Compile the dataset
            $dataSet[] = [__($value['gender']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        unset($InstitutionRecords);
        return $params;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByAge($params=[])
    {
        $query = $params['query'];
        $InstitutionRecords = $query->cleanCopy();
        $ageQuery = $InstitutionRecords
            ->select([
                'age' => $InstitutionRecords->func()->dateDiff([
                    $InstitutionRecords->func()->now(),
                    'Users.date_of_birth' => 'literal'
                ]),
                'student' => $this->aliasField('student_id')
            ])
            ->distinct(['student'])
            ->order('age');

        $InstitutionStudentCount = $ageQuery->toArray();

        $convertAge = [];

        // (Logic to be reviewed)
        // Calculate the age taking account to the average of leap years
        foreach($InstitutionStudentCount as $val){
            $convertAge[] = floor($val['age']/365.25);
        }
        // Count and sort the age
        $result = [];
        $prevValue = ['age' => -1, 'count' => null];
        foreach ($convertAge as $val) {
            if ($prevValue['age'] != $val) {
                unset($prevValue);
                $prevValue = ['age' => $val, 'count' => 0];
                $result[] =& $prevValue;
            }
            $prevValue['count']++;
        }

        // Creating the data set
        $dataSet = [];
        foreach ($result as $value) {
            //Compile the dataset
            $dataSet[] = [__('Age').' '.$value['age'], $value['count']];
        }
        $params['dataSet'] = $dataSet;
        unset($InstitutionRecords);
        return $params;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByGradeByInstitution($params=[])
    {
        $query = $params['query'];
        $InstitutionRecords = clone $query;
        $studentByGrades = $InstitutionRecords
            ->select([
                'grade' => 'EducationGrades.name',
                'count' => $query->func()->count('DISTINCT '.$this->aliasField('student_id'))
            ])
            ->contain([
                'EducationGrades'
            ])
            ->group([$this->aliasField('education_grade_id')], true)
            ->toArray();

        $dataSet = [];
        foreach($studentByGrades as $value){
            $dataSet[] = [__($value['grade']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        unset($InstitutionRecords);
        return $params;
    }

    // For Dashboard (Institution Dashboard and Home Page)
    public function getNumberOfStudentsByYear($params=[])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $_conditions = [];
        foreach ($conditions as $key => $value) {
            $_conditions[$this->alias().'.'.$key] = $value;
        }

        $AcademicPeriod = $this->AcademicPeriods;
        $currentPeriodId = $AcademicPeriod->getCurrent();

        $genderOptions = $this->Users->Genders->getList();
        $dataSet = new ArrayObject();
        foreach ($genderOptions as $key => $value) {
            $dataSet[$value] = ['name' => __($value), 'data' => []];
        }

        $academicPeriodList = [];
        $found = false;
        foreach ($AcademicPeriod->getYearList() as $periodId => $periodName) {
            if ($found) {
                $academicPeriodList[$periodId] = $periodName;
                break;
            }
            if ($periodId == $currentPeriodId) {
                $academicPeriodList[$periodId] = $periodName;
                $found = true;
            } else {
                $academicPeriodList = [$periodId => $periodName];
            }
        }
        $academicPeriodList = array_reverse($academicPeriodList, true);

        if (!empty($academicPeriodList)) {
            $academicPeriodCondition = ['academic_period_id IN ' => array_keys($academicPeriodList)];
        } else {
            $academicPeriodCondition = [];
        }

        $queryCondition = array_merge($academicPeriodCondition, $_conditions);
        $studentsByYear = $this
            ->find('list',[
                'groupField' => 'gender_name',
                'keyField' => 'period_name',
                'valueField' => 'total'
            ])
            ->matching('Users.Genders')
            ->matching('AcademicPeriods')
            ->select([
                'gender_name' => 'Genders.name',
                'period_name' => 'AcademicPeriods.name',
                'total' => $this->find()->func()->count('DISTINCT '.$this->aliasField('student_id'))
            ])
            ->where($queryCondition)
            ->group(['gender_name', $this->aliasField('academic_period_id')])
            ->order('AcademicPeriods.order DESC')
            ->hydrate(false)
            ->toArray()
            ;

        foreach ($dataSet as $key => $data) {
            foreach ($academicPeriodList as $period) {

                if (isset($studentsByYear[$key][$period])) {
                    $dataSet[$key]['data'][$period] = $studentsByYear[$key][$period];
                } else {
                    $dataSet[$key]['data'][$period] = 0;
                }
            }
        }
        $params['dataSet'] = $dataSet->getArrayCopy();

        return $params;
    }

    // For Dashboard (Home Page and Institution Dashboard page)
    public function getNumberOfStudentsByGrade($params=[])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $_conditions = [];
        foreach ($conditions as $key => $value) {
            $_conditions[$this->alias().'.'.$key] = $value;
        }

        $AcademicPeriod = $this->AcademicPeriods;
        $currentYearId = $AcademicPeriod->getCurrent();

        if (!empty($currentYearId)) {
            $currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;
        } else {
            $currentYear = __('Not Defined');
        }

        $studentsByGradeConditions = [
            $this->aliasField('academic_period_id') => $currentYearId,
            $this->aliasField('education_grade_id').' IS NOT NULL',
            'Genders.name IS NOT NULL'
        ];
        $studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);
        $query = $this->find();
        $studentByGrades = $query
            ->select([
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                'EducationGrades.name',
                'Users.id',
                'Genders.name',
                'total' => $query->func()->count($this->aliasField('id'))
            ])
            ->contain([
                'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels',
                'Users.Genders'
            ])
            ->where($studentsByGradeConditions)
            ->group([
                $this->aliasField('education_grade_id'),
                'Genders.name'
            ])
            ->order(
                ['EducationLevels.order', 'EducationCycles.order', 'EducationProgrammes.order', 'EducationGrades.order']
            )
            ->toArray()
            ;


        $grades = [];

        $genderOptions = $this->Users->Genders->getList();
        $dataSet = array();
        foreach ($genderOptions as $key => $value) {
            $dataSet[$value] = array('name' => __($value), 'data' => array());
        }

        foreach ($studentByGrades as $key => $studentByGrade) {
            $gradeId = $studentByGrade->education_grade_id;
            $gradeName = $studentByGrade->education_grade->name;
            $gradeGender = $studentByGrade->user->gender->name;
            $gradeTotal = $studentByGrade->total;

            $grades[$gradeId] = $gradeName;

            foreach ($dataSet as $dkey => $dvalue) {
                if (!array_key_exists($gradeId, $dataSet[$dkey]['data'])) {
                    $dataSet[$dkey]['data'][$gradeId] = 0;
                }
            }
            $dataSet[$gradeGender]['data'][$gradeId] = $gradeTotal;
        }

        // $params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
        $params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
        $params['options']['xAxis']['categories'] = array_values($grades);
        $params['dataSet'] = $dataSet;

        return $params;
    }

    public function completedGrade($educationGradeId, $studentId)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');

        $statuses = $StudentStatuses->findCodeList();
        $completedGradeCount = $this->find()
            ->where([
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('student_status_id').' IN ' => [$statuses['GRADUATED'], $statuses['PROMOTED']]
            ])
            // ;pr($completedGradeCount->toArray());die;
            ->count()
            ;

        return !($completedGradeCount == 0);
    }

    public function getInstitutionIdByUser($studentId, $academicPeriodId)
    {
        return $institutionId = $this->find()
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_status_id') => 1 // status enrolled
            ])
            ->first()->institution_id;
    }
}
