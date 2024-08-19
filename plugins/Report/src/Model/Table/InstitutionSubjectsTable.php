<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Log\Log;

class InstitutionSubjectsTable extends AppTable  {
    public function initialize(array $config): void {
        $this->setTable('institution_subjects');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

    $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.AreaList');//POCOR-7794
    }

    public function beforeAction(Event $event) {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    //POCOR-8391 refactored
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;
        $AreaAdministratives = $this->getDynamicTableInstance('area_administratives');
        $Institutions = $this->getDynamicTableInstance('institutions');
        $Areas = $this->getDynamicTableInstance('areas');
        $AreaLevels = $this->getDynamicTableInstance('area_levels');
        $conditions = $this->buildConditions($academicPeriodId, $institutionId, $requestData->education_subject_id, $areaLevelId, $areaId);

        $InstitutionClassSubjects = $this->getDynamicTableInstance('Institution.InstitutionClassSubjects');
        $InstitutionClasses = $this->getDynamicTableInstance('Institution.InstitutionClasses');
        $EducationGrades = $this->getDynamicTableInstance('Education.EducationGrades');

        $query
            ->select($this->getSelectFields())
            ->contain($this->getContainModels())
            ->innerJoin(
                [$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()],
                [$this->aliasField('id') . ' = ' . $InstitutionClassSubjects->aliasField('institution_subject_id')]
            )
            ->innerJoin(
                [$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()],
                [$InstitutionClassSubjects->aliasField('institution_class_id') . ' = ' . $InstitutionClasses->aliasField('id')]
            )
            ->innerJoin(
                [$EducationGrades->getAlias() => $EducationGrades->getTable()],
                [$EducationGrades->aliasField('id') . ' = ' . $this->aliasField('education_grade_id')]
            )
            ->innerJoin(
                ['Institutions' => $Institutions->getTable()],
                [
                    'Institutions.id = ' . $this->aliasField('institution_id'),
                ]
            )
            ->leftJoin(
                ['AreaAdministratives' => $AreaAdministratives->getTable()],
                [
                    'AreaAdministratives.id = Institutions.area_administrative_id',
                ]
            )
            ->leftJoin(
                ['Areas' => $Areas->getTable()],
                [
                    'Areas.id = Institutions.area_id',
                ]
            )
            ->leftJoin(
                ['ParentAreas' => $Areas->getTable()],
                [
                    'ParentAreas.id = Areas.parent_id',
                ]
            )
            ->leftJoin(
                ['AreaLevels' => $AreaLevels->getTable()],
                ['ParentAreas.area_level_id = AreaLevels.id',
                 'AreaLevels.level != 1']
            )
            ->where([
                $conditions,
                'OR' => [
                    $this->aliasField('total_male_students != 0'),
                    'AND' => [
                        $this->aliasField('total_female_students != 0')
                    ]
                ]
            ]);
        Log::debug($query->sql());
        $query->formatResults([$this, 'formatQueryResults']);
    }

