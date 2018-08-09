<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class FeederIncomingInstitutionsTable  extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('feeders_institutions');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('FeederInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'feeder_institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);        
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution') && $entity->institution->has('code')) {
            $value = $entity->institution->code;
        }
        return $value;
    }

    public function onGetAreaEducation(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->feeder_institution->area->name;
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $AreasTable = TableRegistry::get('Area.Areas');
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

    public function onGetNoOfStudents(Event $event, Entity $entity)
    {
        $noOfStudents = 0;

        $InstitutionStudents = TableRegistry::get('Institution.Students');
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
        $this->field('feeder_institution_id', [
            'type' => 'select'
        ]);
        $this->field('area_education');
        $this->field('education_grade_id', [
            'type' => 'select'
        ]);
        $this->field('no_of_students');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;

        $query
            ->select([
                'institution_id',
                'feeder_institution_id',
                'academic_period_id',
                'education_grade_id'
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
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
            if (isset($request->query) && array_key_exists('period', $request->query)) {
                $selectedAcademicPeriod = $request->query['period'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }
        } elseif ($this->action == 'add') {
            $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
        }

        return $selectedAcademicPeriod;
    }
}
