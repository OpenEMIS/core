<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class FeederOutgoingInstitutionsTable  extends ControllerActionTable
{
    private $institutionId = null;

    public function initialize(array $config)
    {
        $this->table('feeders_institutions');
        parent::initialize($config);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('FeederInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'feeder_institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('Area.Areapicker');

        $this->toggle('edit', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->requirePresence('area_education_id')
            ->add('institution_id', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['feeder_institution_id', 'academic_period_id', 'education_grade_id']]],
                'provider' => 'table'
            ]);

        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');
    } 

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $extra['selectedAcademicPeriod'] = $this->getSelectedAcademicPeriod($this->request);
        $extra['elements']['control'] = [
            'name' => 'Institution.Feeders/controls',
            'data' => [
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriod']
            ],
            'order' => 3
        ];

        $this->field('code');
        $this->field('recipient_institution');
        $this->field('area_education');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ], 
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ]
        ]);

        $conditions = [];
        if ($extra->offsetExists('selectedAcademicPeriod')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        }

        if (!empty($this->institutionId)) {
            $institutionId = $this->institutionId;
            $conditions[$this->aliasField('feeder_institution_id')] = $institutionId;
        }

        if (!empty($conditions)) {
            // to overwrite the previous where conditions auto added in Institution Controller
            $query->where($conditions, [], true);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('code');
        $this->field('recipient_institution');
        $this->field('area_education');

        $this->setFieldOrder([
            'code',
            'recipient_institution',
            'area_education'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Institutions' => [
                'fields' => [
                    'name',
                    'code'
                ]
            ], 
            'Institutions.Areas' => [
                'fields' => [
                    'name'
                ]
            ]
        ]);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity->academic_period_id = $this->AcademicPeriods->getCurrent();
        $entity->feeder_institution_id = $this->institutionId;
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity
        ]);        
        $this->field('area_education_id', [
            'type' => 'areapicker',
            'source_model' => 'Area.Areas',
            'displayCountry' => false,
            'null' => false,
            'onchange' => true
        ]);
        $this->field('institution_id', [
            'type' => 'select',
            'attr' => [
                'label' => __('Recipient Institution')
            ],
            'entity' => $entity
        ]);
        $this->field('feeder_institution_id', [
            'type' => 'hidden'
        ]);

        $this->setFieldOrder([
            'academic_period_id',
            'education_grade_id',
            'area_education_id',
            'institution_id',
            'feeder_institution_id'
        ]);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('code')) {
            $value = $entity->institution->code;
        }
        return $value;
    }

    public function onGetRecipientInstitution(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('name')) {
            $value = $entity->institution->name;
        }
        return $value;
    }

    public function onGetAreaEducation(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('area') && $entity->institution->area->has('name')) {
            $value = $entity->institution->area->name;
        }
        return $value;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList();

            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = true;
        }
        return $attr;
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $gradeList = [];
            $entity = $attr['entity'];

            if ($entity->has('academic_period_id')) {
                $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
                $institutionId = $this->institutionId;
                $academicPeriodId = $entity->academic_period_id;

                $gradeResults = $InstitutionGradesTable
                    ->find('list', [
                        'keyField' => 'education_grade_id',
                        'valueField' => 'education_grade.programme_grade_name'
                    ])
                    ->contain(['EducationGrades'])
                    ->where([
                        $InstitutionGradesTable->aliasField('institution_id') => $institutionId
                    ])
                    ->find('academicPeriod', ['academic_period_id' => $academicPeriodId])
                    ->toArray();

                foreach ($gradeResults as $gradeId => $value) {
                    $isLastGrade = $this->EducationGrades->isLastGradeInEducationProgrammes($gradeId);
                    if ($isLastGrade) {
                        $gradeList[$gradeId] = $value;
                    }
                }
            }

            if (empty($gradeList)) {
                $gradeOptions = ['' => $this->getMessage('general.select.noOptions')];
                $attr['type'] = 'select';
                $attr['options'] = $gradeOptions;
            } else {
                $gradeOptions = ['' => '-- '.__('Select').' --'] + $gradeList;
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $gradeOptions;
                $attr['onChangeReload'] = true;
            }
        }

        return $attr;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $institutionList = [];
            $entity = $attr['entity'];

            $nextAcademicPeriodId = 0;
            $nextEducationGrades = [];
            $areaEducationId = 0;

            if ($entity->has('academic_period_id')) {
                $nextAcademicPeriodId = $this->AcademicPeriods->getNextAcademicPeriodId($entity->academic_period_id);
            }

            if ($entity->has('education_grade_id')) {
                $selectedEducationGrade =$entity->education_grade_id;
                $nextEducationGrades = array_keys($this->EducationGrades->getNextAvailableEducationGrades($selectedEducationGrade, true, true));
            }

            if ($entity->has('area_education_id')) {
                $areaEducationId = $entity->area_education_id;
            }

            if (!empty($nextAcademicPeriodId) && !empty($nextEducationGrades) && !empty($areaEducationId)) {
                $AreasTable = TableRegistry::get('Area.Areas');
                $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
                $areaEducationEntity = $AreasTable->get($areaEducationId);

                $institutionList = $InstitutionGradesTable
                    ->find('list', [
                        'keyField' => 'institution_id',
                        'valueField' => 'institution.code_name',
                        'groupField' => 'institution.type.name'
                    ])
                    ->contain([
                        'Institutions' => [
                                'fields' => [
                                    'id',
                                    'code',
                                    'name'
                                ]
                            ],
                        'Institutions.Areas' => [
                            'fields' => [
                                'lft',
                                'rght'
                            ]
                        ],
                        'Institutions.Types' => [
                            'fields' => [
                                'name',
                                'order'
                            ]
                        ]
                    ])
                    ->where([
                        $InstitutionGradesTable->aliasField('education_grade_id IN ') => $nextEducationGrades,
                        'Areas.lft >=' => $areaEducationEntity->lft,
                        'Areas.rght <=' => $areaEducationEntity->rght
                    ])
                    ->find('academicPeriod', ['academic_period_id' => $nextAcademicPeriodId])
                    ->order([
                        'Types.order' => 'ASC',
                        'Institutions.code' => 'ASC',
                        'Institutions.name' => 'ASC'
                    ])
                    ->toArray();
            }

            if (empty($institutionList)) {
                $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];

                $attr['type'] = 'select';
                $attr['options'] = $institutionOptions;
            } else {
                $institutionOptions = ['' => '-- '.__('Select').' --'] + $institutionList;
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $institutionOptions;
            }
        }

        return $attr;
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';
        if (isset($request->query) && array_key_exists('period', $request->query)) {
            $selectedAcademicPeriod = $request->query['period'];
        } else {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }
}