    private function buildConditions($academicPeriodId, $institutionId, $educationSubjectId, $areaLevelId, $areaId)
    {
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[] = $this->aliasField('academic_period_id = ') . $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[] = 'Institutions.id = ' . $institutionId;
        }
        if (!empty($educationSubjectId)) {
            $conditions[] = $this->aliasField('education_subject_id =') . $educationSubjectId;
        }
        $areaList = [];
        if (empty($institutionId) || $institutionId < 0) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        }
        if (!empty($areaList)) {
            $conditions['Institutions.area_id IN'] = $areaList;
        }

        return $conditions;
    }


    private function getSelectFields()
    {
        return [
            'institution_code' => 'Institutions.code',
            'institution_name' => 'Institutions.name',
            'area_code' => 'Areas.code',
            'area_name' => 'Areas.name',
            'area_administrative_code' => 'AreaAdministratives.code',
            'area_administrative_name' => 'AreaAdministratives.name',
            'institution_subject_name' => 'InstitutionSubjects.name',
            'education_subject_name' => 'EducationSubjects.name',
            'education_grade_name' => 'EducationGrades.name',
            'class_name' => 'InstitutionClasses.name',
            'institution_class_id' => 'InstitutionClasses.id',
            'AcademicPeriods.name',
            $this->aliasField('total_male_students'),
            $this->aliasField('total_female_students'),
            $this->aliasField('name'),
            $this->aliasField('no_of_seats'),
            $this->aliasField('total_male_students'),
            $this->aliasField('total_female_students'),
            $this->aliasField('institution_id'),
            $this->aliasField('education_grade_id'),
            $this->aliasField('education_subject_id'),
            $this->aliasField('academic_period_id'),
            'Institutions.area_id',
            'region_code' => 'ParentAreas.code',
            'region_name' => 'ParentAreas.name'
        ];
    }

    private function getContainModels()
    {
        return [
            'EducationGrades',
            'EducationSubjects',
            'AcademicPeriods'
        ];
    }

    public function formatQueryResults(\Cake\Collection\CollectionInterface $results)
    {
        return $results->map(function ($row) {
            $row['total_students'] = $row->total_male_students + $row->total_female_students;
            return $row;
        });
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
            $attr['options'] = $this->controller->getFeatureOptions('Institutions');
            return $attr;
    }

    public function onExcelGetStaffName(Event $event, Entity $entity)
    {
        $InstitutionSubjects = self::getDynamicTableInstance('Report.InstitutionSubjects');
        $InstitutionClassSubjects = self::getDynamicTableInstance('Institution.InstitutionClassSubjects');
        $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $InstitutionSubjectStaff = self::getDynamicTableInstance('Institution.InstitutionSubjectStaff');
        $Staff = self::getDynamicTableInstance('User.Users');
        $conditions = [
            $this->aliasField('education_subject_id') => $entity->education_subject_id,
            $this->aliasField('institution_id') => $entity->institution_id,
            $this->aliasField('education_grade_id') => $entity->education_grade_id,
            $this->aliasField('academic_period_id') => $entity->academic_period_id,
            $InstitutionClassSubjects->aliasField('institution_class_id =') => $entity->institution_class_id,
            ];

        $staffResult = $InstitutionSubjects
                ->find()
                ->select([
                    'staff_id' => 'InstitutionSubjectStaff.staff_id',
                    'Users.openemis_no',
                    'Users.first_name',
                    'Users.last_name'
                ])
                ->leftJoin([$InstitutionClassSubjects->getAlias() => $InstitutionClassSubjects->getTable()], [
                    $this->aliasField('id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                ])
                ->leftJoin([$InstitutionClasses->getAlias() => $InstitutionClasses->getTable()], [
                    $InstitutionClassSubjects->aliasField('institution_class_id =') . $InstitutionClasses->aliasField('id')
                ])
                ->leftJoin([$InstitutionSubjectStaff->getAlias() => $InstitutionSubjectStaff->getTable()], [
                    $InstitutionSubjectStaff->aliasField('institution_subject_id =') . $InstitutionClassSubjects->aliasField('institution_subject_id')
                ])
                ->leftJoin([$Staff->getAlias() => $Staff->getTable()], [
                    $Staff->aliasField('id =') . $InstitutionSubjectStaff->aliasField('staff_id')
                ])
                ->where($conditions)
                ->disableHydration()
                ->toArray()
                ;
        $staffName = [];
        foreach($staffResult as $result){
            if(!empty($result['Users']['openemis_no'])){
                $staffName[] = $result['Users']['openemis_no'].' - '.$result['Users']['first_name'].' '.$result['Users']['last_name'];
            }
        }

        return implode(',', $staffName);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        foreach ($fields as $key => $value) {
            if ($value['field'] == 'education_subject_id') {
                $fields[$key] = array('key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class'));
            }
        }

        $cloneFields = $fields->getArrayCopy();
        $newFields = [];

        foreach ($cloneFields as $key => $value) {

            if (in_array($value['field'], ['academic_period_id'])) {
                    unset($cloneFields[$key]);
                    break;
            }

            if ($value['field'] == 'class_name') {
                $newFields[] = [
                    'key' => 'institution_code',
                    'field' => 'institution_code',
                    'type' => 'string',
                    'label' => __('Institution Code')
                ];

                $newFields[] = [
                    'key' => 'institution_name',
                    'field' => 'institution_name',
                    'type' => 'string',
                    'label' => __('Institution Name')
                ];
                /**POCOR-6726 starts - uncommented area column*/
                $AreaLevelTbl = self::getDynamicTableInstance('area_levels');
                $AreaLevelArr = $AreaLevelTbl
                    ->find()
                    ->select(['id','name'])
                    ->order(['id'=>'DESC'])
                    ->limit(2)
                    ->disableHydration()
                    ->toArray();

                $newFields[] = [
                    'key' => '',
                    'field' => 'region_name',
                    'type' => 'string',
                    'label' => __($AreaLevelArr[1]['name'])
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'area_name',
                    'type' => 'string',
                    'label' => __($AreaLevelArr[0]['name'])
                ];
                /**POCOR-6726 ends*/
                $newFields[] = [
                    'key' => 'InstitutionClasses.name',
                    'field' => 'class_name',
                    'type' => 'string',
                    'label' => __('Institution Class')
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'institution_subject_name',
                    'type' => 'string',
                    'label' => __('Institution Subject Name')
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'education_subject_name',
                    'type' => 'string',
                    'label' => __('Education Subject Name')
                ];

                $newFields[] = [
                    'key' => '',
                    'field' => 'education_grade_name',
                    'type' => 'string',
                    'label' => __('Education Grade')
                ];

                $newFields[] = [
                    'key' => 'staff_name',
                    'field' => 'staff_name',
                    'type' => 'string',
                    'label' => __('Subject Teacher')
                ];

                $newFields[] = [
                    'key' => 'InstitutionSubjects.no_of_seats',
                    'field' => 'no_of_seats',
                    'type' => 'integer',
                    'label' => __('Number of seats')
                ];

                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'integer',
                    'label' => __('Male students')
                ];

                $newFields[] = [
                    'key' => 'InstitutionSubjects.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'integer',
                    'label' => __('Female students')
                ];

                $newFields[] = [
                    'key' => 'total_students',
                    'field' => 'total_students',
                    'type' => 'integer',
                    'label' => __('Total students')
                ];

            }
        }

        $fields->exchangeArray($newFields);
    }

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

}
