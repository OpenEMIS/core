<?php
namespace Quality\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use Cake\I18n\Date;

use App\Model\Table\ControllerActionTable;

class InstitutionQualityVisitsTable extends ControllerActionTable
{
    private $SubjectStaff = null;

    public function initialize(array $config): void
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
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );

        $this->SubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Visits'=>['id']]
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }


    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        //$institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionId = $this->getInstitutionID();

        $Classes = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionClassStudents');
        $periodId = $this->request->getQuery('academic_period_id');

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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::getTableLocator()->get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

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
            'key' => 'QualityVisitTypes.name',
            'field' => 'visit_type',
            'type' => 'string',
            'label' => __('Visit Type')
        ];
        
        $fields->exchangeArray($extraField);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'VisitRequests'];
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupValues($entity);
        $this->setupFields($entity);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['CreatedUser']);
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupValues($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        //clear querystring after add so it wont effected the next add / edit process
        unset($extra['redirect']['period']);
        unset($extra['redirect']['subject']);
    }

    public function onGetStaffId(EventInterface $event, Entity $entity)
    {
        if ($entity->staff) {
            if ($this->action == 'view') {
                return $event->getSubject()->Html->link($entity->staff->name_with_id, [
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            //$institutionId = $this->Session->read('Institution.Institutions.id');
            $institutionId = $this->getInstitutionID();
            $Subjects = $this->Subjects;

            $periodOptions = $this->AcademicPeriods->getYearList(['withSelect' => true, 'isEditable' => true]);
            if (is_null($request->getQuery('period'))) {
                $queryParams = $this->request->getQueryParams();
                $queryParams['period'] = '';
                $this->request = $this->request->withQueryParams($queryParams);
            }
            //POCOR-9112
            if(!empty($this->request->getQuery('period'))){
                $selectedPeriod = $this->getQueryString('period', $periodOptions);
                $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
                    'message' => '{{label}} - ' . $this->getMessage('general.noSubjects'),
                    'callable' => function ($id) use ($Subjects, $institutionId) {
                        return $Subjects
                            ->find()
                            ->where([
                                $Subjects->aliasField('institution_id') => $institutionId,
                                $Subjects->aliasField('academic_period_id') => $id
                            ])->count();
                    }
                ]);
            }
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changePeriod';
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            //$institutionId = $this->Session->read('Institution.Institutions.id');
            $institutionId = $this->getInstitutionID();
            $SubjectStaff = $this->SubjectStaff;

            if ($action == 'add') {
                //POCOR-9112
                // $selectedPeriod = $request->getQuery['period'];
                $selectedPeriod = $request->getData()['InstitutionQualityVisits']['academic_period_id'];
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
                    ->group($this->Subjects->aliasField('id'))
                    ->toArray();
                $classOptions = ['' => __('-- Select Subject --')] + $classOptions;
                if (is_null($request->getQuery('subject'))) {
                    $queryParams = $this->request->getQueryParams();
                    $queryParams['subject'] = '';
                    $this->request = $this->request->withQueryParams($queryParams);
                }
                //POCOR-9112
                // $selectedClass = $this->getQueryString('subject', $classOptions);
                // $this->advancedSelectOptions($classOptions, $selectedClass, [
                //     'message' => '{{label}} - ' . $this->getMessage('general.noStaff'),
                //     'callable' => function ($id) use ($SubjectStaff) {
                //         return $SubjectStaff
                //             ->find()
                //             ->where([
                //                 $SubjectStaff->aliasField('institution_subject_id') => $id
                //             ])
                //             ->count();
                //     }
                // ]);
            }

            $attr['options'] = $classOptions;
            $attr['onChangeReload'] = 'changeSubject';
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'view') {
        } elseif ($action == 'add' || $action == 'edit') {
            if ($action == 'add') {
                $selectedClass = $request->getQuery('subject');
            } elseif ($action == 'edit') {
                $selectedClass = $attr['entity']->institution_subject_id;
            }
            //POCOR-9112
            $selectedClass = $request->getData()['InstitutionQualityVisits']['institution_subject_id'];
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

    public function onUpdateFieldEvaluator(EventInterface $event, array $attr, $action, ServerRequest $request)
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

    public function addEditOnChangePeriod(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $queryParams = $request->getQueryParams();
        unset($queryParams['period']);
        unset($queryParams['subject']);
        $request = $request->withQueryParams($queryParams);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('academic_period_id', $request->getData()[$this->getAlias()])) {
                    $request->getQuery['period'] = $request->getData($this->getAlias())['academic_period_id'];
                }
            }
        }
    }

    public function addEditOnChangeSubject(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        $queryParams = $request->getQueryParams();
        unset($queryParams['subject']);
        $request = $request->withQueryParams($queryParams);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->getAlias(), $request->getData())) {
                if (array_key_exists('institution_subject_id', $request->getData($this->getAlias()))) {
                    $queryParams = $request->getQueryParams();
                    $institutionSubjectId = $this->request->getData($this->getAlias())['institution_subject_id'];
                    $queryParams['subject'] = $institutionSubjectId;
                    $request = $request->withQueryParams($queryParams);
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
            'visible' => ['view' => false, 'edit' => true]]);
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'date':
                return __('Date');
            case 'staff_id':
                return __('Staff');
            case 'comment':
                return __('Comment');
            case 'evaluator':
                return __('Evaluator System');
            case 'academic_period_id':
                return __('Academic Period');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}