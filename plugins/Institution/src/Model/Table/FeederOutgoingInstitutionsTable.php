<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Log\Log;

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
                    'id',
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
        $this->field('modified_user_id', ['visible' => 'false']);
        $this->field('modified', ['visible' => 'false']);

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
            'type' => 'select',
            'entity' => $entity
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
        if ($this->action == 'index') {
            $areaName = $entity->institution->area->name;
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $AreasTable = TableRegistry::get('Area.Areas');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            // Getting the current area id
            $areaId = $entity->institution->area->id;
            try {
                if ($areaId > 0) {
                    $path = $AreasTable
                        ->find('path', ['for' => $areaId])
                        ->contain('AreaLevels')
                        ->toArray();

                    foreach ($path as $value) {
                        if ($value['area_level']['level'] == $areaLevel) {
                            $areaName = $value['name'];
                        }
                    }
                }
            } catch (InvalidPrimaryKeyException $ex) {
                Log::write('error', $ex->getMessage());
            }
            return $areaName;
        }
        return $entity->institution->area->name;

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'area_education' && $this->action == 'index') {
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            $AreaTable = TableRegistry::get('Area.AreaLevels');
            $value = $AreaTable->find()
                    ->where([$AreaTable->aliasField('level') => $areaLevel])
                    ->first();

            if (is_object($value)) {
                return $value->name;
            } else {
                return $areaLevel;
            }
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
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

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action = 'add') {
            $areaEducationList = [];
            $entity = $attr['entity'];

            $nextAcademicPeriodId = 0;
            $nextEducationGrades = [];

            if ($entity->has('academic_period_id')) {
                $nextAcademicPeriodId = $this->AcademicPeriods->getNextAcademicPeriodId($entity->academic_period_id);
            }

            if ($entity->has('education_grade_id')) {
                $selectedEducationGrade =$entity->education_grade_id;
                $nextEducationGrades = array_keys($this->EducationGrades->getNextAvailableEducationGrades($selectedEducationGrade, true, true));
            }

            if (!empty($nextAcademicPeriodId) && !empty($nextEducationGrades)) {
                $nextPeriodData = $this->AcademicPeriods->get($nextAcademicPeriodId);
                if ($nextPeriodData->start_date instanceof Time) {
                    $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
                } else {
                    $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
                }

                $AreasTable = TableRegistry::get('Area.Areas');
                $areaEducationList = $AreasTable->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->innerJoinWith('Institutions.InstitutionGrades')
                    ->where([
                        'InstitutionGrades.education_grade_id IN ' => $nextEducationGrades,
                        $this->Institutions->aliasField('id').' <> ' => $this->institutionId,
                        'InstitutionGrades.start_date <=' => $nextPeriodStartDate,
                        'OR' => [
                                'InstitutionGrades.end_date IS NULL',
                                'InstitutionGrades.end_date >=' => $nextPeriodStartDate
                        ]
                    ])
                    ->order([$AreasTable->aliasField('order')])
                    ->toArray();
            }

            if (empty($areaEducationList)) {
                $areaEducationOptions = ['' => $this->getMessage('general.select.noOptions')];

                $attr['type'] = 'select';
                $attr['options'] = $areaEducationOptions;
            } else {
                $areaEducationOptions = ['' => '-- '.__('Select').' --'] + $areaEducationList;
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $areaEducationOptions;
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
                $nextPeriodData = $this->AcademicPeriods->get($nextAcademicPeriodId);
                if ($nextPeriodData->start_date instanceof Time) {
                    $nextPeriodStartDate = $nextPeriodData->start_date->format('Y-m-d');
                } else {
                    $nextPeriodStartDate = date('Y-m-d', strtotime($nextPeriodData->start_date));
                }

                $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
                $InstitutionStatusesTable = TableRegistry::get('Institution.Statuses');
                $activeStatus = $InstitutionStatusesTable->getIdByCode('ACTIVE');

                $institutionList = $this->Institutions
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name',
                        'groupField' => 'type.name'
                    ])
                    ->contain([
                        'Types' => [
                            'fields' => [
                                'name',
                                'order'
                            ]
                        ]
                    ])
                    ->join([
                        'table' => $InstitutionGradesTable->table(),
                        'alias' => $InstitutionGradesTable->alias(),
                        'conditions' => [
                            $InstitutionGradesTable->aliasField('institution_id = ') . $this->Institutions->aliasField('id'),
                            $InstitutionGradesTable->aliasField('education_grade_id IN ') => $nextEducationGrades,
                            $InstitutionGradesTable->aliasField('start_date <=') => $nextPeriodStartDate,
                            'OR' => [
                                $InstitutionGradesTable->aliasField('end_date IS NULL'),
                                $InstitutionGradesTable->aliasField('end_date >=') => $nextPeriodStartDate
                            ]
                        ]
                    ])
                    ->where([
                        $this->Institutions->aliasField('id <>') => $this->institutionId,
                        $this->Institutions->aliasField('area_id') => $areaEducationId,
                        $this->Institutions->aliasField('institution_status_id') => $activeStatus
                    ])
                    ->order([
                        'Types.order' => 'ASC',
                        $this->Institutions->aliasField('name') => 'ASC',
                        $this->Institutions->aliasField('code') => 'ASC'
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
