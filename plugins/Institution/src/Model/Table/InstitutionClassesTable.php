<?php

namespace Institution\Model\Table;

use ArrayObject;
use stdClass;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\ConnectionManager;


class InstitutionClassesTable extends ControllerActionTable
{


    use MessagesTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->belongsTo('InstitutionUnits', ['className' => 'Institution.Unit', 'foreignKey' => 'institution_unit_id']);
        $this->belongsTo('InstitutionCourses', ['className' => 'Institution.Course', 'foreignKey' => 'institution_course_id']);

        $this->hasMany('ClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);
        $this->hasMany('SubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'saveStrategy' => 'replace']);
        $this->hasMany('ClassAttendanceRecords', ['className' => 'Institution.ClassAttendanceRecords', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id',
        ]);

        $this->belongsToMany('InstitutionSubjects', [
            'className' => 'Institution.InstitutionSubjects',
            'through' => 'Institution.InstitutionClassSubjects',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'institution_subject_id'
        ]);
        //POCOR-9267 Starts
        $this->hasMany('InstitutionClassGrades', [
            'className' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
        ]);//POCOR-9267 Ends

        /**
         * Shortcuts
         */
        $this->InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');

        // this behavior restricts current user to see All Classes or My Classes
        $this->addBehavior('Security.SecurityAccess');
        $this->addBehavior('Security.InstitutionClass');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'ClassStudents' => ['view', 'edit'],
            'StudentCompetencies' => ['view'],
            'StudentCompetencyComments' => ['view'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'StudentOutcomes' => ['view'],
            'SubjectStudents' => ['index'],
            'Results' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        $this->setDeleteStrategy('restrict');

        $this->addBehavior('ClassExcel', ['excludes' => ['security_group_id', 'identity_number', 'identity_type', 'student_status', 'student_name', 'gender', 'institution_classes_staff_openemis_no', 'special_need', 'openEMIS_ID'], 'pages' => ['view']]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => [
                'Classes' => ['id']
            ]
        ]);
        //POCOR-8538 start
        $this->hasMany('InstitutionClassesCustomFieldValues', [
            'className' => 'InstitutionCustomField.InstitutionClassesCustomFieldValues', // Correct class name
            'dependent' => true,
            'cascadeCallbacks' => true,
            'foreignKey' => 'institution_class_id'
        ]);
        $this->hasMany(
            'CustomFieldValues',
            [
                'className' => 'InstitutionCustomField.InstitutionClassesCustomFieldValues',
                'foreignKey' => 'institution_class_id'
            ]
        );
        $this->addBehavior('CustomField.Record', [
            'model' => 'Institution.InstitutionClasses',
            'fieldKey' => 'institution_custom_field_id',
            'tableColumnKey' => 'institution_custom_table_column_id',
            'tableRowKey' => 'institution_custom_table_row_id',
            'fieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFields'],
            'formKey' => 'institution_custom_form_id',
            'filterKey' => 'institution_custom_filter_id',
            'formFieldClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFields'],
            'recordKey' => 'institution_class_id',
            'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionClassesCustomFieldValues', 'foreignKey' => 'institution_class_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => null,
            'events' => [
                'ControllerAction.Model.add.onInitialize'       => [],
                'ControllerAction.Model.add.beforeSave'         => [],
                'ControllerAction.Model.addEdit.beforePatch'    => [],
                'ControllerAction.Model.addEdit.afterAction'    => [],
                'ControllerAction.Model.add.beforeSave'         => ['callable' => 'addBeforeSave', 'priority' => 500]

            ],
        ]);
        //POCOR-8538 end
        $this->addBehavior('Configuration.CallWebhook', // POCOR-9403
            [
                'entity_create' => 'institution_class_create',
                'entity_delete' => 'institution_class_delete',
                'entity_update' => 'institution_class_update',
                'table_alias' => 'Institution.InstitutionClasses',
                'contain' => []
            ]
        ); // for webhook
        // POCOR-8391 remove annoing log
        //        Log::write('debug', 'Here it us beforeFilter initialize End');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            // ->allowEmpty('staff_id')
            ->requirePresence('name')
            ->requirePresence('academic_period_id') //POCOR-8904
            ->requirePresence('institution_shift_id')//POCOR-8904
            ->add('capacity', 'positive', [//POCOR-8904
                'rule' => ['comparison', '>', 0],
                'message' => 'Please provide valid capacity'
            ])
            ->add('name', 'ruleUniqueNamePerAcademicPeriod', [
                'rule' => 'uniqueNamePerAcademicPeriod',
                'provider' => 'table',
            ])
            // ->add('staff_id', 'ruleCheckHomeRoomTeachers', [
            //     'rule' => ['checkHomeRoomTeachers', 'classes_secondary_staff'],
            //     'provider' => 'table',
            // ])
            ->add('capacity', 'ruleCheckMaxStudentsPerClass', [
                'rule' => ['checkMaxStudentsPerClass'],
                'provider' => 'table',
            ]);

