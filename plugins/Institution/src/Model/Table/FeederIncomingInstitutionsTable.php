<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class FeederIncomingInstitutionsTable  extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('feeders_institutions');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('FeederInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'feeder_institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['FeederIncomingInstitutions' =>['academic_period_id','education_grade_id','feeder_institution_id', 'institution_id']
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.view.beforeAction'] = 'viewBeforeAction';
        return $events;
    }

    /**
     * When view is requested with id=null but composite keys present (old/wrong URL),
     * look up the record by composite key and redirect to the same view with correct id.
     * Prevents "Expression `FeederIncomingInstitutions.id` is missing operator (IS, IS NOT) with `null` value".
     */
    public function viewBeforeAction(EventInterface $event)
    {
        $encodedPass = $this->paramsPass(0);
        if (empty($encodedPass)) {
            return null;
        }
        try {
            $ids = $this->paramsDecode($encodedPass);
        } catch (\Cake\Controller\Exception\SecurityException $e) {
            return null;
        }
        if (!isset($ids['id']) || $ids['id'] !== null) {
            return null;
        }
        $institutionId = $ids['institution_id'] ?? null;
        $academicPeriodId = $ids['academic_period_id'] ?? null;
        $educationGradeId = $ids['education_grade_id'] ?? null;
        $feederInstitutionId = $ids['feeder_institution_id'] ?? null;
        if ($institutionId === null || $academicPeriodId === null || $educationGradeId === null || $feederInstitutionId === null) {
            return null;
        }
        $entity = $this->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('feeder_institution_id') => $feederInstitutionId,
            ])
            ->first();
        if (!$entity || !$entity->get('id')) {
            return null;
        }
        $correctIds = [
            'id' => $entity->get('id'),
            'institution_id' => $institutionId,
            'academic_period_id' => $academicPeriodId,
            'education_grade_id' => $educationGradeId,
            'feeder_institution_id' => $feederInstitutionId,
        ];
        $query = $this->request->getQueryParams();
        $url = array_merge(
            ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'FeederIncomingInstitutions', 'view', $this->paramsEncode($correctIds)],
            ['?' => $query]
        );
        $event->setResult($this->controller->redirect($url));
        $event->stopPropagation();
        return $event->getResult();
    }

    public function onGetCode(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('code')) {
            $value = $entity->institution->code;
        }
        return $value;
    }

    public function onGetAreaEducation(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->feeder_institution->area->name;
            // Getting the system value for the area
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $AreasTable = TableRegistry::getTableLocator()->get('Area.Areas');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            // Getting the current area id
            $areaId = $entity->feeder_institution->area->id;
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
        return $entity->feeder_institution->area->name;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'area_education' && $this->action == 'index') {
            // Getting the system value for the area
            $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            $AreaTable = TableRegistry::getTableLocator()->get('Area.AreaLevels');
            $value = $AreaTable->find()
                    ->where([$AreaTable->aliasField('level') => $areaLevel])
                    ->first();

            if (is_object($value)) {
                return $value->name;
            } else {
                return $areaLevel;
            }
        } else if($field == 'code'){
            return __('Code');
        } else if($field == 'education_grade_id'){
            return __('Education Grade');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetNoOfStudents(EventInterface $event, Entity $entity)
    {
        $noOfStudents = 0;

        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.Students');
        $noOfStudents = $InstitutionStudents
            ->find()
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN ' => ['TRANSFERRED', 'WITHDRAWN', 'REPEATED']]);
            })
            ->where([
                $InstitutionStudents->aliasField('institution_id') => $entity->feeder_institution_id,
                $InstitutionStudents->aliasField('academic_period_id') => $entity->academic_period_id,
                $InstitutionStudents->aliasField('education_grade_id') => $entity->education_grade_id
            ])
            ->count();

        return $noOfStudents;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(); //to show list of academic period for selection
        $extra['selectedAcademicPeriod'] = $this->getSelectedAcademicPeriod($this->request);
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $extra['elements']['control'] = [
            'name' => 'Institution.Feeders/controls',
            'data' => [
                'encodedQueryString' => $encodedQueryString,
                'periodOptions'=> $academicPeriodOptions,
                'selectedPeriodOption'=> $extra['selectedAcademicPeriod']
            ],
            'order' => 3
        ];

        $this->field('code');
        $this->field('feeder_institution_id', [
            'type' => 'select'
        ]);
        $this->field('area_education');
        $this->field('education_grade_id', [
            'type' => 'select'
        ]);
        $this->field('no_of_students');
        $this->field('academic_period_id', ['visible' => 'false']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query
            ->select([
                $this->aliasField('id'),
                'institution_id',
                'feeder_institution_id',
                'academic_period_id',
                'education_grade_id',
                'code' => 'Institutions.code'
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ],
                'FeederInstitutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ],
                'FeederInstitutions.Areas' => [
                    'fields' => [
                        'id',
                        'code',
                        'name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ]
            ]);

        $conditions = [];
        if ($extra->offsetExists('selectedAcademicPeriod')) {
            $selectedAcademicPeriod = $extra['selectedAcademicPeriod'];
            $conditions[$this->aliasField('academic_period_id')] = $selectedAcademicPeriod;
        }

        if (!empty($conditions)) {
            $query->where($conditions);
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', [
            'type' => 'select'
        ]);
        $this->field('code');
        $this->field('feeder_institution_id', [
            'type' => 'select'
        ]);
        $this->field('area_education');
        $this->field('education_grade_id', [
            'type' => 'select'
        ]);
        $this->field('no_of_students');
        $this->field('modified_user_id', ['visible' => 'false']);
        $this->field('modified', ['visible' => 'false']);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ],
                'FeederInstitutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ],
                'FeederInstitutions.Areas' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
            ]);
    }

    private function getSelectedAcademicPeriod($request)
    {
        $selectedAcademicPeriod = '';

        if ($this->action == 'index' || $this->action == 'view' || $this->action == 'edit') {
            $requestData = $this->request->getQuery();
            if (!is_null($requestData) && isset($requestData['period'])) {
                $selectedAcademicPeriod = $requestData['period'];

            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }
}
