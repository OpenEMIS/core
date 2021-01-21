<?php
namespace Quality\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\I18n\Date;

use App\Model\Table\ControllerActionTable;

class InstitutionQualityVisitsTable extends ControllerActionTable
{
    private $SubjectStaff = null;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Subjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'institution_subject_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects', 'foreignKey' => 'institution_subject_id']);
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Quality.Visit');
        $this->addBehavior('Excel', [
            'pages' => ['index'],
            'autoFields' => false
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->SubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }


    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $periodId = $this->request->query['academic_period_id'];

        $query
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->contain([
                'Institutions',
                'AcademicPeriods',
                'InstitutionSubjects',
                'Institutions.Areas',
                'Staff',
                'QualityVisitTypes'
            ])
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'academic_periods' => 'AcademicPeriods.name',
                'date' => $this->aliasField('date'),
                'subject' => 'InstitutionSubjects.name',
                'staff_name' => $query->func()->concat([
                    'Staff.first_name' => 'literal',
                    " ",
                    'Staff.last_name' => 'literal'
                ]),
                'visit_type' => 'QualityVisitTypes.name',
                'comment' => $this->aliasField('comment'),
            ]);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {

                    $firstName = $this->Auth->user('first_name');
                    $lastName = $this->Auth->user('last_name');
                    $evaluator = $firstName . " " . $lastName;
                              
                    $row['evaluator'] = $evaluator;
                    return $row;
                });
            });

        if ($periodId > 0) {
            $query->where([$this->aliasField('academic_period_id') => $periodId]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $extraField[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => 'Institution Code',
        ];

        $extraField[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraField[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $extraField[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_periods',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key' => 'date',
            'field' => 'date',
            'type' => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key' => 'InstitutionSubjects.name',
            'field' => 'subject',
            'type' => 'string',
            'label' => __('Subject')
        ];

        $extraField[] = [
            'key' => 'staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff')
        ];

        $extraField[] = [
            'key' => 'evaluator',
            'field' => 'evaluator',
            'type' => 'string',
            'label' => __('Evaluator')
        ];

        $extraField[] = [
            'key' => 'QualityVisitTypes.name',
            'field' => 'visit_type',
            'type' => 'string',
            'label' => __('Visit Type')
        ];

        $extraField[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        

        $fields->exchangeArray($extraField);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'VisitRequests'];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // from onUpdateToolbarButtons
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $buttons = $extra['indexButtons'];

        $extraButtons = [
            'export' => [
                'permission' => ['Institutions', 'Promotion', 'excel'],
                'action' => 'Visits',
                'icon' => '<i class="fa kd-export"></i>',
                'title' => __('Promotion / Graduation')
            ]
        ];

        // foreach ($extraButtons as $key => $attr) {
        //     if ($this->AccessControl->check($attr['permission'])) {
        //         $button = [
        //             'type' => 'button',
        //             'attr' => $btnAttr,
        //             'url' => [0 => 'add']
        //         ];
        //         $button['url']['action'] = $attr['action'];
        //         $button['attr']['title'] = $attr['title'];
        //         $button['label'] = $attr['icon'];

        //         $extra['toolbarButtons'][$key] = $button;
        //     }
        // }
        $this->field('comment', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => false]);

        $this->setFieldOrder([
            'date', 'institution_subject_id', 'staff_id', 'quality_visit_type_id'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupValues($entity);
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['CreatedUser']);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupValues($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        //clear querystring after add so it wont effected the next add / edit process
        unset($extra['redirect']['period']);
        unset($extra['redirect']['subject']);
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        if ($entity->staff) {
            if ($this->action == 'view') {
                return $event->subject()->Html->link($entity->staff->name_with_id, [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'StaffUser',
                    'view',
                    $this->paramsEncode(['id' => $entity->staff->id])
                ]);
            } else {
                return $entity->staff->name_with_id;
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $Subjects = $this->Subjects;

            $periodOptions = $this->AcademicPeriods->getYearList(['withSelect' => true, 'isEditable' => true]);
            if (is_null($request->query('period'))) {
                $this->request->query['period'] = '';
            }
            $selectedPeriod = $this->queryString('period', $periodOptions);
            $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                'message' => '{{label}} - ' . $this->getMessage('general.noSubjects'),
                'callable' => function ($id) use ($Subjects, $institutionId) {
                    return $Subjects
                        ->find()
                        ->where([
                            $Subjects->aliasField('institution_id') => $institutionId,
                            $Subjects->aliasField('academic_period_id') => $id
                        ])
                        ->count();
                }
            ]);

            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changePeriod';
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            $institutionId = $this->Session->read('Institution.Institutions.id');
            $SubjectStaff = $this->SubjectStaff;

            if ($action == 'add') {
                $selectedPeriod = $request->query('period');
            } elseif ($action == 'edit') {
                $selectedPeriod = $attr['entity']->academic_period_id;
            }

            $classOptions = [];
            if (!is_null($selectedPeriod)) {
                $classOptions = $this->Subjects
                    ->find('list')
                    ->where([
                        $this->Subjects->aliasField('institution_id') => $institutionId,
                        $this->Subjects->aliasField('academic_period_id') => $selectedPeriod
                    ])
                    ->toArray();
                $classOptions = ['' => __('-- Select Subject --')] + $classOptions;

                if (is_null($request->query('subject'))) {
                    $this->request->query['subject'] = '';
                }
                $selectedClass = $this->queryString('subject', $classOptions);
                $this->advancedSelectOptions($classOptions, $selectedClass, [
                    'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
                    'callable' => function ($id) use ($SubjectStaff) {
                        return $SubjectStaff
                            ->find()
                            ->where([
                                $SubjectStaff->aliasField('institution_subject_id') => $id
                            ])
                            ->count();
                    }
                ]);
            }

            $attr['options'] = $classOptions;
            $attr['onChangeReload'] = 'changeSubject';
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $selectedClass = $request->query('subject');
            } elseif ($action == 'edit') {
                $selectedClass = $attr['entity']->institution_subject_id;
            }

            $staffOptions = [];
            if (!is_null($selectedClass)) {
                $staff = $this->SubjectStaff
                    ->find()
                    ->contain('Users')
                    ->where([
                        $this->SubjectStaff->aliasField('institution_subject_id') => $selectedClass
                    ])
                    ->all();

                foreach ($staff as $key => $obj) {
                    $staffOptions[$obj->staff_id] = $obj->user->name_with_id;
                }
            }

            $attr['options'] = $staffOptions;
        }

        return $attr;
    }

    public function onUpdateFieldEvaluator(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add') {
            // when add, is login user
            $firstName = $this->Auth->user('first_name');
            $lastName = $this->Auth->user('last_name');
            $evaluator = $firstName . " " . $lastName;

            $attr['type'] = 'readonly';
            $attr['value'] = $evaluator;
            $attr['attr']['value'] = $evaluator;
        } elseif ($action == 'edit') {
            // when edit, is created user
            $attr['type'] = 'readonly';
        }

        return $attr;
    }

    public function addEditOnChangePeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['period']);
        unset($request->query['subject']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeSubject(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['subject']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('institution_subject_id', $request->data[$this->alias()])) {
                    $request->query['subject'] = $request->data[$this->alias()]['institution_subject_id'];
                }
            }
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        $this->field('institution_subject_id', [
            'entity' => $entity
        ]);
        $this->field('staff_id', [
            'entity' => $entity
        ]);
        $this->field('evaluator');
        $this->field('quality_visit_type_id', ['type' => 'select']);
        $this->field('file_name', [
            'type' => 'hidden',
            'visible' => ['view' => false, 'edit' => true]
        ]);
        $this->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);

        $this->setFieldOrder([
            'date', 'academic_period_id', 'institution_subject_id', 'staff_id',
            'evaluator', 'quality_visit_type_id', 'comment', 'file_name', 'file_content'
        ]);
    }

    public function setupValues(Entity $entity)
    {
        $entity->evaluator = $entity->created_user->name;
    }
}