        return $validator;
    }

    public static function uniqueNamePerAcademicPeriod($field, array $globalData)
    {
        $data = $globalData['data'];
        $model = $globalData['providers']['table'];
        $exists = $model->find('all')
            ->select(['id'])
            ->where([
                $model->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
                $model->aliasField('institution_id') => $globalData['data']['institution_id'],
                $model->aliasField('name') => $field,
            ])
            ->toArray();
        if (!empty($exists)) {
            foreach ($exists as $value) {
                if (isset($data['id']) && $value->id == $data['id']) {
                    // if editing an existing value
                    return true;
                    break;
                }
            }
            return false;
        } else {
            return true;
        }
    }

    //POCOR-9613[START]
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-9257: default to 0 for new classes where total_male/female_students is not yet set
        $entity->total_male_students = $entity->total_male_students ?? 0;
        $entity->total_female_students = $entity->total_female_students ?? 0;
    }
    //POCOR-9613[END]

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.delete.afterAction'] = ['callable' => 'deleteAfterAction', 'priority' => 10];
        return $events;
    }
    //POCOR-8323 starts
    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $encodedString = $this->request->getAttribute('params')['pass'][1];
        $query = $this->request->getQuery();
        $academic_period_id = $this->AcademicPeriods->getCurrent();
        if (!empty($query['academic_period_id'])) {
            $academic_period_id = $query['academic_period_id'];
        }
        $education_grade_id = -1;
        if (!empty($query['education_grade_id'])) {
            $education_grade_id = $query['education_grade_id'];
        }

        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons['remove'] = [
            'url' => [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                '0' => 'remove',
                '1' => $encodedString,
                '2' => $this->ControllerAction->paramsEncode(['id' => $entity->id]),
                '_ext' => '',
                'academic_period_id' => $academic_period_id,
                'education_grade_id' => $education_grade_id,
            ],
            'type' => 'button',
            'label' => '<i class="fa fa-trash"></i>' . __('Delete'),
            'attr' => [
                'role' => 'menuitem',
                'tabindex' => -1,
                'escape' => false,
            ]
        ];

        $buttons['view'] = [
            'url' => [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                '0' => 'view',
                '1' => $encodedString,
                '_ext' => '',
                'academic_period_id' => $academic_period_id,
                'education_grade_id' => $education_grade_id,
            ],
            'type' => 'button',
            'label' => '<i class="fa fa-eye"></i>' . __('View'),
            'attr' => [
                'role' => 'menuitem',
                'tabindex' => -1,
                'escape' => false,
            ]
        ];
        return $buttons;
    } //POCOR-8323 ends 

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        $LabelTable = TableRegistry::get('Labels');
        if ($field == 'classes_secondary_staff') {
           // return $this->getMessage($this->aliasField($field));
            //POCOR-9524
            $secondarystaff = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'secondary_staff_id'])->first();
            $secondarystaffName = !empty($secondarystaff->name)
                            ? (string)$secondarystaff->name
                            : (string)$secondarystaff->field_name;
            return  __((string)$secondarystaffName);
        } else if ($field == 'institution_unit_id') {
            $unitname = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'unit'])->first();
            if ($unitname != null) {
                $unit =  $unitname->name; //add this name from Adminsitration > System Setup > Labels
            }
            return  __((string)$unit);
        } else if ($field == 'institution_course_id') {
            $CourseName = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'course'])->first();
            if ($CourseName != null) {
                $Courses =  $CourseName->name; //add this name from Adminsitration > System Setup > Labels
            }
            return  __((string)$Courses);
        } else if ($field == 'staff_id') {
            $teacher = $LabelTable->find()->where(['module_name' => 'Institutions -> Classes', 'field' => 'staff_id'])->first();
            $teacherName = !empty($teacher->name)
                            ? (string)$teacher->name
                            : (string)$teacher->field_name;
            return  __((string)$teacherName);
        } else if ($field == 'name') {
            return __('Class Name');
        }else if ($field == 'total_male_students') {
            return __('Male Students');
        } else if ($field == 'total_female_students') {
            return __('Female Students');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    /**
     * common function to get institution id
     * @return string|null
     *
     */
    public
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id = $this->getQueryString('institution_id');
        if (!$institution_id) {
            $institution_id = $this->getQueryString('id');
            if (!$institution_id) {
                $session = $this->request->getSession();
                $institution_id = $session->read('Institution.Institutions.id');
                if (!$institution_id) {
                    if ($debugString != "") {
                        die($debugString . 'For Developer: You should put institution_id into query string first');
                    }
                }
            }
        }
        return $institution_id;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {

        // POCOR-8391 remove annoing log
        //        Log::write('debug', 'Here it us beforeFilter beforeAction Start');

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $this->controllerAction = $extra['indexButtons']['view']['url']['action'];
        $query = $this->request->getQuery();

        if (!empty($this->request->getData['InstitutionClasses']['institution_shift_id'])) {
            $extra['institution_shift_id'] = $this->request->getData['InstitutionClasses']['institution_shift_id'];
        }

        $institutionId = $this->getInstitutionID(__FUNCTION__ . ':' . __LINE__);
        $extra['institution_id'] = $institutionId;
        $academicPeriodOptions = $this->getAcademicPeriodOptions($institutionId);

        // POCOR-9538: If no academic periods available (no programmes/grades exist),
        // redirect to Programmes add page with flash message
        if (empty($academicPeriodOptions)) {
            $this->Alert->error(__('Please add a Programme/Grade in the Institution before accessing Classes.'), ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            $url = $this->url('index');
            $url['action'] = 'Programmes';
            $this->controller->redirect(
                    Router::url($url, true)
            );
            return false;
        }

        $selectedAcademicPeriodId = $this->AcademicPeriods->getCurrent();

        if ($this->action == 'index') {
            if (empty($query['academic_period_id'])) {
                $query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedGradeType = 'single';
            if (isset($query['grade_type'])) {
                $selectedGradeType = $query['grade_type'];
            }
            $gradeBehaviors = ['Institution.SingleGrade', 'Institution.MultiGrade'];
            foreach ($gradeBehaviors as $behavior) {
                if ($this->hasBehavior($behavior)) {
                    $this->removeBehavior($behavior);
                }
            }
            if ($selectedGradeType == 'single') {
                $this->addBehavior('Institution.SingleGrade');
            } else {
                $this->addBehavior('Institution.MultiGrade');
            }
            $extra['selectedGradeType'] = $selectedGradeType;
        }
        if (array_key_exists($this->getAlias(), $this->request->getData())) {
            $selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
        } elseif ($this->action == 'edit' && isset($this->request->getParam('pass')[1])) {
            $id = $this->paramsDecode($this->request->getParam('pass')[1]);
            if ($this->exists($id)) {
                $selectedAcademicPeriodId = $this->get($id)->academic_period_id;
                $selectedInstitutionUnitId = $this->get($id)->institution_unit_id;
                // $selectedAcademicPeriodId = $this->get($id)->academic_period_id;
            }
        }
        $extra['selectedInstitutionUnitId'] = $selectedInstitutionUnitId;
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        //POCOR-5852 starts
        if (empty($this->request->getQuery('academic_period_id'))) {
            $selectedAcademicPeriodId = $selectedAcademicPeriodId;
            $this->request = $this->request->withQueryParams(array_merge(
                $this->request->getQueryParams(),
                ['academic_period_id' => $selectedAcademicPeriodId]
            ));

            $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
            if (!empty($gradeOptions)) {
                $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
            }
            $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
            $this->request = $this->request->withQueryParams(array_merge(
                $this->request->getQueryParams(),
                ['education_grade_id' => $selectedEducationGradeId]
            ));
        }
        //POCOR-5852 ends
        $this->field('class_number', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('institution_shift_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true]]);

        $this->field('institution_unit_id', ['type' => 'select', 'visible' => ['index' => false, 'add' => false, 'view' => false, 'edit' => false]]); //POCOR-8671
        $this->field('institution_course_id', ['type' => 'select', 'visible' => ['index' => false, 'add' => false, 'view' => false, 'edit' => false]]); //POCOR-8671

        $this->field('total_students', ['type' => 'integer', 'visible' => ['index' => true]]);
        $this->field('subjects', ['override' => true, 'type' => 'integer', 'visible' => ['index' => true]]);

        $this->field('students', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Classes/students',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'students' => [],
                'studentOptions' => []
            ],
            'visible' => ['view' => true, 'edit' => true]
        ]);
        $this->field('education_grades', [
            'type' => 'element',
            'element' => 'Institution.Classes/multi_grade',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'grades' => []
            ],
            'visible' => ['view' => true]
        ]);

        $this->field('staff_id', [
            'type' => 'select',
            'options' => [],
            'visible' => ['index' => true, 'view' => true, 'edit' => true],
            'attr' => [
                'label' => $this->getMessage($this->aliasField('staff_id'))
            ]
        ]);
        $this->field('classes_secondary_staff');

        $this->field('multigrade');
        $this->field('capacity', [
            'attr' => ['label' => __('Capacity') . $this->tooltipMessage()]
        ]);

        $this->setFieldOrder([
            'name',
            'institution_unit_id',
            'institution_course_id',
            'staff_id',
            'classes_secondary_staff',
            'multigrade',
            'capacity',
            'total_male_students',
            'total_female_students',
            'total_students',
            'subjects'
        ]);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'All Classes', 'Academic');
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

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $action = $this->action;
        //Start:POCOR-6644
        if (!isset($extra['entity']->institution_shift_id) || empty($extra['entity']->institution_shift_id) || ($extra['entity']->institution_shift_id == "")) {
        } else {
            $institutionShiftId = $extra['entity']->institution_shift_id;
            if ($action != 'add') {
                $staffOptions = [];
                $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
                $institutionId = $extra['institution_id'];
                if ($selectedAcademicPeriodId > -1) {
                    if ($action == 'index') {
                        $action = 'view';
                    }
                }
                /** POCOR-6721 starts - due to getStaffOptions function Institutions>Academic>Classes page was loding long while viewing class */
                if ($action == 'edit') {
                    $staffOptions = $this->getStaffOptions($institutionId, $action, $selectedAcademicPeriodId);
                    $this->fields['staff_id']['options'] = $staffOptions;
                    $this->fields['staff_id']['select'] = false;

                    $this->fields['institution_unit_id']['options'] = [];
                }

                /** POCOR-6721 ends */
            }
        }
        //End:POCOR-6644
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('classStudents') && empty($data['classStudents'])) { //only utilize save by association when class student empty.
            $data['class_students'] = [];
            $data['total_male_students'] = 0;
            $data['total_female_students'] = 0;
            $data->offsetUnset('classStudents');
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        // POCOR-9403 cleancoded
        $this->handleClassCustomFields($entity);
        $this->syncClassStudents($entity, $options);
        $this->dispatchEvent('Model.afterFullSave', compact('entity', 'options'));
        if ($entity->isNew()) {
            $this->InstitutionSubjects->autoInsertSubjectsByClass($entity);
        }
    }

    private function handleClassCustomFields(Entity $entity): void
    {
        try {
            if ($entity->has('custom') && !empty($entity->custom)) {
                $userId = $entity->modified_user_id ?? $entity->created_user_id;
                self::saveCustomFields($entity->custom, $entity->id, $userId);
            }
        } catch (\Exception $e) {
            Log::debug(print_r(['Error Saving Class Custom Fields:' => $e->getMessage()], true));
        }
    }

    private function syncClassStudents(Entity $entity, ArrayObject $options): void
    {
        if (empty($entity->classStudents)) {
            // Handle bulk unassignment
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $SubjectStudents->deleteAll([$SubjectStudents->aliasField('institution_class_id') => $entity->id]);
            return;
        }

        $ClassStudents = $this->ClassStudents;
        $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $newStudents = [];

        foreach ($entity->classStudents as $encoded) {
            $student = json_decode($this->urlsafeB64Decode($encoded), true);
            $newStudents[$student['student_id']] = $student;
        }

        $existing = $ClassStudents->find()
            ->select(['id', 'student_id', 'institution_class_id', 'education_grade_id'])
            ->matching('StudentStatuses', fn($q) =>
            $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']])
            )
            ->where([$ClassStudents->aliasField('institution_class_id') => $entity->id])
            ->toArray();

        foreach ($existing as $record) {
            if (!isset($newStudents[$record->student_id])) {
                $ClassStudents->delete($record);
                $SubjectStudents->deleteAll([
                    $SubjectStudents->aliasField('institution_class_id') => $entity->id,
                    $SubjectStudents->aliasField('student_id') => $record->student_id,
                ]);
            } else {
                unset($newStudents[$record->student_id]);
            }
        }

        foreach ($newStudents as $student) {
            $student['id'] ??= Text::uuid();
            $newEntity = $ClassStudents->newEntity($student);
            if ($ClassStudents->save($newEntity)) {
                $SubjectStudents->updateAll(
                    ['institution_class_id' => $newEntity->institution_class_id],
                    ['id' => $newEntity->id]
                );
            }
        }
    }



    // POCOR-8538 start
    private static function saveCustomFields($customFields, $classId, $createdUserId): array
    {
        //        Log::debug(print_r(['beforeSave' => $customFields], true));
        $cv = [];
        if (!empty($customFields)) {
            $customFieldValuesTable =
                TableRegistry::getTableLocator()->get('InstitutionCustomField.InstitutionClassesCustomFieldValues');;

            // Delete existing custom fields
            $customFieldValuesTable->deleteAll(
                [$customFieldValuesTable->aliasField('institution_class_id') => $classId]
            );
            $relevantFields = [
                'text_value',
                'number_value',
                'decimal_value',
                'textarea_value',
                'time_value',
                'date_value',
                'file'
            ];
            // Save new custom fields
            foreach ($customFields as $field) {
                $fieldData = [
                    'id' => Text::uuid(),
                    'institution_class_id' => $classId,
                    'created_user_id' => $createdUserId,
                    'created' => date('Y-m-d H:i:s')
                ];

                // Relevant fields to check

                $hasValue = false;

                foreach ($field as $key => $value) {
                    // Check if the current key is in the relevant fields and has a value
                    if (in_array($key, $relevantFields) && (!empty($value) || $value !== null || $value != '')) {
                        $fieldData[$key] = $value;
                        $hasValue = true;
                        //                        Log::debug(print_r([$key, $value], true));
                    }
                    if (!in_array($key, $relevantFields)) {
                        $fieldData[$key] = $value;
                    }
                }

                // Only create and save the entity if at least one relevant field has a value
                if ($hasValue) {
                    if (isset($fieldData['custom_field_id'])) {
                        // Copy the value from 'custom_field_id' to 'student_custom_field_id'
                        $fieldData['institution_custom_field_id'] = $fieldData['custom_field_id'];
                        // Remove the old 'custom_field_id' key
                        unset($fieldData['custom_field_id']);
                    }
                    //                    Log::debug(print_r($fieldData, true));
                    $fieldEntity = $customFieldValuesTable->newEntity($fieldData);
                    try {
                        $cv[] = $customFieldValuesTable->save($fieldEntity);
                    } catch (\Exception $e) {
                        Log::debug(__FUNCTION__);
                        Log::debug('Error: ' . $e->getMessage());
                    }
                }
            }
        }
        return $cv;
    }
    // POCOR-8538 end
    /******************************************************************************************************************
     **
     ** delete action methods
     **
     ******************************************************************************************************************/
    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        // only show the student and the subject of the class.
        $extra['excludedModels'] = [
            $this->ClassGrades->getAlias(),
            // $this->ClassStudents->alias(),
            // $this->SubjectStudents->alias(),
            $this->EducationGrades->getAlias(),
            $this->Students->getAlias(),
            $this->InstitutionSubjects->getAlias()
        ];
        $homeRoomTeacher = (isset($entity->staff_id) && $entity->staff_id > 0) ? 1 : 0;
        $extra['associatedRecords'][] = ['model' => 'HomeRoomTeacher', 'count' => $homeRoomTeacher];
    }

    public function deleteAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $errorMessage = $this->aliasField('stopDeleteWhenStudentExists');
        if (isset($extra['errorMessage']) && $extra['errorMessage'] == $errorMessage) {
            $this->Alert->warning($errorMessage, ['reset' => true]);
        }

    }

    public function onBeforeDelete(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $Students = $this->ClassStudents;
        $conditions = [$Students->aliasField($Students->getForeignKey()) => $entity->id];
        if ($Students->exists($conditions)) {
            $extra['errorMessage'] = $this->aliasField('stopDeleteWhenStudentExists');
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        }
    }


    /******************************************************************************************************************
     **
     ** index action methods
     **
     ******************************************************************************************************************/
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $query = $this->request->getQuery();
        if (isset($query['grade_type'])) {
            $action = $this->url('index');
            unset($action['grade_type']);
            unset($action['?']); //POCOR-8323 remove grade_type and queryString value from url
            $this->controller->redirect($action);
        }

        $Classes = $this;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId) {
                return $Classes->find()
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);

        if (!empty($gradeOptions)) {
            $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
        } else {
            $gradeOptions = [-1 => __('All Grades')];
        }

        $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
        $this->advancedSelectOptions($gradeOptions, $selectedEducationGradeId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function ($id) use ($Classes, $institutionId, $selectedAcademicPeriodId) {
                /**
                 * If statement added on PHPOE-1762 for PHPOE-1766
                 * If $id is -1, get all classes under the selected academic period
                 */

                $join = [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
                    ]
                ];

                if ($id > 0) {
                    $join['conditions']['InstitutionClassGrades.education_grade_id'] = $id;
                }

                $query = $Classes->find()
                    ->join([$join])
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                    ]);
                return $query->count();
            }
        ]);
        $extra['selectedEducationGradeId'] = $selectedEducationGradeId;
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Institution.Classes/controls',
            'data' => [
                'academicPeriodOptions' => $academicPeriodOptions,
                'encodedQueryString' => $encodedQueryString,
                'selectedAcademicPeriod' => $selectedAcademicPeriodId,
                'gradeOptions' => $gradeOptions,
                'selectedGrade' => $selectedEducationGradeId,
            ],
            'options' => [],
            'order' => 3
        ];

        $configItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configItemsData = $configItems->find()->where(['type' => 'Columns for Institutions Classes List Page', 'visible' => 1])->toArray(); //POCOR-8671
        //echo "<pre>";print_r($configItemsData);die;
        foreach ($configItemsData as $configItemsData1) {
            if (($configItemsData1['code'] == 'class_name') && ($configItemsData1['value'] == 0)) {
                $this->fields['name']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_unit') && ($configItemsData1['value'] == 0)) {
                $this->fields['institution_unit_id']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_course') && ($configItemsData1['value'] == 0)) {
                $this->fields['institution_course_id']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_homeroom_teacher') && ($configItemsData1['value'] == 0)) {
                $this->fields['staff_id']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_secondary_teacher') && ($configItemsData1['value'] == 0)) {
                $this->fields['classes_secondary_staff']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_multi_grade') && ($configItemsData1['value'] == 0)) {
                $this->fields['multigrade']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_capacity') && ($configItemsData1['value'] == 0)) {
                $this->fields['capacity']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_male_student') && ($configItemsData1['value'] == 0)) {
                $this->fields['total_male_students']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_female_student') && ($configItemsData1['value'] == 0)) {
                $this->fields['total_female_students']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_total_student') && ($configItemsData1['value'] == 0)) {
                $this->fields['total_students']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_subjects') && ($configItemsData1['value'] == 0)) {
                $this->fields['subjects']['visible'] = false;
            }
        }

        //$this->setFieldOrder('name','institution_unit_id','institution_course_id','');
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $sortable = !is_null($this->request->getQuery('sort')) ? true : false;

        $query
            ->find('byGrades', [
                'education_grade_id' => $extra['selectedEducationGradeId'],
            ])
            ->select([
                'id',
                'name',
                'class_number',
                'institution_unit_id',
                'institution_course_id',
                'capacity',
                'staff_id',
                'total_male_students',
                'total_female_students',
                'institution_shift_id',
                'institution_id',
                'academic_period_id',
                'modified_user_id',
                'modified',
                'created_user_id',
                'created',
                'education_stage_order' => $query->func()->min('EducationStages.order')
            ])
            ->contain([ //POCOR-8852
                'Staff',
                'ClassesSecondaryStaff.SecondaryStaff'
            ])
            ->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])
            ->group([$this->aliasField('id')]);

        if (!$sortable) {
            $query
                ->order([
                    'education_stage_order',
                    $this->aliasField('name') => 'ASC'
                ]);
        }
    }


    public function findHomeOrSecondary(Query $query, array $options)
    {
        if (isset($options['class_id']) && isset($options['staff_id'])) {
            $InstitutionClassesSecondaryStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionClassesSecondaryStaff');

            $classId = $options['class_id'];
            $staffId = $options['staff_id'];

            $query
                ->select(['staff_id' => $this->aliasField('staff_id')])
                ->where([
                    $this->aliasField('id') => $classId,
                    'OR' => [
                        [$this->aliasField('staff_id') => $staffId]
                    ]
                ])
                ->union(
                    $InstitutionClassesSecondaryStaff
                        ->find()
                        ->select(['staff_id' => $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')])
                        ->where([
                            $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id') => $staffId,
                            $InstitutionClassesSecondaryStaff->aliasField('institution_class_id') => $classId
                        ])
                );

            return $query;
        }
    }
    public function findTranslateItem(Query $query, array $options)
    {
        return $query
            ->formatResults(function ($results) {
                $arrResults = $results->toArray();
                foreach ($arrResults as &$value) {
                    if (isset($value['class_students']) && is_array($value['class_students'])) {
                        foreach ($value['class_students'] as $student) {
                            $student['student_status']['name'] = __($student['student_status']['name']);
                        }
                    }
                }
                return $arrResults;
            });
    }

    public function findClassDetails(Query $query, array $options)
    {
        // POCOR-2547 sort list of staff and student by name
        // move the contain from institution.class.student.ctrl.js since its using finder method
        return $query
            ->find('translateItem')
            ->contain([
                'ClassStudents' => [
                    'sort' => ['Users.first_name', 'Users.last_name']
                ],
                'ClassStudents.StudentStatuses' => function ($q) {
                    // return $q->where([('StudentStatuses.code NOT IN ') => ['TRANSFERRED', 'WITHDRAWN']]);
                    // POCOR-6454[START]
                    return $q->where([('StudentStatuses.code NOT IN ') => ['TRANSFERRED', 'WITHDRAWN', 'REPEATED']]);
                    // POCOR-6454[END]
                },
                'ClassStudents.Users.Genders',
                'ClassStudents.EducationGrades',
                'AcademicPeriods',
                'ClassesSecondaryStaff.SecondaryStaff',
                'CustomFieldValues.CustomFields' //POCOR-8538
            ]);
    }

    public function findByGrades(Query $query, array $options)
    {
        $sortable = isset($options['sort']) ? $options['sort'] : false;

        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $EducationStages = TableRegistry::getTableLocator()->get('Education.EducationStages');

        $gradeId = $options['education_grade_id'];
        $join = [
            'table' => 'institution_class_grades',
            'alias' => 'InstitutionClassGrades',
            'conditions' => [
                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id'
            ]
        ];

        if ($gradeId > 0) {
            $join['conditions']['InstitutionClassGrades.education_grade_id'] = $gradeId;
        }

        $query = $query
            ->join([$join])

            ->innerJoin(
                [$EducationGrades->getAlias() => $EducationGrades->getTable()],
                [$EducationGrades->aliasField('id = ') . 'InstitutionClassGrades.education_grade_id']
            )
            ->innerJoin(
                [$EducationStages->getAlias() => $EducationStages->getTable()],
                [$EducationStages->aliasField('id = ') . 'EducationGrades.education_stage_id']
            );

        return $query;
    }


    /******************************************************************************************************************
     **
     ** view action methods
     **
     ******************************************************************************************************************/
    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {

        if ($extra['selectedAcademicPeriodId'] == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'Classes'
            ]);
        }

        $requestQuery = $this->request->getQuery();
        //POCOR-8323 not using in v3
        // $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        // if(empty($requestQuery)){
        //     $requestQuery = array(
        //                     'academic_period_id' => !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent()
        //                 );
        // }//POCOR-8323 ends
        if (isset($requestQuery['academic_period_id']) || isset($requestQuery['education_grade_id'])) {
            $action = $this->url('view');
            if (isset($requestQuery['academic_period_id'])) {
                unset($action['academic_period_id']);
            }
            if (isset($requestQuery['education_grade_id'])) {
                unset($action['education_grade_id']);
            }
            //$this->controller->redirect($action);
        }

        $this->field('total_students', ['visible' => true]);

        $configItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configItemsData = $configItems->find()->where(['type' => 'Fields for Institutions Classes Details Page', 'visible' => 1])->toArray(); //POCOR-8671
        foreach ($configItemsData as $configItemsData1) {
            if (($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 0)) {
                $this->fields['institution_unit_id']['visible'] = false;
            }
            if (($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 0)) {
                $this->fields['institution_course_id']['visible'] = false;
            }
        }

        $this->setFieldOrder([
            'academic_period_id',
            'name',
            'institution_shift_id',
            'education_grades',
            'institution_unit_id',
            'institution_course_id',
            'capacity',
            'total_male_students',
            'total_female_students',
            'total_students',
            'staff_id',
            'classes_secondary_staff',
            'multigrade',
            'students'
        ]);

        // overwrite back button
        /*$btnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        $extraButtons = [
            'export' => [
                'Institution' => ['Institutions', 'IndividualClassExport', 'excel'],
                'action' => 'IndividualClassExport', 'excel',
                'icon' => '<i class="fa kd-export"></i>',
                'title' => __('Export')
            ]
        ];
        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'index']
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }*/
        // back button
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {

        $decodedClass = $this->paramsDecode($this->request->getParam('pass')[1]);
        if (!empty($decodedClass)) {
            $classId = $decodedClass['id'];
        }
        /*POCOR-6566 starts*/
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $grades = [];
        $classGradeData = $this->find()
            ->select(['grade_id' => $InstitutionClassGrades->aliasField('education_grade_id')])
            ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()], [
                $this->aliasField('id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
            ])
            ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
            ->toArray();
        if (!empty($classGradeData)) {
            foreach ($classGradeData as $data) {
                $grades[] = $data->grade_id;
            }
        }
        /*POCOR-6566 ends*/
        $extra['selectedGrade'] = -1;
        $extra['selectedStatus'] = -1;
        $extra['selectedGender'] = -1;
        if (array_key_exists('queryString', $this->request->getQuery())) {
            $queryString = $this->paramsDecode($this->request->getQuery('queryString'));

            if (!empty($queryString) && isset($queryString['grade'])) {
                $extra['selectedGrade'] = $queryString['grade'];
            }

            if (!empty($queryString) && isset($queryString['status'])) {
                $extra['selectedStatus'] = $queryString['status'];
            }


            if (!empty($queryString) && isset($queryString['gender'])) {
                $extra['selectedGender'] = $queryString['gender'];
            }

            if (!empty($queryString) && isset($queryString['sort'])) {
                $extra['sort'] = $queryString['sort'];
            }

            if (!empty($queryString) && isset($queryString['direction'])) {
                $extra['direction'] = $queryString['direction'];
            }
        }

        $sortConditions = '';
        if (!empty($extra['sort'])) {
            if ($extra['sort'] == 'name') {
                $sortConditions = 'Users.first_name ' .  $extra['direction'];
            } elseif ($extra['sort'] == 'openemis_no') {
                $sortConditions = 'Users.openemis_no ' .  $extra['direction'];
            }
        }

        if ($sortConditions) {
            $query->contain([
                'AcademicPeriods',
                'InstitutionShifts.ShiftOptions',
                'InstitutionUnits',
                'InstitutionCourses',
                'EducationGrades',
                'Staff',
                'ClassesSecondaryStaff.SecondaryStaff',
                'ClassStudents' => [
                    'Users.Genders',
                    //START: POCOR-6623
                    'Users.Identities',
                    //END: POCOR-6623
                    'Users.SpecialNeeds',
                    'EducationGrades',
                    'StudentStatuses',
                    'sort' => [$sortConditions]
                ],
            ]);
        } else {
            $query->contain([
                'AcademicPeriods',
                'InstitutionShifts.ShiftOptions',
                'InstitutionUnits',
                'InstitutionCourses',
                'EducationGrades',
                'Staff',
                'ClassesSecondaryStaff.SecondaryStaff',
                'ClassStudents' => [
                    'Users.Genders',
                    //START: POCOR-6623
                    'Users.Identities',
                    //END: POCOR-6623
                    'Users.SpecialNeeds',
                    /*POCOR-6566 starts*/
                    'EducationGrades' => function ($q) use ($grades) {
                        return $q
                            ->where([
                                ['EducationGrades.id IN' => $grades],
                            ]);
                    },
                    /*POCOR-6566 ends*/
                    'StudentStatuses'
                ],
            ]);
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        //generate student filter.
        $params = $this->getQueryString();
        $baseUrl = $this->url($this->action, true);

        $this->fields['students']['data']['baseUrl'] = $baseUrl;
        $this->fields['students']['data']['params'] = $params;

        $gradeOptions = [];
        $statusOptions = [];
        $genderOptions = [];

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configureStudentName = $ConfigItems->value("configure_student_name");

        foreach ($entity->class_students as $key => $value) {
            if (!empty($value->education_grade)) { //grade filter
                $gradeOptions[$value->education_grade->id]['name'] = $value->education_grade->name;
                $gradeOptions[$value->education_grade->id]['order'] = $value->education_grade->order;

                $params['grade'] = $value->education_grade->id;
                $params['status'] = $extra['selectedStatus']; //maintain current status selection
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $gradeOptions[$value->education_grade->id]['url'] = $url;
            }

            if (!empty($value->student_status)) { //status filter
                $statusOptions[$value->student_status->id]['name'] = $value->student_status->name;
                $statusOptions[$value->student_status->id]['order'] = $value->student_status->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $value->student_status->id;
                $params['gender'] = $extra['selectedGender'];
                $url = $this->setQueryString($baseUrl, $params);

                $statusOptions[$value->student_status->id]['url'] = $url;
            }

            if (!empty($value->user) && !empty($value->user->gender)) { //gender filter
                $genderOptions[$value->user->gender->id]['name'] = $value->user->gender->name;
                $genderOptions[$value->user->gender->id]['order'] = $value->user->gender->id;

                $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
                $params['status'] = $extra['selectedStatus'];
                $params['gender'] = $value->user->gender->id;
                $url = $this->setQueryString($baseUrl, $params);

                $genderOptions[$value->user->gender->id]['url'] = $url;
            }

            //if student does not fullfil the filter, then unset from array
            if ($extra['selectedGrade'] != -1 && $value->education_grade->id != $extra['selectedGrade']) {
                unset($entity->class_students[$key]);
            }

            if ($extra['selectedStatus'] != -1 && $value->student_status->id != $extra['selectedStatus']) {
                unset($entity->class_students[$key]);
            }

            if ($extra['selectedGender'] != -1 && $value->user->gender->id != $extra['selectedGender']) {
                unset($entity->class_students[$key]);
            }
        }

        //for all grades / no option
        $gradeOptions[-1]['name'] = count($gradeOptions) > 0 ? '-- ' . __('All Grades') . ' --' : '-- ' . __('No Options') . ' --';
        $gradeOptions[-1]['id'] = -1;
        $gradeOptions[-1]['order'] = 0;

        $params['grade'] = -1;
        $params['status'] = $extra['selectedStatus']; //maintain current status selection
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $gradeOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($gradeOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        //for all statuses option
        $statusOptions[-1]['name'] = count($statusOptions) > 0 ? '-- ' . __('All Statuses') . ' --' : '-- ' . __('No Options') . ' --';
        $statusOptions[-1]['id'] = -1;
        $statusOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = -1;
        $params['gender'] = $extra['selectedGender'];
        $url = $this->setQueryString($baseUrl, $params);

        $statusOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($statusOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        //for all gender option
        $genderOptions[-1]['name'] = count($genderOptions) > 0 ? '-- ' . __('All Genders') . ' --' : '-- ' . __('No Options') . ' --';
        $genderOptions[-1]['id'] = -1;
        $genderOptions[-1]['order'] = 0;

        $params['grade'] = $extra['selectedGrade']; //maintain current grade selection
        $params['status'] = $extra['selectedStatus'];
        $params['gender'] = -1;
        $url = $this->setQueryString($baseUrl, $params);

        $genderOptions[-1]['url'] = $url;

        //order array by 'order' key
        uasort($genderOptions, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        //set option and selected filter value
        $this->fields['students']['data']['filter']['education_grades']['options'] = $gradeOptions;
        $this->fields['students']['data']['filter']['education_grades']['selected'] = $extra['selectedGrade'];

        $this->fields['students']['data']['filter']['student_status']['options'] = $statusOptions;
        $this->fields['students']['data']['filter']['student_status']['selected'] = $extra['selectedStatus'];

        $this->fields['students']['data']['filter']['genders']['options'] = $genderOptions;
        $this->fields['students']['data']['filter']['genders']['selected'] = $extra['selectedGender'];
        $this->fields['students']['data']['configure_student_name'] = $configureStudentName;

        $this->fields['education_grades']['data']['grades'] = $entity->education_grades;

        $this->fields['students']['data']['students'] = $entity->class_students;

        $academicPeriodOptions = $this->getAcademicPeriodOptions($entity->institution_id);
    }

    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('institution_unit_id', ['visible' => true]);
        $this->setFieldOrder([
            'academic_period_id',
            'name',
            'institution_shift_id',
            'institution_unit_id',
            'institution_course_id'
        ]);
    }


    /******************************************************************************************************************
     **
     ** add action methods
     **
     ******************************************************************************************************************/
    // selected grade_type behavior's addBeforeAction will be called later
    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $query = $this->request->getQuery();
        if (isset($query['academic_period_id']) || isset($query['education_grade_id'])) {
            $action = $this->url('add');
            if (isset($query['academic_period_id'])) {
                unset($action['academic_period_id']);
            }
            if (isset($query['education_grade_id'])) {
                unset($action['education_grade_id']);
            }
            //$this->controller->redirect($action);
        }
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];
        if (array_key_exists($this->getAlias(), $this->request->getData())) {
            $academicPeriodOptions = $this->getAcademicPeriodOptions($extra['institution_id']);
            $selectedAcademicPeriodId = $this->postString('academic_period_id', $academicPeriodOptions);
        }
        if ($selectedAcademicPeriodId == -1) {
            return $this->controller->redirect([
                'plugin' => $this->controller->getPlugin(),
                'controller' => $this->controller->getName(),
                'action' => 'Classes'
            ]);
        }
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['selectedEducationGradeId'] = 0;

        $this->Navigation->substituteCrumb(ucwords(strtolower($this->action)), ucwords(strtolower($this->action)) . ' ' . ucwords(strtolower($extra['selectedGradeType'])) . ' Grade');
        $queryString = $this->request->getQuery('queryString');
        if (empty($queryString)) {
            $queryString = $this->request->getParam('pass')[1];
        }
        $tabElements = [
            'single' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', '1' => $queryString, 'grade_type' => 'single', 'queryString' => $queryString],
                'text' => $this->getMessage($this->aliasField('singleGrade'))
            ],
            'multi' => [
                'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Classes', 'add', '1' => $queryString, 'grade_type' => 'multi', 'queryString' => $queryString],
                'text' => $this->getMessage($this->aliasField('multiGrade'))
            ],
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        //POCOR-7803 :: start
        $configItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $configItemsData = $configItems->find()->where(['type' => 'Fields for Institutions Classes Details Page', 'visible' => 1])->toArray(); //POCOR-8671
        foreach ($configItemsData as $configItemsData1) {
            if (($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 0)) {
                $unitEnable = 0;
            } elseif (($configItemsData1['code'] == 'class_ins_unit') && ($configItemsData1['value'] == 1)) {
                $unitEnable = 1;
            }
            if (($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 0)) {
                $courseEnable = 0;
            } elseif (($configItemsData1['code'] == 'class_ins_course') && ($configItemsData1['value'] == 1)) {
                $courseEnable = 1;
            }
        }
        if ($unitEnable == 0) {
            $this->field('institution_unit_id', ['visible' => false]);
        }
        if ($courseEnable == 0) {
            $this->field('institution_course_id', ['visible' => false]);
        }
        //POCOR-7803 :: start

        $this->field('multigrade', ['visible' => false]);
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = $extra['selectedAcademicPeriodId'];

        if ($selectedAcademicPeriodId > -1) {
            $shiftOptions = $this->InstitutionShifts->getShiftOptions($institutionId, $selectedAcademicPeriodId);
        } else {
            $shiftOptions = [];
        }


        $unitOptions1 = $this->InstitutionUnits->getUnitOptions($institutionId, $selectedAcademicPeriodId);
        $courseOptions1 = $this->InstitutionCourses->getCourseOptions($institutionId, $selectedAcademicPeriodId);



        // $InsUnit = TableRegistry::getTableLocator()->get('institution_units');
        // $InsCourse =  TableRegistry::getTableLocator()->get('institution_courses');


        // $unitOptions[0] = "-------select----------";
        // $courseOptions[0] = "-----------select--------";
        //$unitOptions = $InsUnit->find('list',['keyField' => 'id', 'valueField' => 'name']);
        //$courseOptions = $InsCourse->find('list',['keyField' => 'id', 'valueField' => 'name']);
        //echo "<pre>";print_r($unitOptions1->toArray());die;
        //$courseOptions =[];
        $this->fields['institution_shift_id']['options'] = $shiftOptions;
        $this->fields['institution_shift_id']['onChangeReload'] = true;

        $this->fields['institution_unit_id']['options'] = $unitOptions1;
        $this->fields['institution_course_id']['options'] = $courseOptions1;

        if (empty($shiftOptions)) {
            $this->Alert->warning($this->aliasField('noShift'));
        }


        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->fields['academic_period_id']['onChangeReload'] = true;
        $this->fields['academic_period_id']['default'] = $this->AcademicPeriods->getCurrent();

        $this->controller->set('selectedAction', $extra['selectedGradeType']);
    }

    /******************************************************************************************************************
     **
     ** field specific methods
     **
     ******************************************************************************************************************/
    public function onGetInstitutionShiftId(EventInterface $event, Entity $entity)
    {
        if ($entity->institution_shift->institution_id != $entity->institution_id) { //if the current institution is not the owner of the shift.
            $ownerInfo = $this->Institutions->get($entity->institution_shift->institution_id)->toArray(); //show more information of the shift owner
            return $ownerInfo['code_name'] . ' - ' . $entity->institution_shift->shift_option->name;
        } else {
            return $entity->institution_shift->shift_option->name;
        }
    }

    public function getUnitId()
    {
        $InsUnit = TableRegistry::getTableLocator()->get('Institution.Unit');
        $unitOptions = $InsUnit->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
        return $unitOptions;
    }
    public function getCourseId()
    {
        $InsCourse =  TableRegistry::getTableLocator()->get('Institution.Course');
        $courseOptions = $InsCourse->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
        return $courseOptions;
    }

    public function onGetStaffId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $institutionId = $this->getQueryString('institution_id'); //POCOR-8323
            if ($entity->has('staff')) {
                return $event->getSubject()->Html->link($entity->staff->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StaffUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->staff->id, 'institution_id' => $institutionId, 'staff_id' => $entity->staff->id]) //POCOR-8323
                ]);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        } else {
            if ($entity->has('staff')) {
                return $entity->staff->name_with_id;
            } //POCOR-8852
            else if ($entity->has('staff_id') && $entity->staff_id > 0) {
                $Staff = TableRegistry::getTableLocator()->get('Staff.Staff');
                $staffEntity = $Staff->get($entity->staff_id);
                return $staffEntity->name_with_id;
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        }
    }

    public function onGetClassesSecondaryStaff(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $institutionId = $this->getQueryString('institution_id'); //POCOR-8323
            if ($entity->has('classes_secondary_staff') && !empty($entity->classes_secondary_staff)) {
                $staffList = [];
                foreach ($entity->classes_secondary_staff as $classStaffEntity) {
                    if ($classStaffEntity->has('secondary_staff')) {
                        $staffLink = $event->getSubject()->Html->link($classStaffEntity->secondary_staff->name_with_id, [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffUser',
                            'view',
                            $this->paramsEncode(['id' => $classStaffEntity->secondary_staff->id, 'institution_id' => $institutionId, 'staff_id' => $classStaffEntity->secondary_staff->id])
                        ]);

                        $staffList[] = $staffLink;
                    }
                }
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        } else {
            if ($entity->has('classes_secondary_staff') && !empty($entity->classes_secondary_staff)) {
                $staffList = [];
                foreach ($entity->classes_secondary_staff as $classStaffEntity) {
                    if ($classStaffEntity->has('secondary_staff')) {
                        $staffList[] = $classStaffEntity->secondary_staff->name_with_id;
                    }
                }
                return implode(', ', $staffList);
            } else {
                return $this->getMessage($this->aliasField('noTeacherAssigned'));
            }
        }
    }

    public function onGetTotalStudents(EventInterface $event, Entity $entity)
    {
        /*POCOR-6566 starts*/
        $classId = $entity->id;
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $institutionId = $entity->institution_id;
        $periodId = $entity->academic_period_id;
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $grades = [];
        $classGradeData = $this->find()
            ->select(['grade_id' => $InstitutionClassGrades->aliasField('education_grade_id')])
            ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()], [
                $this->aliasField('id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
            ])
            ->where([
                $InstitutionClassGrades->aliasField('institution_class_id') => $classId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $periodId
            ])
            ->toArray();
        if (!empty($classGradeData)) {
            foreach ($classGradeData as $data) {
                $grades[] = $data->grade_id;
            }
        }

        $totalStudentRecord = $InstitutionClassStudents->find()
            ->where([
                $InstitutionClassStudents->aliasField('institution_class_id') => $classId,
                $InstitutionClassStudents->aliasField('institution_id') => $institutionId,
                $InstitutionClassStudents->aliasField('academic_period_id') => $periodId,
                $InstitutionClassStudents->aliasField('education_grade_id IN') => $grades,
                $InstitutionClassStudents->aliasField('student_status_id IN') => [$statuses['GRADUATED'], $statuses['PROMOTED'], $statuses['CURRENT'], $statuses['REPEATED']]  //POCOR-6733
            ]);
        $count = 0;
        if (!empty($totalStudentRecord)) {
            return $count = $totalStudentRecord->count();
        } else {
            return $count;
        }
        /*POCOR-6566 ends*/
    }


    //POCOR-9613[START]
    // public function onGetTotalMaleStudents(EventInterface $event, Entity $entity)
    // {
    //     /*POCOR-6566 starts*/
    //     $gender_id = 1; // male
    //     $classId = $entity->id;
    //     $institutionId = $entity->institution_id;
    //     $periodId = $entity->academic_period_id;
    //     $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
    //     $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
    //     $grades = [];
    //     $classGradeData = $this->find()
    //         ->select(['grade_id' => $InstitutionClassGrades->aliasField('education_grade_id')])
    //         ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()], [
    //             $this->aliasField('id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
    //         ])
    //         ->where([
    //             $InstitutionClassGrades->aliasField('institution_class_id') => $classId,
    //             $this->aliasField('institution_id') => $institutionId,
    //             $this->aliasField('academic_period_id') => $periodId
    //         ])
    //         ->toArray();
    //     if (!empty($classGradeData)) {
    //         foreach ($classGradeData as $data) {
    //             $grades[] = $data->grade_id;
    //         }
    //     }

    //     $totalMaleStudentRecord = $InstitutionClassStudents->find()
    //         ->contain('Users')
    //         ->matching('StudentStatuses', function ($q) {
    //             return $q->where(['StudentStatuses.code IN' => ['CURRENT', 'REPEATED', 'PROMOTED', 'GRADUATED']]);
    //         })
    //         ->where([
    //             $InstitutionClassStudents->aliasField('institution_class_id') => $classId,
    //             $InstitutionClassStudents->aliasField('institution_id') => $institutionId,
    //             $InstitutionClassStudents->aliasField('academic_period_id') => $periodId,
    //             $InstitutionClassStudents->aliasField('education_grade_id IN') => $grades,
    //             $InstitutionClassStudents->Users->aliasField('gender_id') => $gender_id
    //         ]);
    //     $count = 0;
    //     if (!empty($totalMaleStudentRecord)) {
    //         return $count = $totalMaleStudentRecord->count();
    //     } else {
    //         return $count;
    //     }
    //     /*POCOR-6566 ends*/
    // }

    public function onGetTotalMaleStudents(EventInterface $event, Entity $entity)
    {
        return $entity->total_male_students;
    }

    // public function onGetTotalFemaleStudents(EventInterface $event, Entity $entity)
    // {
    //     /*POCOR-6566 starts*/
    //     $gender_id = 2; // female
    //     $classId = $entity->id;
    //     $institutionId = $entity->institution_id;
    //     $periodId = $entity->academic_period_id;
    //     $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
    //     $InstitutionClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
    //     $grades = [];
    //     $classGradeData = $this->find()
    //         ->select(['grade_id' => $InstitutionClassGrades->aliasField('education_grade_id')])
    //         ->leftJoin([$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()], [
    //             $this->aliasField('id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
    //         ])
    //         ->where([
    //             $InstitutionClassGrades->aliasField('institution_class_id') => $classId,
    //             $this->aliasField('institution_id') => $institutionId,
    //             $this->aliasField('academic_period_id') => $periodId
    //         ])
    //         ->toArray();
    //     if (!empty($classGradeData)) {
    //         foreach ($classGradeData as $data) {
    //             $grades[] = $data->grade_id;
    //         }
    //     }

    //     $totalFemaleStudentRecord = $InstitutionClassStudents->find()
    //         ->contain('Users')
    //         ->matching('StudentStatuses', function ($q) {
    //             return $q->where(['StudentStatuses.code IN' => ['CURRENT', 'REPEATED', 'PROMOTED', 'GRADUATED']]);
    //         })
    //         ->where([
    //             $InstitutionClassStudents->aliasField('institution_class_id') => $classId,
    //             $InstitutionClassStudents->aliasField('institution_id') => $institutionId,
    //             $InstitutionClassStudents->aliasField('academic_period_id') => $periodId,
    //             $InstitutionClassStudents->aliasField('education_grade_id IN') => $grades,
    //             $InstitutionClassStudents->Users->aliasField('gender_id') => $gender_id
    //         ]);
    //     $count = 0;
    //     if (!empty($totalFemaleStudentRecord)) {
    //         return $count = $totalFemaleStudentRecord->count();
    //     } else {
    //         return $count;
    //     }
    //     /*POCOR-6566 ends*/
    // }

    public function onGetTotalFemaleStudents(EventInterface $event, Entity $entity)
    {
        return $entity->total_female_students;
    }

    //POCOR-9613[END]
    public function onExcelGetTotalStudents(EventInterface $event, Entity $entity)
    {
        return $entity->total_male_students + $entity->total_female_students;
    }

    public function onGetSubjects(EventInterface $event, Entity $entity)
    {
        if (!empty($entity->id)) {
            $table = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
            $count = $table
                ->find()
                ->where([$table->aliasField('institution_class_id') => $entity->id])
                ->count();

            $institutionClass = $table
                ->find('all')
                ->where([$table->aliasField('institution_class_id') => $entity->id])
                ->toArray();

            if ($institutionClass[0]->institution_class_id != $entity->id) {
                $ProgGradeSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionProgramGradeSubjects');
                $count = $ProgGradeSubjects
                    ->find()
                    ->where([
                        $ProgGradeSubjects->aliasField('education_grade_id') => $entity->education_stage_order,
                        $ProgGradeSubjects->aliasField('institution_id') => $entity->institution_id
                    ])
                    ->count();
            }

            return $count;
        }
    }

    public function onGetMultigrade(EventInterface $event, Entity $entity)
    {
        if (empty($entity->class_number)) {
            return __('Yes');
        } else {
            return __('No');
        }
    }
    /******************************************************************************************************************
     **
     ** essential functions
     **
     ******************************************************************************************************************/
    public function getClassGradeOptions($institutionClassId)
    {
        $Grade = $this->ClassGrades;
        $gradeOptions = $Grade->find()
            ->contain('EducationGrades')
            ->where([
                $Grade->aliasField('institution_class_id') => $institutionClassId
            ])
            ->toArray();
        $options = [];
        foreach ($gradeOptions as $value) {
            $options[] = $value->education_grade->id;
        }
        return $options;
    }



    /**
     * [getStudentsOptions description]
     * @param  [type] $classEntity [description]
     * @return [type]                [description]
     */
    private function getStudentsOptions($classEntity)
    {
        $academicPeriodId = $classEntity->academic_period_id;
        $academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
        $classGradeObjects = $classEntity->education_grades;
        $classGrades = [];
        foreach ($classGradeObjects as $value) {
            $classGrades[] = $value->id;
        }

        /**
         * Modified this query in PHPOE-1780. Use PeriodBehavior which is loaded InstitutionStudents, by adding ->find('AcademicPeriod', ['academic_period_id'=> $academicPeriodId])
         * This is inline with how InstitutionClassesTable populate getStudentOptions.
         */
        $students = $this->Institutions->Students;

        //logic to get enrolled students from institution which has not been assigned to class
        //the institution student also validated based on the academic period
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $query = $students
            ->find('all')
            ->leftJoin([
                'ClassStudents' => 'institution_class_students'
            ], [
                'ClassStudents.student_id = ' . $students->aliasfield('student_id'),
                'AND' => [
                    'ClassStudents.student_status_id = ' . $enrolled,
                    'ClassStudents.academic_period_id = ' . $academicPeriodId
                ]
            ])
            ->contain([
                'Users' => function ($q) {
                    return $q->select(['id', 'openemis_no', 'first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name']);
                },
                'EducationGrades'
            ])
            ->where([
                $students->aliasField('institution_id') => $classEntity->institution_id,
                $students->aliasField('student_status_id') => $enrolled,
                $students->aliasField('education_grade_id') . ' IN' => $classGrades,
                $students->aliasField('academic_period_id')  => $academicPeriodId,
                'ClassStudents.id IS NULL' //dont have class assigned
            ])
            ->order([
                'EducationGrades.order'
            ])
            ->toArray();

        $studentOptions = [$this->getMessage('Users.select_student')];
        if (!empty($query)) {
            $studentOptions[-1] = $this->getMessage('Users.add_all_student');
        }
        foreach ($query as $obj) {
            /**
             * Modified this filter in PHPOE-1799.
             * Use institution_students table through $this->Institutions->Students where Students being the table alias.
             */
            if (in_array($obj->education_grade_id, $classGrades)) {
                if (isset($obj->user)) {
                    $studentOptions[$obj->education_grade->name][$obj->user->id] = $obj->user->name_with_id;
                } else {
                    $this->log('Data corrupted with no security user for student: ' . $obj->id, 'debug');
                }
            }
        }
        $studentOptions = $this->attachClassInfo($classEntity, $studentOptions);
        return $studentOptions;
    }

    private function attachClassInfo($classEntity, $studentOptions)
    {
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        if (!empty($studentOptions)) {
            $query = $this->ClassStudents->find()
                ->contain(['InstitutionClasses'])
                ->where([
                    $this->aliasField('institution_id') => $classEntity->institution_id,
                    $this->aliasField('academic_period_id') => $classEntity->academic_period_id,
                ])
                ->where([
                    $this->ClassStudents->aliasField('student_id') . ' IN' => array_keys($studentOptions),
                    $this->ClassStudents->aliasField('academic_period_id') => $classEntity->academic_period_id,
                    $this->ClassStudents->aliasField('student_status_id') => $enrolled
                ]);
            $classesWithStudents = $query->toArray();

            foreach ($classesWithStudents as $student) {
                if ($student->institution_class_id != $classEntity->id) {
                    if (!isset($studentOptions[$student->institution_class->name])) {
                        $studentOptions[$student->institution_class->name] = ['text' => 'Class ' . $student->institution_class->name, 'options' => [], 'disabled' => true];
                    }
                    $studentOptions[$student->institution_class->name]['options'][] = ['value' => $student->student_id, 'text' => $studentOptions[$student->student_id]];
                    unset($studentOptions[$student->student_id]);
                }
            }
        }
        return $studentOptions;
    }

    public function getStaffOptions($institutionId, $action = 'edit', $academicPeriodId = 0, $staffIds = [], $institutionShiftId = 0, $homeTeacher = null)
    {
        if (in_array($action, ['edit', 'add'])) {
            $options = [0 => '-- ' . $this->getMessage($this->aliasField('selectTeacherOrLeaveBlank')) . ' --'];
        } else {
            $options = [0 => $this->getMessage($this->aliasField('noTeacherAssigned'))];
        }

        if (empty($staffIds)) {
            $staffIds = [0];
        }

        if (!empty($academicPeriodId)) {
            $academicPeriodObj = $this->AcademicPeriods->get($academicPeriodId);
            $startDate = $this->AcademicPeriods->getDate($academicPeriodObj->start_date);
            $endDate = $this->AcademicPeriods->getDate($academicPeriodObj->end_date);
            $todayDate = new Date();
            // where condition for shift
            if (!empty($institutionShiftId) && $institutionShiftId != 0) {
                $where = ['InstitutionStaffShifts.shift_id' => $institutionShiftId];
            }
            $Staff = $this->Institutions->Staff;
            $query = $Staff->find('all')
                ->select([
                    $Staff->Users->aliasField('id'),
                    $Staff->Users->aliasField('openemis_no'),
                    $Staff->Users->aliasField('first_name'),
                    $Staff->Users->aliasField('middle_name'),
                    $Staff->Users->aliasField('third_name'),
                    $Staff->Users->aliasField('last_name'),
                    $Staff->Users->aliasField('preferred_name')
                ])
                ->contain(['Users'])

                ->find('byInstitution', ['Institutions.id' => $institutionId])
                ->find('AcademicPeriod', ['academic_period_id' => $academicPeriodId])
                //POCOR-7790 :: Connented this join because now shift is not saved when we add staff.
                // ->join(
                //     ['InstitutionStaffShifts' => 'institution_staff_shifts'],
                //     ['InstitutionStaffShifts.staff_id = ' . $Staff->aliasField('staff_id')]
                // )
                ->where($where)
                ->where([
                    $Staff->aliasField('staff_id NOT IN') => $staffIds,
                    $Staff->aliasField('is_homeroom') => 1, //POCOR-5070
                    $Staff->aliasField('start_date <= ') => $todayDate,
                    'OR' => [
                        [$Staff->aliasField('end_date >= ') => $todayDate],
                        [$Staff->aliasField('end_date IS NULL')]
                    ]
                ])
                ->group([$Staff->Users->aliasField('id')]) //POCOR-6735
                ->order([
                    $Staff->Users->aliasField('first_name')
                ]);
            //if($homeTeacher) {
            // $query  ->matching('Positions', function ($q) {
            //     return $q->where(['Positions.is_homeroom' => 1]);
            // });
            //}   //POCOR-7014
            $query->formatResults(function ($results) {
                $returnArr = [];
                foreach ($results as $result) {
                    // if ($result->has('Users')) {
                    //     $returnArr[$result->Users->id] = $result->Users->name_with_id;
                    // }
                    //POCOR-8323 starts
                    if ($result->has('user')) {
                        $returnArr[$result->user->id] = $result->user->name_with_id;
                    } //POCOR-8323 ends
                }
                return $returnArr;
            });
            $options = $options + $query->toArray();
        }
        return $options;
    }

    public function getExistedClasses($institutionId, $academicPeriodId, $educationGradeId)
    {
        //POCOR-8904 start
        $data =[];
        if(!$institutionId || !$academicPeriodId || !$educationGradeId) {
            return $data;
        }
         //POCOR-8904 end
        $data = $this->find('list', [
            'keyField' => 'id',
            'valueField' => 'name'
        ])
            ->join([
                [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('id'),
                        'InstitutionClassGrades.education_grade_id = ' . $educationGradeId
                    ]
                ]
            ])
            ->where([
                /**
                 * If class_number is null, it is considered as a multi-grade class
                 */
                $this->aliasField('class_number') . ' IS NOT NULL',
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->toArray();
        return $data;
    }

    public function createVirtualStudentEntity($id, $entity)
    {
        $StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        if ($entity->has('education_grades')) { //build grades array to cater for multi grade class
            foreach ($entity->education_grades as $value) {
                $educationGrades[] = $value->id;
            }
        }

        $InstitutionStudentsTable = $this->Institutions->Students;
        $userData = $InstitutionStudentsTable->find()
            ->contain(['Users' => ['Genders'], 'StudentStatuses', 'EducationGrades'])
            ->where([
                $InstitutionStudentsTable->aliasField('student_id') => $id,
                $InstitutionStudentsTable->aliasField('institution_id') => $entity->institution_id,
                $InstitutionStudentsTable->aliasField('academic_period_id') => $entity->academic_period_id,
                //this is to ensure that student have the correct education grade accordingly.
                $InstitutionStudentsTable->aliasField('education_grade_id IN ') => $educationGrades
            ])
            ->first();

        if ($userData) {
            $data = [
                'id' => $this->getExistingRecordId($id, $entity),
                'student_id' => $id,
                'institution_class_id' => $entity->id,
                'education_grade_id' =>  $userData->education_grade_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'student_status_id' => $userData->student_status_id,
                'education_grade' => [],
                'student_status' => [],
                'user' => []
            ];
            $student = $this->ClassStudents->newEntity();
            $student = $this->ClassStudents->patchEntity($student, $data);
            $student->user = $userData->user;
            $student->student_status = $userData->student_status;
            $student->education_grade = $userData->education_grade;
            return $student;
        } else {
            return null;
        }
    }

    public function getExistingRecordId($securityId, $entity)
    {
        $id = Text::uuid();
        foreach ($entity->class_students as $student) {
            if ($student->student_id == $securityId) {
                $id = $student->id;
            }
        }
        return $id;
    }

    private function getAcademicPeriodOptions($institutionId)
    {
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $conditions = [$InstitutionGrades->aliasField('institution_id') => $institutionId];
        return $InstitutionGrades->getAcademicPeriodOptions($this->Alert, $conditions);
    }

    public function findClassOptions(Query $query, array $options)
    {
        $institutionId = isset($options['institution_id']) ? $options['institution_id'] : null;
        $academicPeriodId = isset($options['academic_period_id']) ? $options['academic_period_id'] : null;
        $gradeId = isset($options['grade_id']) ? $options['grade_id'] : null;

        if (!is_null($academicPeriodId) && !is_null($institutionId) && !is_null($gradeId)) {
            $query->select(['InstitutionClasses.id', 'InstitutionClasses.name']);
            $query->where([
                'InstitutionClasses.academic_period_id' => $academicPeriodId,
                'InstitutionClasses.institution_id' => $institutionId
            ]);
            if ($gradeId != false) {
                $query->join(
                    [
                        [
                            'table' => 'institution_class_grades',
                            'alias' => 'InstitutionClassGrades',
                            'conditions' => [
                                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassGrades.education_grade_id = ' . $gradeId
                            ]
                        ]
                    ]
                );
                $query->group(['InstitutionClasses.id']);
            }
        } else {
            // incomplete data return nothing
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    public function findSubjectClassOptions(Query $query, array $options)
    {
        $institutionId = isset($options['institution_id']) ? $options['institution_id'] : null;
        $academicPeriodId = isset($options['academic_period_id']) ? $options['academic_period_id'] : null;
        $gradeId = isset($options['grade_id']) ? $options['grade_id'] : null;
        $institutionSubjectId = isset($options['institution_subject_id']) ? $options['institution_subject_id'] : null;

        if (!is_null($academicPeriodId) && !is_null($institutionId) && !is_null($gradeId)) {
            $query
                ->select(['InstitutionClasses.id', 'InstitutionClasses.name'])
                ->where([
                    'InstitutionClasses.academic_period_id' => $academicPeriodId,
                    'InstitutionClasses.institution_id' => $institutionId
                ])
                ->join(
                    [
                        [
                            'table' => 'institution_class_grades',
                            'alias' => 'InstitutionClassGrades',
                            'conditions' => [
                                'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                                'InstitutionClassGrades.education_grade_id = ' . $gradeId
                            ]
                        ]
                    ]
                )
                ->group(['InstitutionClasses.id']);
        } else {
            // incomplete data return nothing
            $query->where([$this->aliasField('id') => -1]);
        }

        return $query;
    }

    /**
     * Used by Institution/UserBehavior && Institution/InstitutionStudentsTable
     * @param  [integer]  $academicPeriodId [description]
     * @param  [integer]  $institutionId    [description]
     * @param  boolean $gradeId          [description]
     * @return [type]                    [description]
     */
    public function getClassOptions($academicPeriodId, $institutionId, $gradeId = false)
    {
        $multiGradeOptions = [
            'fields' => ['InstitutionClasses.id', 'InstitutionClasses.name'],
            'conditions' => [
                'InstitutionClasses.academic_period_id' => $academicPeriodId,
                'InstitutionClasses.institution_id' => $institutionId
            ],
            'order' => ['InstitutionClasses.name']
        ];

        if ($gradeId != false) {
            $multiGradeOptions['join'] = [
                [
                    'table' => 'institution_class_grades',
                    'alias' => 'InstitutionClassGrades',
                    'conditions' => [
                        'InstitutionClassGrades.institution_class_id = InstitutionClasses.id',
                        'InstitutionClassGrades.education_grade_id = ' . $gradeId
                    ]
                ]
            ];
            $multiGradeOptions['group'] = ['InstitutionClasses.id'];
        }

        $multiGradeData = $this->find('list', $multiGradeOptions);
        return $multiGradeData->toArray();
    }

    public function findClassesByInstitutionAndAcademicPeriod(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $staffId = $options['user']['id'];
        $isStaff = $options['user']['is_staff'];

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name')
            ])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([$this->aliasField('name')]);

        if ($options['user']['super_admin'] == 0) {
            $query
                ->select([
                    'SecurityRoleFunctions._view',
                    'SecurityRoleFunctions._edit'
                ]);
            $allclassesPermission = $this->getRolePermissionAccessForAllClasses($staffId, $institutionId);
            $mySubjectsPermission = $this->getRolePermissionAccessForMySubjects($staffId, $institutionId);
            $myClassesPermission = $this->getRolePermissionAccessForMyClasses($staffId, $institutionId);
            if (!$allclassesPermission) {
                if ($mySubjectsPermission && !$myClassesPermission) {
                    $InstitutionClassSubjects = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
                    $query
                        ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            [
                                'InstitutionClassSubjects.institution_class_id = ' . $this->aliasField('id')
                            ]
                        ])
                        ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id',
                                'InstitutionSubjectStaff.staff_id' => $staffId
                            ]
                        ]);
                } else if ($myClassesPermission && !$mySubjectsPermission) {
                    $query
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            [
                                'InstitutionClassesSecondaryStaff.institution_class_id = ' . $this->aliasField('id')
                            ]
                        ])
                        ->where([
                            'OR' => [
                                $this->aliasField('staff_id') => $staffId,
                                'InstitutionClassesSecondaryStaff.secondary_staff_id' => $staffId
                            ]
                        ]);
                } else if ($myClassesPermission && $mySubjectsPermission) {
                    $query
                        ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                            [
                                'InstitutionClassSubjects.institution_class_id = ' . $this->aliasField('id')
                            ]
                        ])
                        ->leftJoin(['InstitutionSubjectStaff' => 'institution_subject_staff'], [
                            [
                                'InstitutionSubjectStaff.institution_subject_id = InstitutionClassSubjects.institution_subject_id',
                                'InstitutionSubjectStaff.staff_id' => $staffId
                            ]
                        ])
                        ->leftJoin(['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'], [
                            [
                                'InstitutionClassesSecondaryStaff.institution_class_id = ' . $this->aliasField('id')
                            ]
                        ])
                        ->where([
                            'OR' => [
                                $this->aliasField('staff_id') => $staffId,
                                'InstitutionClassesSecondaryStaff.secondary_staff_id' => $staffId,
                                'InstitutionSubjectStaff.staff_id' => $staffId
                            ]
                        ]);
                }
            }
        }

        return $query;
    }

    public function getSubjectClasses($institutionId, $academicPeriodId, $gradeId, $subjectId)
    {
        return $this->find('list')->where([
            $this->aliasField('institution_id') => $institutionId,
            $this->aliasField('academic_period_id') => $academicPeriodId
        ])
            ->innerJoinWith('EducationGrades', function ($q) use ($gradeId) {
                return $q->where(['EducationGrades.id' => $gradeId]);
            })
            ->innerJoinWith('InstitutionSubjects', function ($q) use ($subjectId) {
                return $q->where(['InstitutionSubjects.education_subject_id' => $subjectId]);
            })
            ->toArray();
    }

    protected function tooltipMessage()
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $maxCapacity = $ConfigItems->value('max_students_per_class');

        $message =  "Capacity must not exceed " . $maxCapacity . " students per class";
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }

    public function getRolePermissionAccessForMyClasses($userId, $institutionId)
    {
        $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        $QueryResult = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
            ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                ]
            ])
            ->where([
                'SecurityFunctions.controller' => 'Institutions',
                'SecurityRoleFunctions.security_role_id IN' => $roles,
                'AND' => [
                    'OR' => [
                        "SecurityFunctions.`_view` LIKE 'Classes.index%'",
                        "SecurityFunctions.`_view` LIKE 'Classes.view%'"
                    ]
                ],
                'SecurityRoleFunctions._view' => 1
            ])
            ->toArray();
        if (!empty($QueryResult)) {
            return true;
        }

        return false;
    }

    public function getRolePermissionAccessForMySubjects($userId, $institutionId)
    {
        $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        $QueryResult = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
            ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                ]
            ])
            ->where([
                'SecurityFunctions.controller' => 'Institutions',
                'SecurityRoleFunctions.security_role_id IN' => $roles,
                'AND' => [
                    'OR' => [
                        "SecurityFunctions.`_view` LIKE 'Subjects.index%'",
                        "SecurityFunctions.`_view` LIKE 'Subjects.view%'"
                    ]
                ],
                'SecurityRoleFunctions._view' => 1
            ])
            ->toArray();
        if (!empty($QueryResult)) {
            return true;
        }

        return false;
    }

    public function getRolePermissionAccessForAllClasses($userId, $institutionId)
    {
        $roles = TableRegistry::getTableLocator()->get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
        $QueryResult = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
            ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                [
                    'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                ]
            ])
            ->where([
                'SecurityFunctions.controller' => 'Institutions',
                'SecurityRoleFunctions.security_role_id IN' => $roles,
                'AND' => [
                    'OR' => [
                        "SecurityFunctions.`_view` LIKE 'AllClasses.index%'",
                        "SecurityFunctions.`_view` LIKE 'AllClasses.view%'"
                    ]
                ],
                'SecurityRoleFunctions._view' => 1
            ])
            ->toArray();
        if (!empty($QueryResult)) {
            return true;
        }

        return false;
    }

    public function findGradesByInstitutionAndAcademicPeriodAndInstitutionClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $institutionClassId = $options['institution_class_id'];
        $institutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');

        $query->select([
            'id' => $EducationGrades->aliasField('id'),
            'name' => $EducationGrades->aliasField('name')
        ])
            ->innerJoin(
                [$institutionClassGrades->getAlias() => $institutionClassGrades->getTable()],
                [$this->aliasField('id = ') . $institutionClassGrades->aliasField('institution_class_id')]
            )->innerJoin(
                [$EducationGrades->getAlias() => $EducationGrades->getTable()],
                [$EducationGrades->aliasField('id = ') . $institutionClassGrades->aliasField('education_grade_id')]
            )
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $institutionClassGrades->aliasField('institution_class_id') => $institutionClassId
            ])
            ->group([$EducationGrades->aliasField('id')])
            ->order([$EducationGrades->aliasField('name')]);

        return $query;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        //echo "<pre>"; print_r($cloneFields); exit;
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if ($value['field'] == 'secondary_teacher') {
                //START:POCOR-6678
                $newFields[] = [
                    'key' => '',
                    'field' => 'subject_teachers',
                    'type' => 'string',
                    'label' => 'Subject Teachers'
                ];
                //END:POCOR-6678
                $newFields[] = [
                    'key' => 'InstitutionClasses.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'string',
                    'label' => 'Total Male Student'
                ];

                $newFields[] = [
                    'key' => 'InstitutionClasses.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'string',
                    'label' => 'Total Female Student'
                ];
                $newFields[] = [
                    'key' => '',
                    'field' => 'total_students',
                    'type' => 'integer',
                    'label' => 'Total Students'
                ];
            }
        }
        //print_r($newFields); exit;
        $fields->exchangeArray($newFields);
    }


    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $extra, Query $query)
    {
        $requestQuery = $this->request->getQuery();
        $institutionID = $this->getInstitutionID();
        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();
        //Start:POCOR-6678 add institution_class_id in field
        $query
            ->select([
                'institution_class_id' => 'InstitutionClasses.id',
                'total_male_students' => 'InstitutionClasses.total_male_students',
                'total_female_students' => 'InstitutionClasses.total_female_students'
            ])
            ->where([
                $this->aliasField('academic_period_id =') . $selectedAcademicPeriodId,
                $this->aliasField('Institutions.id =') . $institutionID,
            ]);
        //End:POCOR-6678
        /**
         * added condition to make query on the bases on selected class and exporting student's list
         * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
         * @ticket POCOR-6635 starts
         */
        //$encodedClassId = $this->request->getAttribute('params')['pass'][1];//POCOR-8323
        $checkEncodedClassId = $this->request->getAttribute('params')['pass'][1] ?? null; //POCOR-8323
        if ($checkEncodedClassId) {
            $encodedClassId = $this->paramsDecode($checkEncodedClassId); //POCOR-8323
            if (isset($encodedClassId['institution_class_id'])) { //POCOR-8323
                $query;
            } else {
                $query->group(['InstitutionClasses.id']);
            }
        } else {
            $query->group(['InstitutionClasses.id']);
        }
        //POCOR-6635 ends

        //Start:POCOR-6678
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {

                $institutionClassSubjectsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionClassSubjects');
                $institutionClassSubjecs = $institutionClassSubjectsTable->find()
                    ->where(['institution_class_id' => $row['institution_class_id']])->all();

                $nArr = [];
                foreach ($institutionClassSubjecs as $key => $institutionClassSubject) {
                    $institutionSubjectStaffTable = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
                    $institutionSubjectStaff[$key] = $institutionSubjectStaffTable->find()
                        ->where(['institution_subject_id' => $institutionClassSubject['institution_subject_id']])->all();

                    foreach ($institutionSubjectStaff[$key] as $kj => $singleArr) {
                        $nArr[$key][$kj] = $singleArr->staff_id;
                    }
                }

                $splArr = array_unique($this->array_flatten($nArr));
                $subteachers = '';
                foreach ($splArr as $kjj => $institutionSubjectStaffOne) {

                    $staffUserTable = TableRegistry::getTableLocator()->get('Security.Users');
                    $staffUserData = $staffUserTable->find()
                        ->where(['id' => $institutionSubjectStaffOne])->first();
                    $subteachers .=  $staffUserData['first_name'] . ' ' . $staffUserData['last_name'] . ',';
                }
                if (empty($row['subject_teachers'])) {
                    $row['subject_teachers'] = rtrim($subteachers, ',');
                }

                return $row;
            });
        });
        //End:POCOR-6678
    }

    function array_flatten($array)
    {
        if (!is_array($array)) {
            return FALSE;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    //POCOR-8538 start
    private function getCustomFieldData($class_id)
    {
        $connection = ConnectionManager::get('default');

        $sql = "
            SELECT
                CustomModules.*,
                InstitutionCustomForms.*,
                InstitutionCustomFormsFields.*,
                InstitutionCustomFields.*,
                InstitutionCustomFieldOptions.*,
                InstitutionClassesCustomFieldValues.*,
                InstitutionClasses.*
            FROM
                custom_modules AS CustomModules
            INNER JOIN
                institution_custom_forms AS InstitutionCustomForms ON InstitutionCustomForms.custom_module_id = CustomModules.id
            INNER JOIN
                institution_custom_forms_fields AS InstitutionCustomFormsFields ON InstitutionCustomFormsFields.institution_custom_form_id = InstitutionCustomForms.id
            INNER JOIN
                institution_custom_fields AS InstitutionCustomFields ON InstitutionCustomFields.id = InstitutionCustomFormsFields.institution_custom_field_id
            LEFT JOIN
                institution_custom_field_options AS InstitutionCustomFieldOptions ON InstitutionCustomFieldOptions.institution_custom_field_id = InstitutionCustomFields.id
            LEFT JOIN
                institution_classes_custom_field_values AS InstitutionClassesCustomFieldValues ON
                InstitutionClassesCustomFieldValues.institution_custom_field_id = InstitutionCustomFormsFields.institution_custom_field_id
            LEFT JOIN
            institution_classes AS InstitutionClasses ON
            InstitutionClassesCustomFieldValues.institution_class_id = InstitutionClasses.id
            WHERE
                CustomModules.code = 'Institution > Classes' AND InstitutionClasses.id = " . $class_id;


        $result = $connection->execute($sql)->fetchAll('assoc');

        return $result;
    }
    //POCOR-8538 end
}