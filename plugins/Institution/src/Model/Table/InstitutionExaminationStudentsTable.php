<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\I18n\FrozenTime;

class InstitutionExaminationStudentsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $institutionId;

    public function initialize(array $config): void
    {
        $this->setTable('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsToMany('IdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->belongsToMany('Genders', ['className' => 'User.Genders', 'foreignKey' => 'gender_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationCentresExaminations', [
            'className' => 'Examination.ExaminationCentresExaminations',
            'foreignKey' => ['examination_centre_id', 'examination_id']
        ]);
        $this->belongsToMany('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'joinTable' => 'examination_centres_examinations_subjects_students',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'targetForeignKey' => ['examination_centre_id', 'examination_subject_id'],
            'through' => 'Examination.ExaminationCentresExaminationsSubjectsStudents',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'bindingKey' => ['examination_centre_id', 'examination_id', 'student_id'],
            'dependent' => true,
            'cascadeCallBacks' => true
        ]);

        $this->addBehavior('Examination.RegisteredStudents');
        $this->addBehavior('Excel', [
            'excludes' => ['id', 'education_subject_id', 'examination_subject_id'],
            'pages' => ['index'],
            'filename' => 'RegisteredStudents',
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('CompositeKey');
        $this->addBehavior(
            'Institution.InstitutionTab', //POCOR-8813
            ['appliedAction' => ['ExaminationStudents' => ['examination_centre_id', 'examination_id', 'student_id']]]
        );
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        //POCOR-7512 start
        if (isset($this->action)) {
            if ($this->action == 'add') {
                $validator
                    ->allowEmpty('registration_number')
                    ->add('registration_number', 'ruleUnique', [
                        'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                        'provider' => 'table'
                    ])
                    ->requirePresence('auto_assign_to_rooms');
            }
            if ($this->action == 'edit') {
                $validator->allowEmpty('registration_number')
                    ->add('registration_number', 'ruleUnique', [
                        'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                        'provider' => 'table'
                    ]);
            }
        }
        return $validator;
    }
    //POCOR-7512 end

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $User = TableRegistry::getTableLocator()->get('User.Users');
        $nationalities = TableRegistry::getTableLocator()->get('FieldOption.Nationalities');
        $examinations = TableRegistry::getTableLocator()->get('Institution.InstitutionExaminations');
        $academicPeriod = ($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $examinationId = ($this->request->getQuery('examination_id')) ? $this->request->getQuery('examination_id') : 0;

        $session = $this->request->getSession();
        $institutionId  = $this->getInstitutionID();
        $query
            ->select([
                'registration_number' => 'InstitutionExaminationStudents.registration_number',
                'openemis_no' => 'Users.openemis_no',
                'dob' => 'Users.date_of_birth',
                'identity_type' => 'IdentityTypes.name',
                'identity_number' => 'Users.identity_number',
                'gender' => 'Genders.code',
                'academic_period' => 'AcademicPeriods.name',
                'nationality_name' => 'nationalities.name',
                'education_grade_id' => $examinations->aliasField('education_grade_id'),
                'student_name' => $User->find()->func()->concat([
                    'first_name' => 'literal',
                    " ",
                    /*'middle_name' => 'literal',
                " ",
                 'third_name' => 'literal',
                " ",*/
                    'last_name' => 'literal'
                ])
            ])
            ->LeftJoin([$this->AcademicPeriods->getAlias() => $this->AcademicPeriods->getTable()], [
                $this->AcademicPeriods->aliasField('id') . ' = ' . 'InstitutionExaminationStudents.academic_period_id'
            ])
            ->LeftJoin([$this->Users->getAlias() => $this->Users->getTable()], [
                $this->Users->aliasField('id') . ' = ' . 'InstitutionExaminationStudents.student_id'
            ])
            ->LeftJoin([$nationalities->getAlias() => $nationalities->getTable()], [
                $nationalities->aliasField('id') . ' = ' . 'Users.nationality_id'
            ])
            ->LeftJoin([$this->IdentityTypes->getAlias() => $this->IdentityTypes->getTable()], [
                $this->IdentityTypes->aliasField('id') . ' = ' . 'Users.identity_type_id'
            ])
            ->LeftJoin([$this->Genders->getAlias() => $this->Genders->getTable()], [
                $this->Genders->aliasField('id') . ' = ' . 'Users.gender_id'
            ])
            ->LeftJoin([$examinations->getAlias() => $examinations->getTable()], [
                [$examinations->aliasField('id =') . $this->aliasField('examination_id')],
            ])
            ->where([
                'InstitutionExaminationStudents.academic_period_id' =>  $academicPeriod,
                'InstitutionExaminationStudents.institution_id' =>  $institutionId,
                $this->aliasField('examination_id =') . $examinationId
            ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
                $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
                $statuses = $StudentStatuses->findCodeList();
                $repeatedStatus = $statuses['REPEATED'];

                $InstitutionStudentsCurrentData = $InstitutionStudents
                    ->find()
                    ->select([
                        'InstitutionStudents.id',
                        'InstitutionStudents.student_status_id',
                        'InstitutionStudents.previous_institution_student_id'
                    ])
                    ->where([
                        $InstitutionStudents->aliasField('student_id') => $row['student_id'],
                        $InstitutionStudents->aliasField('education_grade_id') => $row['education_grade_id'],
                        $InstitutionStudents->aliasField('student_status_id') => $repeatedStatus,
                    ])
                    ->order([$InstitutionStudents->aliasField('InstitutionStudents.student_status_id') => 'DESC'])
                    //->autoFields(true)
                    ->first();

                $StudentTransfers = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentTransfers');
                $approvedStatuses = $StudentTransfers->getStudentTransferWorkflowStatuses('APPROVED');
                $institutionStudentTransfer = $StudentTransfers
                    ->find()
                    ->select([
                        $StudentTransfers->aliasField('id'),
                        $StudentTransfers->aliasField('student_id'),
                        $StudentTransfers->aliasField('previous_institution_id'),
                        $StudentTransfers->aliasField('previous_academic_period_id'),
                        $StudentTransfers->aliasField('status_id')
                    ])
                    ->where([
                        $StudentTransfers->aliasField('student_id') => $row['student_id'],
                        $StudentTransfers->aliasField('previous_institution_id') => $row['institution_id'],
                        $StudentTransfers->aliasField('previous_academic_period_id') => $row['academic_period_id'],
                        $StudentTransfers->aliasField('status_id IN') => $approvedStatuses
                    ])
                    ->order([$StudentTransfers->aliasField('status_id') => 'DESC'])
                    //->autoFields(true)
                    ->first();

                if ($InstitutionStudentsCurrentData) {
                    $student_status = "Yes";
                } else {
                    $student_status = 'No';
                }

                if ($institutionStudentTransfer) {
                    $transfer = 'Yes';
                } else {
                    $transfer = 'No';
                }

                $row['repeater_status'] = $student_status;
                $row['transfer_status'] = $transfer;
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'InstitutionExaminationStudents',
            'field' => 'registration_number',
            'type' => 'integer',
            'label' => 'Registration Number',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => 'Student',
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            //'type' => 'date',
            'type' => 'string',
            'label' => 'Date Of Birth',
        ];

        $newFields[] = [
            'key' => 'Genders.code',
            'field' => 'gender',
            'type' => 'string',
            'label' => 'Gender'
        ];

        $newFields[] = [
            'key' => 'nationalities.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => 'Nationality'
        ];

        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => 'Identity Type',
        ];

        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => 'Identity Number',
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'repeater_status',
            'type' => 'string',
            'label' => __('Repeated')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'transfer_status',
            'type' => 'string',
            'label' => __('Transferred')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetExaminationId(EventInterface $event, Entity $entity)
    {
        if ($entity->has('examination')) {
            return $entity->examination->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetExaminationCentreId(EventInterface $event, Entity $entity)
    {
        if ($entity->has('examination_centre')) {
            return $entity->examination_centre->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetInstitutionId(EventInterface $event, Entity $entity)
    {
        if ($entity->has('institution')) {
            return $entity->institution->code_name;
        } else {
            return '';
        }
    }
    // use for testing purpose by anubhav POCOR-7485
    /*public function onExcelGetDob(EventInterface $event, Entity $entity) {
        echo "<pre>"; print_r($this->formatDate($entity->dob));
        die;
        return $this->formatDate($entity->dob);
    }*/

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->institutionId = $this->getInstitutionID();
        //work around for export button showing in pages not specified
        if ($this->action != 'index') {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (isset($toolbarButtonsArray['add'])) {
            $toolbarButtonsArray['add']['attr']['title'] = __('Register');
        }
        $this->setFieldOrder(['academic_period_id']);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $undoButton['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'UndoExaminationRegistration',
            '0' => 'add',
            '1' => $encodedQueryString,
        ];
        $undoButton['type'] = 'button';
        $undoButton['label'] = '<i class="fa fa-undo"></i>';
        $undoButton['attr']['class'] = 'btn btn-xs btn-default icon-big';
        $undoButton['attr']['data-toggle'] = 'tooltip';
        $undoButton['attr']['data-placement'] = 'bottom';
        $undoButton['attr']['escape'] = false;
        $undoButton['attr']['title'] = __('Unregister');
        $toolbarButtonsArray['undo'] = $undoButton;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

        $examinationId = $this->request->getQuery('examination_id');

        if (!$this->AccessControl->check(['Institutions', 'ExaminationStudents', 'excel'])) {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'Students', 'Examinations');
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
        //POCOR-7509 start

        $syncUserConfigured = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')->getOpenemisExamConfiguration();

        if ($syncUserConfigured) {
            $examinationId = $this->request->getQuery('examination_id');
            if (($this->AccessControl->check(['Examinations', 'syncStudentsToExam', 'execute']) || $this->AccessControl->isAdmin())
                && !empty($examinationId) && $examinationId != -1
            ) {

                $syncParams = [
                    'examination_id' => $examinationId,
                    'academic_period_id' => $this->request->getQuery('academic_period_id'),
                    'institution_id' => $this->getInstitutionID(),
                    'referrer' => $this->request->getRequestTarget()
                ];
                $encodedParams = $this->ControllerAction->paramsEncode($syncParams);

                $syncUrl = [
                    'plugin' => 'Examination',
                    'controller' => 'Examinations',
                    'action' => 'syncStudentsToExam',
                    '?' => ['queryString' => $encodedParams]
                ];

                $syncButton =  [
                    'url' => $syncUrl,
                    'type' => 'button',
                    'label' => '<i class="kd-process"></i>',
                    'attr' => [
                        'class' => 'btn btn-xs btn-default icon-big',
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'bottom',
                        'escape' => false,
                        'title' => __('Sync')
                    ]
                ];


                $extra['toolbarButtons']['sync'] = $syncButton;
            }
        }
        //POCOR-7509 end
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {

        $query->select([
            'InstitutionExaminationStudents.id',
            'InstitutionExaminationStudents.student_id',
            'InstitutionExaminationStudents.academic_period_id',
            'InstitutionExaminationStudents.examination_id',
            'InstitutionExaminationStudents.registration_number',
            'InstitutionExaminationStudents.examination_centre_id',
            'InstitutionExaminationStudents.sync_status',
            'InstitutionExaminationStudents.last_synced',

            'Users.openemis_no',
            'Users.first_name',
            'Users.middle_name',
            'Users.third_name',
            'Users.last_name',
            'Users.preferred_name',
            'Users.date_of_birth',
            'Users.identity_number',

            'MainIdentityTypes.name',
            'Genders.name',
            'MainNationalities.name',

            'Institutions.code',
            'Institutions.name'
        ]);

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => [], 'order' => 1];
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('auto_assign_to_rooms', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);

        $this->field('student_id', ['entity' => $entity]);
        $this->field('subject_id');
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('registration_number', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id',
            'examination_id',
            'examination_education_grade',
            'examination_centre_id',
            'special_needs',
            'auto_assign_to_rooms',
            'institution_class_id',
            'student_id',
            'subject_id'
        ]);
    }
    //POCOR-7512 start
    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $subjectTable = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjects');
        $subjectData = $subjectTable->find('all')->select([
            'id' => 'ExaminationSubjects.id',
            'name' => 'ExaminationSubjects.name',
            'code' => 'ExaminationSubjects.code'

        ])->leftJoin(
            ['ExaminationSubjects' => 'examination_subjects'],
            [
                'ExaminationSubjects.id = ' .  $subjectTable->aliasField('examination_subject_id')
            ]
        )->where([$subjectTable->aliasField('student_id') => $entity->student_id])->toArray();
        $entity['examination_subjects'] = $subjectData;
    }  //POCOR-7512 end
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();

            $attr['default'] = $selectedAcademicPeriod;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }
        return $attr;
    }

    public function addOnChangeAcademicPeriodId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->getAlias(), $data)) {
            if (array_key_exists('examination_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_id']);
            }
            if (array_key_exists('examination_centre_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_class_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['institution_class_id']);
            }
        }
    }

    public function onUpdateFieldExaminationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $examinationOptions = [];
        $this->institutionId = $this->getInstitutionID();
        if ($action == 'add') {
            $todayDate = Time::now();

            if (!empty($request->getData()[$this->getAlias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->getData()[$this->getAlias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $availableGrades = $InstitutionGrades
                ->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
                ->where([$InstitutionGrades->aliasField('institution_id') => $this->institutionId])
                ->toArray();

            $Examinations = $this->Examinations;
            $examinationOptions = $Examinations->find('list')
                ->where([
                    $Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod,
                    $Examinations->aliasField('education_grade_id IN ') => $availableGrades
                ])
                ->toArray();

            $examinationId = isset($request->getData()[$this->getAlias()]['examination_id']) ? $request->getData()[$this->getAlias()]['examination_id'] : null;
            $this->advancedSelectOptions($examinationOptions, $examinationId, [
                'message' => '{{label}} - ' . $this->getMessage('InstitutionExaminationStudents.notAvailableForRegistration'),
                'selectOption' => false,
                'callable' => function ($id) use ($Examinations, $todayDate) {
                    return $Examinations
                        ->find()
                        ->where([
                            $Examinations->aliasField('id') => $id,
                            $Examinations->aliasField('registration_start_date <=') => $todayDate,
                            $Examinations->aliasField('registration_end_date >=') => $todayDate
                        ])
                        ->count();
                }
            ]);

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
        }
        return $attr;
    }

    public function addOnChangeExaminationId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists($this->getAlias())) {
            if (array_key_exists('examination_centre_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_class_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['institution_class_id']);
            }
        }
    }

    public function onUpdateFieldExaminationEducationGrade(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $educationGrade = '';
        if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
            $selectedExamination = $request->getData()[$this->getAlias()]['examination_id'];
            $Examinations = $this->Examinations
                ->get($selectedExamination, [
                    'contain' => ['EducationGrades']
                ])
                ->toArray();

            $educationGrade = $Examinations['education_grade']['name'];
            //$request->withdata()[$this->getAlias()]['education_grade_id'] = $Examinations['education_grade']['id'];
            $request = $request->withData($this->getAlias() . '.education_grade_id', $Examinations['education_grade']['id']);
            $attr['attr']['value'] = $educationGrade;
        }
        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $examCentreOptions = [];
            if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
                $selectedExamination = $request->getData()[$this->getAlias()]['examination_id'];

                $LinkedInstitutions = TableRegistry::getTableLocator()->get('Examination.ExaminationCentresExaminationsInstitutions');
                $examCentreOptions = $LinkedInstitutions
                    ->find('list', [
                        'keyField' => 'examination_centre_id',
                        'valueField' => 'examination_centre.code_name'
                    ])
                    ->contain('ExaminationCentres')
                    ->where([
                        $LinkedInstitutions->aliasField('examination_id') => $selectedExamination,
                        $LinkedInstitutions->aliasField('institution_id') => $this->institutionId
                    ])
                    ->order([$this->ExaminationCentres->aliasField('code')])
                    ->toArray();

                if (empty($examCentreOptions)) {
                    $this->Alert->warning($this->aliasField('noLinkedExamCentres'));
                }
            }
            $attr['options'] = $examCentreOptions;
        }
        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $specialNeeds = [];

        if (!empty($request->getData()[$this->getAlias()]['examination_centre_id'])) {
            $examinationCentreId = $request->getData()[$this->getAlias()]['examination_centre_id'];
            $ExaminationCentreSpecialNeeds = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreSpecialNeeds');
            $query = $ExaminationCentreSpecialNeeds
                ->find('list', [
                    'keyField' => 'special_need_type_id',
                    'valueField' => 'special_needs_type.name'
                ])
                ->contain('SpecialNeedsTypes')
                ->where([$ExaminationCentreSpecialNeeds->aliasField('examination_centre_id') => $examinationCentreId])
                ->toArray();

            if (!empty($query)) {
                $specialNeeds = implode(', ', $query);
            }
            $attr['attr']['value'] = $specialNeeds;
        }
        return $attr;
    }

    public function onUpdateFieldInstitutionClassId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $classes = [];
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_id'])) {
                $examinationId = $request->getData()[$this->getAlias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->getData()[$this->getAlias()]['academic_period_id'];

                $InstitutionClass = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
                $classes = $InstitutionClass
                    ->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $InstitutionClass->aliasField('institution_id') => $this->institutionId,
                        $InstitutionClass->aliasField('academic_period_id') => $academicPeriodId,
                        'ClassGrades.education_grade_id' => $educationGradeId
                    ])
                    ->order($InstitutionClass->aliasField('name'))
                    ->toArray();
            }
            $attr['options'] = $classes;
        }
        return $attr;
    }

    public function onUpdateFieldStudentId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $students = [];
        if ($action == 'add') {
            if (!empty($request->getData($this->getAlias())['examination_id']) && !empty($request->getData($this->getAlias())['institution_class_id'])) {
                $academicPeriodId = $request->getData($this->getAlias())['academic_period_id'];
                $examinationId = $request->getData($this->getAlias())['examination_id'];
                $institutionClassId = $request->getData($this->getAlias())['institution_class_id'];
                $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->getData($this->getAlias())['examination_centre_id'];

                $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centres_examinations_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = ' . $ClassStudents->aliasField('student_id')
                    ])
                    ->contain('Users.SpecialNeeds.SpecialNeedsTypes')
                    ->leftJoinWith('Users.SpecialNeeds')
                    ->where([
                        $ClassStudents->aliasField('institution_id') => $this->institutionId,
                        $ClassStudents->aliasField('academic_period_id') => $academicPeriodId,
                        $ClassStudents->aliasField('institution_class_id') => $institutionClassId,
                        $ClassStudents->aliasField('student_status_id') => $enrolledStatus,
                        'InstitutionExaminationStudents.student_id IS NULL'
                    ])
                    ->order(['SpecialNeeds.id' => 'DESC'])
                    ->group($ClassStudents->aliasField('student_id'))
                    ->toArray();
            }
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.students';
            $attr['data'] = $students;
            //$request->getData($this->getAlias())['studentList'] = $students;
            $this->request = $this->request->withData($this->getAlias() . '.studentList', $students);
        }
        return $attr;
    }
    public function onUpdateFieldSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $subjects = [];
        if ($action == 'add') {
            if (!empty($request->getData()[$this->getAlias()]['examination_id']) && !empty($request->getData()[$this->getAlias()]['studentList'])) {
                $ExaminationSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationSubjects');
                $subjects = $ExaminationSubjects->find()->where([
                    $ExaminationSubjects->aliasField('examination_id') => $request->getData()[$this->getAlias()]['examination_id']
                ])->toArray();
            }
            $attr['label'] = "Education Subjects";
            $attr['type'] = 'element';
            $attr['element'] = 'Examination.institution_examination_subjects';
            $attr['data'] = $subjects;
            return $attr;
        }
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->getAlias()]['student_id'] = 0;
    }  //POCOR-7512 start

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $examinationStudentSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjects');
        $examinationSubjects = $examinationStudentSubjects
            ->find()
            ->where(['student_id' => $entity->student_id])
            ->toArray();
        foreach ($examinationSubjects as $data) {
            $deleteEntity = $examinationStudentSubjects->delete($data);
        }
        foreach ($entity->examination_subjects as $Key => $value) {
            if ($value['selected'] == 1) {
                $studentEntity = array('student_id' => $entity->student_id, 'examination_subject_id' => $value['subject_id']);
                $studSubArr = $examinationStudentSubjects->newEntity($studentEntity);
                $save = $examinationStudentSubjects->save($studSubArr);
            }
        }
    }  //POCOR-7512 end

    public function addBeforeSave(EventInterface $event, $entity, $requestData, $extra)
    {
        $process = function ($model, $entity) use ($requestData) {
            $errors = $entity->getErrors();
            if (!empty($errors)) {
                return false;
            }
            $listOfSelectedStudents = [];
            if (!empty($requestData[$this->getAlias()]['examination_students']) && !empty($requestData[$this->getAlias()]['examination_centre_id'])) {

                $students = $requestData[$this->getAlias()]['examination_students'];
                $newEntities = [];

                $selectedExaminationCentre = $requestData[$this->getAlias()]['examination_centre_id'];
                $selectedExamination = $requestData[$this->getAlias()]['examination_id'];
                $examCentreSubjects = $this->ExaminationCentresExaminationsSubjects->getExaminationCentreSubjects($selectedExaminationCentre, $selectedExamination);

                $studentCount = 0;
                $roomStudents = [];
                foreach ($students as $key => $student) {
                    $obj = [];
                    if ($student['selected'] == 1) {
                        $obj['student_id'] = $student['student_id'];
                        $obj['registration_number'] = $student['registration_number'];
                        $obj['institution_id'] = $requestData[$this->getAlias()]['institution_id'];
                        $obj['academic_period_id'] = $requestData[$this->getAlias()]['academic_period_id'];
                        $obj['examination_id'] = $requestData[$this->getAlias()]['examination_id'];
                        $obj['examination_centre_id'] = $requestData[$this->getAlias()]['examination_centre_id'];
                        $obj['auto_assign_to_rooms'] = $requestData[$this->getAlias()]['auto_assign_to_rooms'];
                        $obj['counterNo'] = $key;
                        $roomStudents[] = $obj;
                        $studentCount++;

                        foreach ($examCentreSubjects as $examItemId => $subjectId) {
                            $obj['examination_centres_examinations_subjects'][] = [
                                'examination_centre_id' => $selectedExaminationCentre,
                                'examination_subject_id' => $examItemId,
                                '_joinData' => [
                                    'education_subject_id' => $subjectId,
                                    'examination_subject_id' => $examItemId,
                                    'examination_centre_id' => $selectedExaminationCentre,
                                    'student_id' => $student['student_id'],
                                    'examination_id' => $selectedExamination
                                ]
                            ];
                        }
                        $newEntities[] = $obj;
                        $listOfSelectedStudents[] = $student['student_id'];
                    }
                }

                if (empty($newEntities)) {
                    $model->Alert->warning($this->aliasField('noStudentSelected'));
                    $entity->getErrors('student_id', __('There are no students selected'));
                    return false;
                }

                $success = $this->getConnection()->transactional(function () use ($newEntities, $entity) {
                    $patchOptions['associated'] = ['ExaminationCentresExaminationsSubjects' => ['validate' => false]];
                    $return = true;

                    foreach ($newEntities as $key => $newEntity) {
                        $examCentreStudentEntity = $this->newEntity($newEntity, $patchOptions);
                        if ($examCentreStudentEntity->getErrors('registration_number')) {
                            $counterNo = $newEntity['counterNo'];
                            $entity->getErrors("examination_students.$counterNo", ['registration_number' => $examCentreStudentEntity->getErrors('registration_number')]);
                        }
                        if (!$this->save($examCentreStudentEntity)) {
                            $return = false;
                        }
                    }
                    return $return;
                });

                if ($success) {
                    $studentCount = $this->find()
                        ->where([
                            $this->aliasField('examination_centre_id') => $entity->examination_centre_id,
                            $this->aliasField('examination_id') => $entity->examination_id
                        ])
                        ->group([$this->aliasField('student_id')])
                        ->count();
                    $this->ExaminationCentresExaminations->updateAll(['total_registered' => $studentCount], ['examination_centre_id' => $entity->examination_centre_id, 'examination_id' => $entity->examination_id]);
                    //POCOR-7511 start
                    if ($entity->examination_subjects) {
                        $examinationStudentSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationStudentSubjects');
                        if (!empty($listOfSelectedStudents)) {
                            $entities = [];
                            foreach ($listOfSelectedStudents as $stu) {
                                foreach ($entity->examination_subjects as $Key => $value) {
                                    if ($value['selected'] == 1) {
                                        $studSubArr = array(
                                            'student_id' => $stu,
                                            'examination_subject_id' => $value['subject_id']
                                        );
                                        $entitiesData[] =  $studSubArr;
                                    }
                                }
                            }
                            $entities = $examinationStudentSubjects->newEntities($entitiesData);
                            foreach ($entities as $new_entity) {
                                $examinationStudentSubjects->save($new_entity);
                            }
                        }
                    } //POCOR-7511 end
                }

                if ($entity->auto_assign_to_rooms) {
                    if ($success) {
                        $examCentreRooms = $this->ExaminationCentres->ExaminationCentreRooms
                            ->find()
                            ->leftJoin(['ExaminationCentreRoomsExaminationsStudents' => 'examination_centre_rooms_examinations_students'], [
                                'ExaminationCentreRoomsExaminationsStudents.examination_centre_room_id = ' . $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                'ExaminationCentreRoomsExaminationsStudents.examination_id = ' . $selectedExamination
                            ])
                            ->order([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->select([
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('id'),
                                $this->ExaminationCentres->ExaminationCentreRooms->aliasField('number_of_seats'),
                                'seats_taken' => 'COUNT(ExaminationCentreRoomsExaminationsStudents.student_id)'
                            ])
                            ->where([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('examination_centre_id') => $selectedExaminationCentre])
                            ->group([$this->ExaminationCentres->ExaminationCentreRooms->aliasField('id')])
                            ->toArray();

                        foreach ($examCentreRooms as $room) {
                            $counter = $room->number_of_seats - $room->seats_taken;
                            while ($counter > 0) {
                                $examCentreRoomStudent = array_shift($roomStudents);
                                $newEntity = [
                                    'examination_centre_room_id' => $room->id,
                                    'student_id' => $examCentreRoomStudent['student_id'],
                                    'examination_id' => $examCentreRoomStudent['examination_id'],
                                    'examination_centre_id' => $examCentreRoomStudent['examination_centre_id']
                                ];

                                $ExaminationCentreRoomStudents = TableRegistry::getTableLocator()->get('Examination.ExaminationCentreRoomsExaminationsStudents');
                                $examCentreRoomStudentEntity = $ExaminationCentreRoomStudents->newEntity($newEntity);
                                $saveSucess = $ExaminationCentreRoomStudents->save($examCentreRoomStudentEntity);
                                $counter--;
                            }
                        }
                        if (!empty($roomStudents)) {
                            $model->Alert->warning('ExaminationStudents.notAssignedRoom');
                        }
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return $success;
                }
            } else {
                $model->Alert->warning($this->aliasField('noStudentSelected'));
                $entity->getErrors('student_id', __('There are no students selected'));
                return false;
            }
        };
        return $process;
    }

    //POCOR-7512 start
    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $subjectTable = TableRegistry::getTableLocator()->get('Examination.ExaminationSubjects');
        $subjectData = $subjectTable
            ->find('all')
            ->select([
                'id' => $subjectTable->aliasField('id'),
                'name' => $subjectTable->aliasField('name'),
                'code' => $subjectTable->aliasField('code')
            ])
            ->where([$subjectTable->aliasField('examination_id') => $entity->examination_id])->toArray();
        $entity['examination_subjects'] = $subjectData;

        $this->field('academic_period_id', ['type' => 'readonly']);
        $this->field('examination_id', ['type' => 'readonly']);
        $this->field('openemis_no', ['type' => 'readonly']);
        $this->field('student_id', ['type' => 'readonly']);

        $this->field('examination_subjects', [
            'type' => 'element',
            'element' => 'Examination.institution_examination_subjects',
            'data' => $entity['examination_subjects']
        ]);
        $this->setFieldOrder(['academic_period_id', 'examination_id', 'registration_number', 'openemis_no', 'student_id', 'examination_subjects']);
    }

    //POCOR-9169 start
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'registration_number':
                return __('Candidate Number');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //POCOR-9169 end
    //POCOR-7509 start
    /**
     * Update action buttons for the entity
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param \Cake\ORM\Entity $entity The entity object.
     * @param array $buttons The array of existing buttons.
     * @return array Modified buttons array.
     */
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $referrerUrl = $this->request->referer();
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);


        $syncUserConfigured = TableRegistry::getTableLocator()->get('Configuration.ConfigExternalDataSourceExam')->getOpenemisExamConfiguration();
        if ($this->AccessControl->check(['Examinations', 'syncStudentsToExam', 'execute']) && !empty($syncUserConfigured)) {


            $params = [
                'institution_id' => $entity->institution->id,
                'academic_period_id' => $entity->academic_period_id,
                'examination_id' => $entity->examination_id,
                'examination_centre_id' => $entity->examination_centre_id,
                'openemis_no' => $entity->openemis_no,
                'institution_id' => $entity->institution_id,
                'referrer' => $referrerUrl,
            ];


            $url = [
                'plugin' => 'Examination',
                'controller' => 'Examinations',
                'action' => 'syncStudentsToExam',
            ];


            $buttons['sync'] = [
                'label' => '<i class="kd-process"></i>' . __('Sync'),
                'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
                'url' => $this->setQueryString($url, $params),
            ];
        }

        return $buttons;
    }

    /**
     * Get sync status for the entity
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param \Cake\ORM\Entity $entity The entity object.
     * @return string|null The sync status value.
     */
    public function onGetSyncStatus(EventInterface $event, Entity $entity)
    {

        switch ($entity->sync_status) {
            case 1:
                return 'Completed';
            case -1:
                return 'Error';
            default:
                return null;
        }
    }

    /**
     * Get last synced timestamp formatted
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param \Cake\ORM\Entity $entity The entity object.
     * @return string|null The formatted last synced date.
     */
    public function onGetLastSynced(EventInterface $event, Entity $entity)
    {
        if ($entity->last_synced instanceof FrozenTime || $entity->last_synced instanceof \DateTime) {
            return $entity->last_synced->format('Y-m-d H:i:s');
        }
        return null;
    }

    /**
     * Modify visible fields after the index action
     *
     * @param \Cake\Event\EventInterface $event The event instance.
     * @param mixed $data The data (not used here).
     */
    public function indexAfterAction(EventInterface $event, $data)
    {

        $this->field('date_of_birth', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('identity_type', ['visible' => false]);
        $this->field('nationality', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
        $this->field('repeated', ['visible' => false]);
        $this->field('transferred', ['visible' => false]);


        $this->field('sync_status', ['visible' => true, 'label' => 'Sync Status']);
        $this->field('last_synced', ['visible' => true, 'label' => 'Last Synced']);
    }

    //POCOR-7509 end
}
