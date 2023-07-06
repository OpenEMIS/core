<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Security;

class InstitutionExaminationStudentsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('examination_centres_examinations_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsToMany('IdentityTypes', ['className' => 'IdentityTypes.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->belongsToMany('Genders', ['className' => 'Genders.Genders', 'foreignKey' => 'gender_id']);
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
            'targetForeignKey' => ['examination_centre_id', 'examination_item_id'],
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
            'excludes' => ['id', 'education_subject_id', 'examination_item_id'],
            'pages' => ['index'],
            'filename' => 'RegisteredStudents',
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('CompositeKey');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('registration_number')
            ->add('registration_number', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['examination_id']]],
                'provider' => 'table'
            ])
            ->requirePresence('auto_assign_to_rooms');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $User = TableRegistry::get('security_users');
        $nationalities = TableRegistry::get('nationalities');
        $examinations = TableRegistry::get('examinations');
        $academicPeriod = ($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent() ;
        $examinationId = ($this->request->query['examination_id']) ? $this->request->query['examination_id'] : 0 ;
        $session = $this->request->session();
        $institutionId  = $session->read('Institution.Institutions.id'); 
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
            'education_grade_id' =>$examinations->aliasField('education_grade_id'),
            'student_name' => $User->find()->func()->concat([
                'first_name' => 'literal',
                " ",
                'middle_name' => 'literal',
                " ",
                 'third_name' => 'literal',
                " ",
                'last_name' => 'literal'
            ])
        ])
        ->LeftJoin([$this->AcademicPeriods->alias() => $this->AcademicPeriods->table()],[
            $this->AcademicPeriods->aliasField('id').' = ' . 'InstitutionExaminationStudents.academic_period_id'
        ])
        ->LeftJoin([$this->Users->alias() => $this->Users->table()],[
            $this->Users->aliasField('id').' = ' . 'InstitutionExaminationStudents.student_id'
        ])
        ->LeftJoin([$nationalities->alias() => $nationalities->table()],[
            $nationalities->aliasField('id').' = ' .'Users.nationality_id'
        ])
        ->LeftJoin([$this->IdentityTypes->alias() => $this->IdentityTypes->table()],[
            $this->IdentityTypes->aliasField('id').' = ' . 'Users.identity_type_id'
        ])
        ->LeftJoin([$this->Genders->alias() => $this->Genders->table()],[
            $this->Genders->aliasField('id').' = ' . 'Users.gender_id'
        ])
        ->LeftJoin([$examinations->alias() => $examinations->table()], [
            [$examinations->aliasField('id ='). $this->aliasField('examination_id')],
        ])
        ->where([
            'InstitutionExaminationStudents.academic_period_id' =>  $academicPeriod,
            'InstitutionExaminationStudents.institution_id' =>  $institutionId,
            $this->aliasField('examination_id =') .$examinationId
        ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $InstitutionStudents = TableRegistry::get('InstitutionStudents');
                $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
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
                ->autoFields(true)
                ->first();

                $StudentTransfers = TableRegistry::get('Institution.InstitutionStudentTransfers');
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
                ->autoFields(true)
                ->first();

                if($InstitutionStudentsCurrentData){
                    $student_status = "Yes";
                }else{
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
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
            'type' => 'date',
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

    public function onExcelGetExaminationId(Event $event, Entity $entity)
    {
        if ($entity->has('examination')) {
            return $entity->examination->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetExaminationCentreId(Event $event, Entity $entity)
    {
        if ($entity->has('examination_centre')) {
            return $entity->examination_centre->code_name;
        } else {
            return '';
        }
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity)
    {
        if ($entity->has('institution')) {
            return $entity->institution->code_name;
        } else {
            return '';
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFieldLabel'] = 'onGetFieldLabel';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
         $this->institutionId = $this->Session->read('Institution.Institutions.id');

        //work around for export button showing in pages not specified
        if ($this->action != 'index') {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (array_key_exists('add', $toolbarButtonsArray)) {
            $toolbarButtonsArray['add']['attr']['title'] = __('Register');
        }

        $undoButton['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'UndoExaminationRegistration',
            'add'
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

        $examinationId = $this->request->query('examination_id');

        if (!$this->AccessControl->check(['Institutions', 'ExaminationStudents', 'excel'])) {
            if (isset($extra['toolbarButtons']['export'])) {
                unset($extra['toolbarButtons']['export']);
            }
        }



        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Students','Examinations');       
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

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['elements']['controls'] = ['name' => 'Examination.controls', 'data' => [], 'options' => [], 'order' => 1];
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('examination_education_grade', ['type' => 'readonly']);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('institution_class_id', ['type' => 'select', 'onChangeReload' => true, 'entity' => $entity]);
        $this->field('auto_assign_to_rooms', ['type' => 'select', 'options' => $this->getSelectOptions('general.yesno')]);
        $this->field('subject_id');
        $this->field('student_id', ['entity' => $entity]);
        $this->field('education_grade_id', ['type' => 'hidden']);
        $this->field('registration_number', ['visible' => false]);

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'examination_education_grade', 'examination_centre_id', 'special_needs', 'auto_assign_to_rooms', 'institution_class_id', 'subject_id','student_id'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();

            $attr['default'] = $selectedAcademicPeriod;
            $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_id']);
            }
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_class_id']);
            }
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request)
    {
        $examinationOptions = [];

        if ($action == 'add') {
            $todayDate = Time::now();

            if(!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
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

            $examinationId = isset($request->data[$this->alias()]['examination_id']) ? $request->data[$this->alias()]['examination_id'] : null;
            $this->advancedSelectOptions($examinationOptions, $examinationId, [
                'message' => '{{label}} - ' . $this->getMessage('InstitutionExaminationStudents.notAvailableForRegistration'),
                'selectOption' => false,
                'callable' => function($id) use ($Examinations, $todayDate) {
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

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_class_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_class_id']);
            }
        }
    }

    public function onUpdateFieldExaminationEducationGrade(Event $event, array $attr, $action, $request)
    {
        $educationGrade = '';

        if (!empty($request->data[$this->alias()]['examination_id'])) {
            $selectedExamination = $request->data[$this->alias()]['examination_id'];
            $Examinations = $this->Examinations
                ->get($selectedExamination, [
                    'contain' => ['EducationGrades']
                ])
                ->toArray();

            $educationGrade = $Examinations['education_grade']['name'];
            $request->data[$this->alias()]['education_grade_id'] = $Examinations['education_grade']['id'];
            $attr['attr']['value'] = $educationGrade;
        }

        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {

            $examCentreOptions = [];
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $selectedExamination = $request->data[$this->alias()]['examination_id'];

                $LinkedInstitutions = TableRegistry::get('Examination.ExaminationCentresExaminationsInstitutions');
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

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, $request)
    {
        $specialNeeds = [];

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $ExaminationCentreSpecialNeeds = TableRegistry::get('Examination.ExaminationCentreSpecialNeeds');
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

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request)
    {
        $classes = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id'])) {
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $educationGradeId = $this->Examinations->get($examinationId)->education_grade_id;
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];

                $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
                $classes = $InstitutionClass
                    ->find('list')
                    ->matching('ClassGrades')
                    ->where([$InstitutionClass->aliasField('institution_id') => $this->institutionId,
                        $InstitutionClass->aliasField('academic_period_id') => $academicPeriodId,
                        'ClassGrades.education_grade_id' => $educationGradeId])
                    ->order($InstitutionClass->aliasField('name'))
                    ->toArray();
            }

            $attr['options'] = $classes;
        }

        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, $request)
    {
        $students = [];

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_class_id'])) {
                $academicPeriodId = $request->data[$this->alias()]['academic_period_id'];
                $examinationId = $request->data[$this->alias()]['examination_id'];
                $institutionClassId = $request->data[$this->alias()]['institution_class_id'];
                $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');
                $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];

                $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                $students = $ClassStudents->find()
                    ->matching('EducationGrades')
                    ->leftJoin(['InstitutionExaminationStudents' => 'examination_centres_examinations_students'], [
                        'InstitutionExaminationStudents.examination_id' => $examinationId,
                        'InstitutionExaminationStudents.student_id = '.$ClassStudents->aliasField('student_id')
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
        }

        return $attr;
    }
    public function onUpdateFieldSubjectId(Event $event, array $attr, $action, $request){
        $subjects = [];
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['examination_id']) && !empty($request->data[$this->alias()]['institution_class_id'])) {
                $ExaminationItems=TableRegistry::get('Examination.ExaminationItems');
                $subjects=$ExaminationItems->find()->where([
                                 $ExaminationItems->aliasField('examination_id')=>$request->data[$this->alias()]['examination_id']   
                          ])->toArray();
                }
        $attr['label']="Education Subjects";
        $attr['type'] = 'element';
        $attr['element'] = 'Examination.institution_examination_subjects';
        $attr['data'] = $subjects;
        return $attr;
    }}

  


    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $requestData[$this->alias()]['student_id'] = 0;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        echo "<pre>";
        print_r($entity);
        exit;
    }
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'subject_id':
                return __('Education Subjects');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
